# Serve Content Files Plugin for PhileCMS #

This plugin tries some magic™ to directly serve additional files from the `content/` directory:

- change HTML output and insert `content/` if it's missing in URLs
- serve requests without `content/` in URL from the `content/` directory

Note: This plugin trades some performance (potentially serving files through PHP) for convenience (no need to fiddle with the server or content files).

[Project Home](https://github.com/Schlaefer/phileServeContentFiles)

### 1.1 Installation (composer) ###

```json
"require": {
   "siezi/phile-serve-content-files": "*"
}
```

### 1.2 Installation (Manual Download)

* Install [Phile](https://github.com/PhileCMS/Phile)
* copy this plugin into `plugins/siezi/phileServeContentFiles`

### 2. Activation

After you have installed the plugin you need to activate it. Add the following line to Phile's root `config.php` file:

```php
$config['plugins']['siezi\\phileServeContentFiles'] = ['active' => true];
```

### 3. Configuration ###

See plugin `config.php` file.