# Kint - debugging helper for PHP developers

http://raveren.github.com/kint/

![Screenshot](http://raveren.github.com/kint/img/preview.png)

Kint for PHP is a powerful and modern, zero-setup replacement for **[var_dump()](http://php.net/manual/en/function.var-dump.php)**, **[print_r()](http://php.net/manual/en/function.print-r.php)** and **[debug_backtrace()](http://php.net/manual/en/function.debug-backtrace.php)**. You'll wonder how you developed without it.


----


## Installation and usage
```php
<?php
require '/kint/Kint.class.php';

Kint::dump($_SERVER);
// or
d($_SERVER);

// for trace:
Kint::trace();
// or
d( 1 );

// to disable all output
Kint::enabled(false);
// further calls, this one included, will not yield any output
d('Get off my lawn!'); // no effect
```

Calling `dd($variable);` will seize further processing - same as `d($variable);die;`. You can also get a **S**implified space-formatted output via function `s()`.

`sd( new ZipArchive );` will output to screen:


    object ZipArchive (5) {
        public status -> integer 0
        public statusSys -> integer 0
        public numFiles -> integer 0
        public filename -> string (0) ""
        public comment -> string (0) ""
    }




[Visit the project page](http://raveren.github.com/kint/) for documentation, configuration, and more examples.

### Author

**Rokas Šleinius** (Raveren)

![](http://img199.yfrog.com/img199/4323/imageda.png)

