# Kint - debugging helper for PHP developers

Kint for PHP is a **zero-setup replacement** for **[var_dump()](http://php.net/manual/en/function.var-dump.php)**, **[print_r()](http://php.net/manual/en/function.print-r.php)** and **[debug_backtrace()](http://php.net/manual/en/function.debug-backtrace.php)**. Extremely easy to use but powerful and customizable.

Kint is an ideological successor _(and a superior alternative)_ to Krumo, which you may know as "**Var_dump 2.0**".

----

## Main advantages over [var_dump()](http://php.net/manual/en/function.var-dump.php) and/or [print_r()](http://php.net/manual/en/function.print-r.php)
  * Much more elegant and readable output - structured, collapsible and escaped.
  * The **name of the variable** is displayed.
  * The place (file, line and method) of where the output was called from is displayed.
  * Accepts any number of parameters in one call and groups them for you to see what was dumped in different iterations. Furthermore, calls from different places in the source are color-coded so you can see loops and repeated calls easily.
  * Handles recursive variables.
  * Much more information is displayed about the variable in many cases:
    - static properties of the dumped objects class;
    - specific types of data are recognized and displayed in a custom way (eg. JSON, XML strings);
    - if a resource variable is of an opened file, the file name is displayed and much more...
  * Complex variables are dumped with a fixed nested depth so that it doesn't hang up your browser for enormous objects. This can be turned off on the fly - read on.
  * Kint can display *or return* its output, the output itself is formatted via HTML5/CSS3 or whitespace in plain text.
  * Ability to dynamically turn off any output so you can debug live systems without users knowing.
  * Modifiers to alter functionality on the fly *(100% unique feature)* - see below.
  * Much shorter name - `d($var)` (it's an alias for `Kint::dump()`). See below for complete usage.

## Installation and usage

    <?php
    require '/kint/Kint.class.php';

    Kint::dump($_SERVER);
    // or
    d($_GOBALS);
    // for trace:
    Kint::trace();



[See the wiki](https://github.com/raveren/kint/wiki) for more documentation on configuration, credits and tips&tricks.

## Version history

For full changelog [see here](https://github.com/raveren/kint/wiki/Changelog).

**v1.0alpha3 2012-07-24**
  * Stay tuned for a detailed changelog in v1 final

**v1.0alpha2 2012-06-10**
  * Ongoing changes

**v1.0alpha1 2012-05-17**
  * Heavily refactored most of the code.
  * Tabular data is now optionally showed in a table layout.
  * Custom data parsers are supported, JSON, XML and SplFileInfo are included in this version, more will follow.
  * When dumping `get_defined_vars()` results are displayed as separate variables (which they are), not an array. Same functionality can be achieved if dumping a variable named `$_kint_flatten`.
  * Migrated to gitHub

***

### Notes

This tool is used heavily in real life development by the developer and constantly improved, just not committed as often. Issues and feature requests are [very welcome](https://github.com/raveren/kint/issues) and will be fixed/implemented as soon as possible.

Avoid using this tool in non-development servers, I cannot be held responsible for the performance, security and stability issues Kint may cause. No license attached: use however you wish, I'd just be grateful for a backlink and feedback is always appreciated.

For integration plugins to frameworks and other systems [see here](https://github.com/raveren/kint/wiki/Kint-used-elsewhere).

### Credits

Design by **Mindaugas Stankaitis**

File path features by <a href="https://github.com/tgr">Tisza Gergő</a>

### Author

**Rokas Šleinius** (<a href="http://careers.stackoverflow.com/rokas-sleinius" title="Rokas Šleinius CV">CV</a>)

![](http://img199.yfrog.com/img199/4323/imageda.png)

