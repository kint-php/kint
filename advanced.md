---
permalink: /advanced/
title: Advanced usage
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#modes">Render modes</a></li>
    <li><a href="#modifiers">Modifiers</a></li>
    <li><a href="#plugins">Useful plugins</a></li>
    <li><a href="#helperfuncs">Making helper functions</a></li>
    <li><a href="#settings">Kint settings</a></li>
    <li><a href="#rendersettings">Render mode settings</a></li>
    <li><a href="{{ site.baseurl }}/contrib/">Improving Kint &raquo;</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Advanced usage

There will be more options and more information in source code docstrings - but here's a rundown of the interesting stuff.

<section id="modes" markdown="1">

## Render modes

Kint has 4 render modes available by default:

### Rich

This is the one you saw on the previous page. It outputs the most data possible in an easy to read way.

Use this output mode with the `d()` helper function.

### Text

The text mode is similar to var_dump - it outputs raw text with no formatting for the web. It also doesn't display extra parsed data and only the raw values of the things you give it.

Use this output mode with the `~` modifier: `~d()`.

### Plain

This is basically the text mode but with html escaping and color highlighting. It still only shows basic information but it does it in a way that's easy to read in a browser.

Use this output mode with the `s()` helper function.

### CLI

This is basically the text mode but with bash color highlighting and automated terminal width detection. This will be automatically chosen by both `d()` and `s()` if you run your script from a terminal.

</section>
<section id="modifiers" markdown="1">

## Modifiers

Modifiers are a way to change Kint output without having to use a different helper function. Simply prefix your call to kint with a modifier to apply it:

* `!`: Expand all data in this dump automatically
* `+`: Disable the depth limit in this dump
* `-`: Attempt to clear any buffered output before this dump
* `@`: Return the output of this dump instead of echoing it
* `~`: Use the text renderer for this dump

Example:

<pre class="prettyprint">
+Kint::dump($data); // Disabled depth limit
!d($data); // Expanded automatically
</pre>

</section>
<section id="plugins" markdown="1">

## Useful Plugins

Most plugins shipped with Kint by default are just there to gather data. Whether it shows you an object's methods or parses JSON automatically, these are things you'll notice as soon as you see them.

For example the Binary plugin will take any binary data and display it as a hex dump:

![]({{ site.baseurl }}/images/plugin-binary.png)

There are a few plugins that will make a big difference if you pay attention to them though...

### The blacklist plugin

lets you hide information you don't want dumped, and improve performance by skipping heavy objects.

* Add a class to `Kint_Parser_Blacklist::$blacklist` and it will be blacklisted entirely.
* Add a class to `Kint_Parser_Blacklist::$shallow_blacklist` it will be blacklisted unless you dump it specifically. This is great for heavy objects that impact performance.

### The Iterator plugin

shows you the contents of any iterator. This can be a problem when the iterators are single-use. For example, iterating over a `PDOStatement` will discard all of the results. Lucky for you, `Kint_Parser_Iterator::$blacklist` has `PDOStatement` by default!

### The microtime plugin

automatically shows memory information when you use it. Better still, it will show you how long since the last call, and how it compares to the average. Call `Kint_Parser_Microtime::clean()` to reset the counters.

![]({{ site.baseurl }}/images/plugin-microtime.png)

</section>
<section id="helperfuncs" markdown="1">

## Making helper functions

Sometimes you want to change general kint behavior without using a plugin. You can do that by making a new helper function.

In this example we're going to make a helper function that only takes one parameter, but returns it afterwards.

<pre class="prettyprint linenums"><?php

// Some Kint features (Variable names, modifiers) only work if Kint knows where
// it was called from. But Kint can't know that if it doesn't know what the helper
// function is called. Add the function to `Kint::$aliases` to make it work.
Kint::$aliases[] = 'dr';

function dr($var)
{
    Kint::dump($var);
    return $var;
}
</pre>

### Disabling helper functions in composer

Kint won't define the `d()` and `s()` helper functions if they already exist, but when using composer you may sometimes want to disable them explicitly.

By adding an `extra.kint.disable-helper-functions` key to your `composer.json`, Kint will skip defining the helper functions. You can use this in your root `composer.json`, or any package installed alongside Kint, and it should work.

<pre class="prettyprint linenums">
{
    "require-dev": {
        "kint-php/kint": "^2.0"
    },
    "extra": {
        "kint": {
            "disable-helper-functions": true
        }
    }
}
</pre>

</section>
<section id="settings" markdown="1">

## Kint settings

* `Kint::$enabled_mode`:
    * `false` to disable Kint entirely
    * a specific render mode from `Kint::$renderers`
    * `true` to pick a render mode automatically between normal and CLI mode

    `true` (Automatic) by default

    You should disable kint in production, or only enable it per `$_SERVER['REMOTE_ADDR']`

* `Kint::$plugins`: A list of parser plugins to apply before parsing
    * Full class names of classes extending `Kint_Parser_Plugin`
    * Instantiated child classes of `Kint_Parser_Plugin`

* `Kint_Object_Blob::$char_encodings`: A list of character encodings to try to identify to be passed to `mb_detect_encoding`

    It's impossible to detect every encoding, and this option may have a severe performance impact if you make it too large.

    The default is black magic from ages past that scares me too much to change it.

* `Kint::$app_root_dirs`: A map of directories to aliases to shorten paths

    Default aliases your `$_SERVER['DOCUMENT_ROOT']` to `'<ROOT>'`

* `Kint::$file_link_format`: A format to link source code paths to

    `ini_get('xdebug.file_link_format')` by default

* `Kint::$display_called_from`: Whether it will display the mini trace

* `Kint::$max_depth`: How deep Kint will try to go when parsing data

    `0` to disable the depth limit. `7` by default

* `Kint::$return`: Whether Kint will return its output or echo it

* `Kint::$expanded`: Whether Kint will automatically expand it's children when possible

</section>
<section id="rendersettings" markdown="1">

## Render mode settings

* `Kint_Renderer_Rich::$strlen_max`: The maximum length of text to show in the bar before unfolding

    `0` to disable, `80` by default

* `Kint_Renderer_Rich::$theme`: Which theme to use. One of:
    * One of the CSS files found in `KINT_DIR/resources/compiled/`
    * The full path to your own CSS file

    **Note:** This is disabled in the single-file distribution of Kint as it would mean duplicating the stylesheets.

* `Kint_Renderer_Text::$strlen_max`: The maximum length of text

    `0` to disable. Since the text renderer doesn't have any unfolding it's `0` by default

* `Kint_Renderer_Text::$default_width`: The width of headers

* `Kint_Renderer_Text::$default_indent`: The indentation level

* `Kint_Renderer_Text::$decorations`: Whether to decorate the header and footer

* `Kint_Renderer_Cli::$cli_colors`: Whether to print color codes

* `Kint_Renderer_Cli::$detect_width`: Whether to detect terminal width automatically

    Only supported on unix systems (Depends on `tput`)

* `Kint_Renderer_Plain::$disable_utf8`: If <a href="http://utf8everywhere.org/" target="_blank">your web page isn't in utf-8</a>, set this to `true` just to be safe

</section>

<h2><a href="{{ site.baseurl }}/contrib/">Improving Kint &raquo;</a></h2>

</div>
