# Kint - Advanced PHP dumper

![Screenshot](https://kint-php.github.io/kint/images/intro.png)

## What am I looking at?

Kint is a dumper in the vein of **[var_dump()](https://www.php.net/function.var_dump)**, with keyboard controls, search, access path provision, and automatic data parsing.

In other words, when you dump a JSON string Kint will let you unfold and search the JSON structure and even provide you the code you need to access specific fields.

## Installation

```bash
composer require kint-php/kint --dev
```

### Without composer

[Download the file](https://raw.githubusercontent.com/kint-php/kint/master/build/kint.phar) and simply
```php
require 'kint.phar';
```

## Usage

```php
<?php

Kint::dump($GLOBALS, $_SERVER); // pass any number of parameters
d($GLOBALS, $_SERVER); // or simply use d() as a shorthand

Kint::trace(); // Debug backtrace

s($GLOBALS); // Basic output mode

+d($GLOBALS); // No depth limit

Kint::$enabled_mode = false; // Disable kint
d('Get off my lawn!'); // Debugs no longer have any effect
```

You can set `Kint\Renderer\AbstractRenderer::$js_nonce` and `$css_nonce` to have Kint work with CSP

There's a small demo and full documentation on [the GH pages site](https://kint-php.github.io/kint/)

## Authors

[**Jonathan Vollebregt** (jnvsor)](https://github.com/jnvsor)  
[Contributors](https://github.com/kint-php/kint/graphs/contributors)

## License

Licensed under the MIT License
