# Kint - debugging helper for PHP developers

[![](https://travis-ci.org/kint-php/kint.svg?branch=master)](https://travis-ci.org/kint-php/kint)

![Screenshot](https://kint-php.github.io/kint/images/intro.png)

## What am I looking at?

At first glance Kint is just a pretty replacement for **[var_dump()](http://php.net/manual/en/function.var-dump.php)**, **[print_r()](http://php.net/manual/en/function.print-r.php)** and **[debug_backtrace()](http://php.net/manual/en/function.debug-backtrace.php)**.

However, it's much, *much* more than that. Even the excellent `xdebug` var_dump improvements don't come close - you will eventually wonder how you developed without it. 

Just to list some of the most useful features:

## Installation

One of the main goals of Kint is to be **zero setup**.

[Download the file](https://raw.githubusercontent.com/kint-php/kint/master/build/kint.php) and simply
```php
<?php

require 'kint.php';
```

### Or, if you use Composer:

```bash
composer require kint-php/kint
```

## Usage

```php
<?php

Kint::dump($GLOBALS, $_SERVER); // pass any number of parameters
d($GLOBALS, $_SERVER); // or simply use d() as a shorthand

Kint::trace(); // Debug backtrace
d(1); // Debug backtrace shorthand

s($GLOBALS); // Basic output mode

~d($GLOBALS); // Text only output mode

Kint::$enabled_mode = false; // Disable kint
d('Get off my lawn!'); // Debugs no longer have any effect
```

## Tips & Tricks

* Kint is enabled by default, set `Kint::$enabled_mode = false;` to turn its functionality completely off. The best practice is to enable Kint in a development environment only (or for example `Kint::$enabled_mode = ($_SERVER['REMOTE_ADDR'] === '<your IP>');`) - so even if you accidentally leave a dump in production, no one will know.
* Kint has *keyboard shortcuts*! When Kint is visible, press <kbd>D</kbd> on the keyboard and you will be able to traverse the tree with arrows, <kbd>h</kbd><kbd>j</kbd><kbd>k</kbd><kbd>l</kbd>, and <kbd>tab</kbd> keys - and expand/collapse nodes with <kbd>space</kbd> or <kbd>enter</kbd>.
* Clicking the <kbd>+</kbd> sign or the bar will open/close it.
* Double clicking the <kbd>+</kbd> sign will open/close it and all its children.
* Triple clicking the <kbd>+</kbd> sign in will open/close everything on the page.
* See the tiny arrows on the right of the output? Click them to open the node in a separate tab, or to show the access path for the value.
* There are a couple of real-time modifiers you can use:
  * `~d($var)` this call will output in plain text format.
  * `+d($var)` will disregard depth level limits and output everything. (Careful, this can hang your browser on large objects!)
  * `!d($var)` will expand the output automatically.
  * `-d($var)` will attempt to `ob_clean` the previous output.
  * You can combine modifiers too: `~+d($var)`
* To change display theme, use `Kint_Renderer_Rich::$theme = '<theme name>';` where available options are: `'original.css'` (default), `'solarized.css'`, `'solarized-dark.css'` and `'aante-light.css'`. You can also pass the absolute path to a CSS file to use that instead.
* Read [the full documentation](https://kint-php.github.io/kint/) for more information

## Authors

[**Jonathan Vollebregt** (jnvsor)](https://github.com/jnvsor)  
[**Rokas Šleinius** (raveren)](https://github.com/raveren)

## License

Licensed under the MIT License
