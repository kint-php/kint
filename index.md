---
permalink: /
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#about">What is it?</a></li>
    <li><a href="#install">Installation</a></li>
    <li><a href="#use">Basic usage</a></li>
    <li><a href="#example">Example</a></li>
    <li><a href="{{ site.baseurl }}/advanced/">Advanced usage &raquo;</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Kint - a modern and powerful PHP debugging helper

<section id="about" markdown="1">

## What is it?

Kint for PHP is a tool designed to present your debugging data in the absolutely best way possible.

In other words, it's `var_dump()` and `debug_backtrace()` on steroids. Easy to use, but powerful and customizable. An essential addition to your development toolbox.

**Still lost?** You use it to see what's inside variables.

![]({{ site.baseurl }}/images/intro.png)

</section>
<section id="install" markdown="1">

## Installation

Is easy

### With composer

```
composer require kint-php/kint
```

<pre class="prettyprint linenums"><?php

include 'vendor/autoload.php';

d('Dumped with Kint');</pre>

### Just dropped in a folder somewhere

<pre class="prettyprint linenums"><?php

include 'kint.php';

d('Dumped with Kint');</pre>

</section>
<section id="use" markdown="1">

## Basic usage

<pre class="prettyprint linenums"><?php

Kint::dump($GLOBALS, $_SERVER); // Dump any number of variables
d($GLOBALS, $_SERVER); // d() is a shortcut for Kint::dump()

Kint::trace(); // Dump a debug backtrace
d(1); // Shortcut for Kint::trace()

Kint::$enabled_mode = false; // Disable kint
</pre>

</section>
<section id="example" markdown="1">

## An example

You have some data and you need to get more data out of it, but you don't know how the data is structured until you poke around inside it.

<pre class="prettyprint linenums"><?php

$time = get_mysterious_value();
$data = get_mysterious_data();

include 'kint.php';

d($time, $data);
</pre>

Let's take a look at this data with Kint

* Click anywhere on the bar to unfold it
* Double click `+` to unfold all children
* Triple click `+` to unfold all Kint dumps on the page
* Press `d` to toggle keyboard navigation.
* Press the "**⇄**" icon on the right to see what you'd need to run to get at a piece of data.
* Change tabs to see different views of data.
* You can sort tables of data by clicking on the headers.

<small>Github pages don't support PHP so this output was pre-recorded</small>

<div>{% include example_kint %}</div>

* Kint automatically detects, unpacks, and parses common formats like XML, base64, serialize, and JSON
* Detects common patterns like colors, filenames, tables, and timestamps and displays extra information about them
* Provides the user with the exact piece of code they need to access some information nested deep in the hierarchy

</section>

<h2><a href="{{ site.baseurl }}/advanced/">Advanced usage &raquo;</a></h2>

</div>
