---
permalink: /
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#about">What is it?</a></li>
    <li><a href="#install">Installation</a></li>
    <li><a href="#use">Basic usage</a></li>
    <li><a href="#demo">Live demo</a></li>
    <li><a href="#tips">Tips &amp; Tricks</a></li>
    <li><a href="{{ site.baseurl }}/advanced/">Advanced usage &raquo;</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Kint - Advanced PHP dumper

<section id="about" markdown="1">

## What is it?

Kint is a dumper in the vein of `var_dump`, with keyboard controls, search, access path provision, and automatic data parsing.

![]({{ site.baseurl }}/images/intro.png)

</section>
<section id="install" markdown="1">

## Install with composer

```
composer require kint-php/kint
```

<pre class="prettyprint linenums"><?php

include 'vendor/autoload.php';

d('Dumped with Kint');</pre>

## Install a phar file

<pre class="prettyprint linenums"><?php

// Found in the build folder
include 'kint.phar';

d('Dumped with Kint');</pre>

</section>
<section id="use" markdown="1">

## Basic usage

<pre class="prettyprint linenums"><?php

Kint::dump($GLOBALS, $_SERVER); // Dump any number of variables
d($GLOBALS, $_SERVER); // d() is a shortcut for Kint::dump()

Kint::trace(); // Dump a backtrace

Kint::$enabled_mode = false; // Disable kint
</pre>

</section>
<section id="demo" markdown="1">

## Live demo

You have some data and you need to get more data out of it, but you don't know how the data is structured until you poke around inside it.

<pre class="prettyprint linenums"><?php

$time = get_mysterious_value();
$data = get_mysterious_data();
$object = get_mysterious_object();

include 'kint.phar';

d($time, $data, $object);
</pre>

<small>This output was pre-recorded</small>

<div>{% include example_kint %}</div>

Let's take a look at this data with Kint

* Click anywhere on the bar to unfold it
* Double click `+` to unfold all children
* Press `d` to toggle keyboard navigation.
* Press `a` or the "**⇄**" icon on the right to see the access path to get at a piece of data.
* Press `s` or the "**⌕**" icon on the right to open a live search.
* Press `f` or the "**↧**" icon on the right to move the dump to a docked folder.
* Change tabs to see different views of data.
* You can sort tables of data by clicking on the headers.

* Kint automatically detects, unpacks, and parses common formats like XML, base64, and JSON
* Detects common patterns like colors, filenames, tables, and timestamps and displays extra information about them
* Provides the user with the exact piece of code they need to access some information nested deep in the hierarchy

</section>
<section id="tips" markdown="1">

## Tips & Tricks

* You can set `Kint::$enabled_mode = false;` to turn off output (For example, in production)
* You can set nonces for CSP environments:
    * `Kint\Renderer\AbstractRenderer::$js_nonce`
    * `Kint\Renderer\AbstractRenderer::$css_nonce`
* There are a couple of real-time modifiers you can use:
    * `+d($var)` will disregard depth level limits and output everything  
      *Careful, this can hang your browser on extremely large objects!*
    * `!d($var)` will expand the output automatically
    * `~d($var)` this call will output in plain text format
    * `-d($var)` will attempt to `ob_clean` the previous output and flush after printing
* Add heavy classes to the blacklist to improve performance:  
    `Kint\Parser\BlacklistPlugin::$shallow_blacklist[] = SomeLargeClass::class;`
* To put dumps in a toolbar at the bottom of the page set `Kint\Renderer\RichRenderer::$folder = true;`
* To change display theme, use `Kint\Renderer\RichRenderer::$theme = 'theme.css';`. You can pass the absolute path to a CSS file, or use one of the built in themes:
    * `original.css` (default)
    * `solarized.css`
    * `solarized-dark.css`
    * `aante-light.css`
    * `aante-dark.css`
* You can install [kint-helpers](https://github.com/kint-php/kint-helpers) for more shortcuts and [kint-twig](https://github.com/kint-php/kint-twig) for twig integration

</section>

<h2><a href="{{ site.baseurl }}/advanced/">Advanced usage &raquo;</a></h2>

</div>
