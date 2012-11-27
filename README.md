# Kint - debugging helper for PHP developers

http://raveren.github.com/kint/

![Screenshot](http://raveren.github.com/kint/img/preview.png)

Kint for PHP is a powerful and modern, zero-setup replacement for **[var_dump()](http://php.net/manual/en/function.var-dump.php)**, **[print_r()](http://php.net/manual/en/function.print-r.php)** and **[debug_backtrace()](http://php.net/manual/en/function.debug-backtrace.php)**. You'll wonder how you developed without it.


----


## Installation and usage

    <?php
    require '/kint/Kint.class.php';

    Kint::dump($_SERVER);
    // or
    d($_SERVER);
    
    // for trace:
    Kint::trace();
    // or
    d( 1 );



[See the wiki](https://github.com/raveren/kint/wiki) for more documentation on configuration, credits and tips&tricks.

### Author

**Rokas Šleinius** (Raveren)

![](http://img199.yfrog.com/img199/4323/imageda.png)

