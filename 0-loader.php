<?php

/**
 * Plugin Name: Level Level autoloader, based on Bedrock Autoloader 1.0.4
 * Plugin URI: https://github.com/roots/bedrock/
 * Description: Bedrock Autoloader enables standard plugins to be required just like must-use plugins. The autoloaded plugins are included after all mu-plugins and standard plugins have been loaded. An asterisk next to the name of the plugin designates the plugins that have been autoloaded.
 * Version: 3.1.0
 * Author: Roots
 * Author URI: https://roots.io/
 * License: MIT License
 */

namespace Roots\Bedrock;

/**
 * Class Autoloader
 * @package Roots\Bedrock
 * @author Roots
 * @link https://roots.io/
 */
class Autoloader
{
    /** @var static Singleton instance */
    private static $instance;

    /** @var array Store Autoloader cache and site option */
    private $cache;

    /** @var array Autoloaded plugins */
    private $autoPlugins;

    /** @var array Autoloaded mu-plugins */
    private $muPlugins;

    /** @var int Number of plugins */
    private $count;

    /** @var array Newly activated plugins */
    private $activated;

    /** @var string Relative path to the mu-plugins dir */
    private $relativePath;

    /** @var array|null Configuration for force loaded plugins */
    private $llForcedPluginsConfig;

    /**
     * Create singleton, populate vars, and set WordPress hooks
     */
    public function __construct()
    {
        if (isset(self::$instance)) {
            return;
        }

        self::$instance = $this;

        $this->relativePath = '/../' . basename(WPMU_PLUGIN_DIR);

        if (is_admin()) {
            add_filter('show_advanced_plugins', [$this, 'showInAdmin'], 0, 2);
        }

        $this->llAutoload(); // Call the custom autoloading for Level Level
        $this->llForcedPluginsHooks(); // Call the hooks needed for forced plugins loading for Level Level

        $this->loadPlugins();
    }

    /**
     * Custom function by Level Level.
     * This function returns the directory where the composer.json file is located.
     */
    public function llDetermineAutoloadDir(): string {
        if (defined('LL_AUTOLOAD_DIR')) {
            return constant('LL_AUTOLOAD_DIR');
        }

        if (defined('LL_AUTOLOAD_CONTENT_DIR') && constant('LL_AUTOLOAD_CONTENT_DIR') === true) {
            define('LL_AUTOLOAD_DIR', realpath(__DIR__.'/../'));
            return constant('LL_AUTOLOAD_DIR');
        }

        if (defined('LL_AUTOLOAD_USE_CHILD') && constant('LL_AUTOLOAD_USE_CHILD') === true) {
            define('LL_AUTOLOAD_DIR', get_stylesheet_directory());
            return constant('LL_AUTOLOAD_DIR');
        }

        if (defined('LL_AUTOLOAD_USE_PARENT') && constant('LL_AUTOLOAD_USE_PARENT') === true) {
            define('LL_AUTOLOAD_DIR', get_template_directory());
            return constant('LL_AUTOLOAD_DIR');
        }

        define('LL_AUTOLOAD_DIR', realpath(__DIR__.'/../'));
        return constant('LL_AUTOLOAD_DIR');
    }

    /**
     * Custom function by Level Level.
     * This function loads the pre-autoload file.
     * Then it loads the composer autoload file.
     * Finally it loads the post-autoload file.
     */
    private function llAutoload(): void {
        $autoload_dir = $this->llDetermineAutoloadDir();
        if (file_exists( $autoload_dir . '/pre-autoload.php')) {
            require_once( $autoload_dir . '/pre-autoload.php');
        }

        $autoload_file = $autoload_dir . '/vendor/autoload.php';
        if (file_exists($autoload_file)){
            require_once($autoload_file);

            if (file_exists( $autoload_dir . '/post-autoload.php')) {
                require_once( $autoload_dir . '/post-autoload.php');
            }
        } else {
            trigger_error(sprintf('No vendor autoload file was found @ %s', $autoload_file));
        }
    }

    public function llLoadForcedPlugins(): void {
        $parsed_forced_plugins_config = $this->llGetForcedPluginsConfig();
        if ( empty( $parsed_forced_plugins_config['forced_plugins'] ) ) {
            return;
        }

        foreach ($parsed_forced_plugins_config['forced_plugins'] as $plugin) {
            if ( ! isset( $plugin['slug'] ) ) {
                continue;
            }

            $network = is_multisite() && ( $plugin['network'] ?? false );
            $this->llLoadForcedPlugin( $plugin['slug'], $network );
        }
    }

    private function llForcedPluginsHooks(): void {
        add_action('admin_init', array( $this, 'llLoadForcedPlugins' ));

        add_action( 'load-plugins.php', array( $this, 'llAddPluginActionLinkFilters' ), 1 );
    }

    public function llAddPluginActionLinkFilters(): void {
        $parsed_forced_plugins_config = $this->llGetForcedPluginsConfig();
        $forced_plugins = $parsed_forced_plugins_config['forced_plugins'] ?? array();
        foreach ($forced_plugins as $forced_plugin) {
            add_filter( 'plugin_action_links_' . $forced_plugin['slug'], array( $this, 'llFilterPluginActionLinksDeactivate' ) );
            add_filter( 'network_admin_plugin_action_links_' . $forced_plugin['slug'], array( $this, 'llFilterPluginActionLinksDeactivate' ) );
        }
    }

