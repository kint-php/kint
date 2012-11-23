# Kint - debugging helper for PHP developers

## For documentation and usages please refer to http://raveren.github.com/kint/

Kint for PHP is a **zero-setup replacement** for **[var_dump()](http://php.net/manual/en/function.var-dump.php)**, **[print_r()](http://php.net/manual/en/function.print-r.php)** and **[debug_backtrace()](http://php.net/manual/en/function.debug-backtrace.php)**. Extremely easy to use but powerful and customizable.

Kint is an ideological successor _(and a superior alternative)_ to Krumo, which you may know as **Var_dump 2.0**.

----


## Installation and usage

    <?php
    require '/kint/Kint.class.php';

    Kint::dump($_SERVER);
    // or
    d($_GOBALS);
    // for trace:
    Kint::trace();
    // or
    d( 1 );



[See the wiki](https://github.com/raveren/kint/wiki) for more documentation on configuration, credits and tips&tricks.

### Author

**Rokas Šleinius** (<a href="http://careers.stackoverflow.com/rokas-sleinius" title="Rokas Šleinius CV">CV</a>)

![](http://img199.yfrog.com/img199/4323/imageda.png)

