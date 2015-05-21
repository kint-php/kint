# Kint - debugging helper for PHP developers

[![Total Downloads](https://poser.pugx.org/raveren/kint/downloads.png)](https://packagist.org/packages/raveren/kint)

> **New version** v1.0.0 is released with more than two years of active development - changes are too numerous to list, but there's CLI output and literally hundreds of improvements and additions.

![Screenshot](http://raveren.github.com/kint/img/preview.png)

## What am I looking at?

At first glance Kint is just a pretty replacement for **[var_dump()](http://php.net/manual/en/function.var-dump.php)**, **[print_r()](http://php.net/manual/en/function.print-r.php)** and **[debug_backtrace()](http://php.net/manual/en/function.debug-backtrace.php)**. 

However, it's much, *much* more than that. Even the excellent `xdebug` var_dump improvements don't come close - you will eventually wonder how you developed without it. 

Just to list some of the most useful features:

 * You can **disable** all Kint output easily and on the fly - so you can even debug live systems (although you know you shouldn't be doing that!). Just call `Kint::enabled(false);` Or, for example `Kint::enabled($_SERVER['REMOTE_ADDR'] === '<your IP>');`. Now you can `Kint::dump($variables);` without fear (just don't create PHP Errors!).
 * The **variable name and place in code** where Kint was called from is displayed;
 * **CLI is detected** and formatted for automatically (but everything can be overridden on the fly) - if your setup supports it, the output is colored too:
 ![Kint CLI output](http://i.imgur.com/6B9MCLw.png)
 * Debug backtraces are finally fully readable, informative and a pleasure to the eye.
 * Kint has been in active development for around **seven years** and is shipped with [Drupal 8](https://www.drupal.org/) by default as part of its devel suite. You can trust it not being abandoned or getting left behind in features.
 * **Variable content is displayed in the most comfortable** way - and you *never, ever* miss anything! Kint guarantees you see every piece of physically available information about everything you are dumping*; 
 
 <sup>*(in some cases, the content is truncated where it would otherwise be too large to view anyway - but the user is always made aware of that);</sup>
 * Some variable content types have an alternative display - for example you will be able see `JSON` in its raw form - but also as an associative array:
![Kint displays data intelligently](http://i.imgur.com/9P57Ror.png)

----


## Installation and usage

One of the main goals of Kint is to be **zero setup**. 

Download the archive and simply
```php
<?php
require '/kint/Kint.class.php';
```

**Or, if you use Composer:**

```json
    "require": {
       "raveren/kint": "1.0.x-dev"
    }
```

Or just run `composer require raveren/kint`

**That's it, you can now use Kint to debug your code:**

```php
########## DUMP VARIABLE ###########################
Kint::dump($GLOBALS, $_SERVER); // any number of parameters

// or simply use d() as a shorthand:
d($_SERVER);


########## DEBUG BACKTRACE #########################
Kint::trace();
// or via shorthand:
d(1);


############# BASIC OUTPUT #########################
s($GLOBALS);


######### WHITESPACE FORMATTED OUTPUT ##############
~d($GLOBALS); // just prepend with the tilde


########## MISCELLANEOUS ###########################
// to disable all output
Kint::enabled(false);
// further calls, this one included, will not yield any output
d('Get off my lawn!'); // no effect

```

Note, that Kint *does* have configuration, but it's in need of being rewritten, so I'm not documenting it yet.

### Furthermore

* `sd()` and `ddd()` are shorthands for `s();die;` and `d();die;` respectively. **Important:** The older shorthand `dd()` is deprecated due to compatibility issues and will be eventually removed. Please use the analogous `ddd()` instead.
* `!Kint::dump()` and `!ddd()` will display the dump expanded by default.
* When looking at Kint output, press <kbd>D</kbd> on the keyboard and you will be able to traverse the tree with arrows and tab keys - and expand/collapse nodes with space or enter.
* Double clicking the `[+]` sign in the output will expand/collapse ALL nodes; triple clicking big blocks of text will select it all.
* Clicking the tiny arrows on the right of the output open it in a separate window where you can keep it for comparison.
* To catch output from Kint just assign it to a variable <sub>beta</sub>:
```php
$o = Kint::dump($GLOBALS); // yes, the assignment is automatically detected, and $o now holds the output string
```
* To force a specific dump output type just pass it to the `Kint::enabled()` method. Available options are: `Kint::MODE_RICH` (default), `Kint::MODE_PLAIN`, `Kint::MODE_WHITESPACE` and `Kint::MODE_CLI`:
```php
Kint::enabled(Kint::MODE_WHITESPACE);
$kintOutput = Kint::dump($GLOBALS); // now $kintOutput can be placed into a text log file and be perfectly readable
```

----

[Visit the project page](http://raveren.github.com/kint/) for documentation, configuration, and more advanced usage examples.

### Author

**Rokas Šleinius** (Raveren)

### License

Licensed under the MIT License
