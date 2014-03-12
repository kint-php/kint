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
```

**Or, if you use Composer:**

```json
    "require": {
       ...
       "raveren/kint": "v0.9"
    }
```

**That's it, you can now use Kint to debug your code:**

```php
<?php
########## DUMP VARIABLE ###########################
Kint::dump($GLOBALS, $_SERVER); // any number of parameters
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
* `@Kint::dump()` and `@d()` will return the output instead of displaying it.

----


[Visit the project page](http://raveren.github.com/kint/) for documentation, configuration, and more advanced usage examples.

### Author

**Rokas Šleinius** (Raveren)

![](http://img199.yfrog.com/img199/4323/imageda.png)



#### Contributing

I'd really appreciate it if you forked the wip branch when introducing changes. Currently it's located here:

https://github.com/raveren/kint/tree/1.0.0-wip

The stable `master` branch is usually behind and is updated rarely in big batches of tested functionality.


#### License

Licensed under the MIT License
