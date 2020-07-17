# Level Level plugin autoloader

When `0-loader.php` is inserted into the `mu-plugins` directory it will load all subdirectory plugin as it would normal plugins. 

## Configuration options

You have multiple options to manipulate where the Plugin Loader will look for a vendor folder. Define one of the following values in wp-config:

### 1. Default == wp-content directory
If you define **nothing** it will load the same as 2. `wp-content` directory.

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

### 4. Parent/default theme
Use the current parent theme via `get_template_directory` as the theme folder containing the vendor folder. This is only used when no `LL_AUTOLOAD_DIR` is set.

```
define('LL_AUTOLOAD_USE_PARENT', true);
```

### 5. Custom path
Use a specific directory. The script will still append `/vendor/autoload.php` to this path.

```
define('LL_AUTOLOAD_DIR', '/path/to/wordpress/theme/');
```

### Overview

| **Setting**                                  | **Result**                         |
|----------------------------------------------|------------------------------------|
| Nothing                                      | wp-content directory               |
| `define( 'LL_AUTOLOAD_DIR', '/tmp/' );`      | `/tmp/`                            |
| `define( 'LL_AUTOLOAD_CONTENT_DIR', true );` | wp-content directory               |
| `define( 'LL_AUTOLOAD_USE_CHILD', true );`   | Stylesheet directory (child theme) |
| `define( 'LL_AUTOLOAD_USE_PARENT', true );`  | Template directory (parent theme)  |

## Pre and Post autoload

### Pre autoload
Right before the vendor autoload file is loaded, the `pre-autoload.php` file in the directory specified as the autoload directory is required if it exists. 

This file can be used to set environment variables required in composer loaded dependencies.

### Post autoload
Right after the vendor autoload file is loaded, but before the `mu-plugins` are loaded, the `post-autoload.php` file in the directory specified as the autoload directory is required if it exists.

This file can be used to bootstrap/configure mu-plugin loaded dependencies, or trigger actions that need to happen as early as possible, but autoloading to be set up. 

Adding logging is an example of this. You probably require the Monolog composer dependency, but want it to be bootstrapped before we load the mu-plugins.

## Upgrading from v2.x to v3.x
In v3, the `wp-content` directory loads by default, instead of the template directory in v2. To restore behaviour, set `define( 'LL_AUTOLOAD_USE_PARENT', true );` in `wp-config.php`.