    private function llLoadForcedPlugin( string $slug, bool $network ): bool {
        $wp_plugins = get_plugins();
        if (! isset($wp_plugins[$slug])) {
            return false;
        }

        if ((! $network && is_plugin_active($slug)) || is_plugin_active_for_network($slug)) {
            return false;
        }

        $activation_result = $network ? activate_plugin($slug, '', true) : activate_plugin($slug);
        return ! is_wp_error($activation_result);
    }

    private function llGetForcedPluginsConfig(): ?array {
        if ( ! is_null( $this->llForcedPluginsConfig ) ) {
            return $this->llForcedPluginsConfig;
        }

        $json_file = $this->llDetermineAutoloadDir() . '/ll-forced-plugins.json';
        if (!file_exists($json_file)) {
            $this->llForcedPluginsConfig = null;
            return $this->llForcedPluginsConfig;
        }

        $json = file_get_contents($json_file) ?: '';
        $parsed_json = json_decode($json, true);
        if ( ! is_array( $parsed_json ) ) {
            $this->llForcedPluginsConfig = null;
            return $this->llForcedPluginsConfig;
        }
        $this->llForcedPluginsConfig = $parsed_json;
        return $this->llForcedPluginsConfig;
    }

    public function llFilterPluginActionLinksDeactivate( array $actions ): array {
        unset( $actions['deactivate'] );
        $actions[] = 'Force activated by LL Autoloader';
        return $actions;
    }


   /**
    * Run some checks then autoload our plugins.
    */
    public function loadPlugins()
    {
        $this->checkCache();
        $this->validatePlugins();
        $this->countPlugins();

        array_map(static function () {
            include_once WPMU_PLUGIN_DIR . '/' . func_get_args()[0];
        }, array_keys($this->cache['plugins']));

        add_action('plugins_loaded', [$this, 'pluginHooks'], -9999);
    }

    /**
     * Filter show_advanced_plugins to display the autoloaded plugins.
     * @param $show bool Whether to show the advanced plugins for the specified plugin type.
     * @param $type string The plugin type, i.e., `mustuse` or `dropins`
     * @return bool We return `false` to prevent WordPress from overriding our work
     * {@internal We add the plugin details ourselves, so we return false to disable the filter.}
     */
    public function showInAdmin($show, $type)
    {
        $screen = get_current_screen();
        $current = is_multisite() ? 'plugins-network' : 'plugins';

        if ($screen->base !== $current || $type !== 'mustuse' || !current_user_can('activate_plugins')) {
            return $show;
        }

        $this->updateCache();

        $this->autoPlugins = array_map(function ($auto_plugin) {
            $auto_plugin['Name'] .= ' *';
            return $auto_plugin;
        }, $this->autoPlugins);

        $GLOBALS['plugins']['mustuse'] = array_unique(array_merge($this->autoPlugins, $this->muPlugins), SORT_REGULAR);

        return false;
    }

    /**
     * This sets the cache or calls for an update
     */
    private function checkCache()
    {
        $cache = get_site_option('bedrock_autoloader');

        if ($cache === false || (isset($cache['plugins'], $cache['count']) && count($cache['plugins']) !== $cache['count'])) {
            $this->updateCache();
            return;
        }

        $this->cache = $cache;
    }

    /**
     * Get the plugins and mu-plugins from the mu-plugin path and remove duplicates.
     * Check cache against current plugins for newly activated plugins.
     * After that, we can update the cache.
     */
    private function updateCache()
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $this->autoPlugins = get_plugins($this->relativePath);
        $this->muPlugins   = get_mu_plugins();
        $plugins           = array_diff_key($this->autoPlugins, $this->muPlugins);
        $rebuild           = !isset($this->cache['plugins']);
        $this->activated   = $rebuild ? $plugins : array_diff_key($plugins, $this->cache['plugins']);
        $this->cache       = ['plugins' => $plugins, 'count' => $this->countPlugins()];

        update_site_option('bedrock_autoloader', $this->cache);
    }

    /**
     * This accounts for the plugin hooks that would run if the plugins were
     * loaded as usual. Plugins are removed by deletion, so there's no way
     * to deactivate or uninstall.
     */
    public function pluginHooks()
    {
        if (!is_array($this->activated)) {
            return;
        }

        foreach ($this->activated as $plugin_file => $plugin_info) {
            do_action('activate_' . $plugin_file);
        }
    }

    /**
     * Check that the plugin file exists, if it doesn't update the cache.
     */
    private function validatePlugins()
    {
        foreach ($this->cache['plugins'] as $plugin_file => $plugin_info) {
            if (!file_exists(WPMU_PLUGIN_DIR . '/' . $plugin_file)) {
                $this->updateCache();
                break;
            }
        }
    }

    /**
     * Count the number of autoloaded plugins.
     *
     * Count our plugins (but only once) by counting the top level folders in the
     * mu-plugins dir. If it's more or less than last time, update the cache.
     *
     * @return int Number of autoloaded plugins.
     */
    private function countPlugins()
    {
        if (isset($this->count)) {
            return $this->count;
        }

        $count = count(glob(WPMU_PLUGIN_DIR . '/*/', GLOB_ONLYDIR | GLOB_NOSORT));

        if (!isset($this->cache['count']) || $count !== $this->cache['count']) {
            $this->count = $count;
            $this->updateCache();
        }

        return $this->count;
    }
}

if (is_blog_installed() && class_exists(Autoloader::class)) {
    new Autoloader();
}
