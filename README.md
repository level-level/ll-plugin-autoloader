# Level Level plugin autoloader

When `0-loader.php` is inserted into the `mu-plugins` directory it will load all subdirectory plugin as it would normal plugins. 

## Configuration options

You can define the following values in wp-config to manipulate
where the mu-plugin will look for a vendor folder.

```
define('LL_AUTOLOAD_DIR', '/path/to/wordpress/theme/');
define('LL_AUTOLOAD_USE_CHILD', true);
```

`LL_AUTOLOAD_DIR` - Use a specific directory. The script will still append `/vendor/autoload.php` to this path).


`LL_AUTOLOAD_USE_CHILD` - Use `get_stylesheet_directory` instead of `get_template_directory` as the theme folder containing the vendor folder. This is only used when no `LL_AUTOLOAD_DIR` is set.