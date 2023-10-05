# Kint - debugging helper for PHP developers

![Screenshot](https://kint-php.github.io/kint/images/intro.png)

## What am I looking at?

At first glance Kint is just a pretty replacement for **[var_dump()](https://secure.php.net/function.var_dump)**, **[print_r()](https://secure.php.net/function.print_r)** and **[debug_backtrace()](https://secure.php.net/function.debug_backtrace)**.

However, it's much, *much* more than that. You will eventually wonder how you developed without it.

## Installation

One of the main goals of Kint is to be **zero setup**.

[Download the file](https://raw.githubusercontent.com/kint-php/kint/master/build/kint.phar) and simply
```php
<?php

require 'kint.phar';
```

### Or, if you use Composer:

```bash
composer require kint-php/kint --dev
```

## Usage

```php
<?php

Kint::dump($GLOBALS, $_SERVER); // pass any number of parameters
d($GLOBALS, $_SERVER); // or simply use d() as a shorthand

Kint::trace(); // Debug backtrace

s($GLOBALS); // Basic output mode

~d($GLOBALS); // Text only output mode

Kint::$enabled_mode = false; // Disable kint
d('Get off my lawn!'); // Debugs no longer have any effect
```

## Tips & Tricks

* Kint is enabled by default, set `Kint::$enabled_mode = false;` to turn it completely off.  
  The best practice is to enable Kint in a development environment only - so even if you accidentally leave a dump in production, no one will know.
* See the buttons on the right of the output? Click them to open a new tab, show the access path for the value, or show a search box.
* There are a couple of real-time modifiers you can use:
    * `~d($var)` this call will output in plain text format.
    * `+d($var)` will disregard depth level limits and output everything.  
      *Careful, this can hang your browser on large objects!*
    * `!d($var)` will expand the output automatically.
    * `-d($var)` will attempt to `ob_clean` the previous output and flush after printing.
    * You can combine modifiers too: `~+d($var)`
* Double clicking the <kbd>+</kbd> sign will open/close it and all its children.
* Triple clicking the <kbd>+</kbd> sign in will open/close everything on the page.
* Add heavy classes to the blacklist to improve performance:  
  `Kint\Parser\BlacklistPlugin::$shallow_blacklist[] = 'Psr\Container\ContainerInterface';`
* To see the output in a docked toolbar at the bottom of the page:  
  `Kint\Renderer\RichRenderer::$folder = true;`
* To change display theme, use `Kint\Renderer\RichRenderer::$theme = 'theme.css';`. You can pass the absolute path to a CSS file, or use one of the built in themes:
    * `original.css` (default)
    * `solarized.css`
    * `solarized-dark.css`
    * `aante-light.css`
    * `aante-dark.css`
* Kint has *keyboard shortcuts*! When Kint is visible, press <kbd>D</kbd> on the keyboard and you will be able to traverse the tree with arrows, <kbd>H</kbd><kbd>J</kbd><kbd>K</kbd><kbd>L</kbd>, and <kbd>TAB</kbd> keys - and expand/collapse nodes with <kbd>SPACE</kbd> or <kbd>ENTER</kbd>.
* You can write plugins and wrapper functions to customize dump behavior!
* Read [the full documentation](https://kint-php.github.io/kint/) for more information

## Authors

[**Jonathan Vollebregt** (jnvsor)](https://github.com/jnvsor)  
[Contributors](https://github.com/kint-php/kint/graphs/contributors)

## License

Licensed under the MIT License
