# Kint - debugging helper for PHP developers

[![Total Downloads](https://poser.pugx.org/raveren/kint/downloads.png)](https://packagist.org/packages/raveren/kint)

http://raveren.github.com/kint/

![Screenshot](http://raveren.github.com/kint/img/preview.png)

Kint for PHP is a powerful and modern, zero-setup replacement for **[var_dump()](http://php.net/manual/en/function.var-dump.php)**, **[print_r()](http://php.net/manual/en/function.print-r.php)** and **[debug_backtrace()](http://php.net/manual/en/function.debug-backtrace.php)**. You'll wonder how you developed without it.


----


## Installation and usage
```php
<?php
require '/kint/Kint.class.php';

########## DUMP VARIABLE ###########################
Kint::dump($GLOBALS, $_SERVER); // any nuber of parameters
// or simply use d() as a shorthand:
d($_SERVER);


########## DEBUG BACKTRACE #########################
Kint::trace();
// or via shorthand:
d(1);


########## TEXT-ONLY OUTPUT ########################
s($GLOBALS);


########## MISCELLANEOUS ###########################
// to disable all output
Kint::enabled(false);
// further calls, this one included, will not yield any output
d('Get off my lawn!'); // no effect

```

### Furthermore

* `sd()` and `dd()` are shorthands for `s();die;` and `d();die;` respectively.
* `!Kint::dump()` and `!dd()` will display the dump expanded by default.

----


[Visit the project page](http://raveren.github.com/kint/) for documentation, configuration, and more advanced usage examples.

### Author

**Rokas Šleinius** (Raveren)

![](http://img199.yfrog.com/img199/4323/imageda.png)

### License

Licensed under the MIT License