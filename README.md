# Level Level plugin autoloader

When `0-loader.php` is inserted into the `mu-plugins` directory it will load all subdirectory plugin as it would normal plugins. 

## Configuration options

You have multiple options to manipulate where the Plugin Loader will look for a vendor folder. Define one of the following values in wp-config:

### 1. Default == Main / parent theme
If you define **nothing** it will load search in the main / parent theme via `get_template_directory()`.

### 2. wp-content directory

Load from the parent directory above `mu-plugins` (should be the `wp-content` dir). This is only used when no `LL_AUTOLOAD_DIR` is set.

```
define('LL_AUTOLOAD_CONTENT_DIR', true);
```

### 3. Child theme
Use the current Child theme via `get_stylesheet_directory` as the theme folder containing the vendor folder. This is only used when no `LL_AUTOLOAD_DIR` is set.

```
define('LL_AUTOLOAD_USE_CHILD', true);
```

### 4. Custom path
Use a specific directory. The script will still append `/vendor/autoload.php` to this path.

```
define('LL_AUTOLOAD_DIR', '/path/to/wordpress/theme/');
```
