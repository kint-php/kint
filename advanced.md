---
permalink: /advanced/
title: Advanced usage
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#modifiers">Modifiers</a></li>
    <li><a href="#helperfuncs">Helper functions</a></li>
    <li><a href="#modes">Render modes</a></li>
    <li><a href="#integrations">Integrations</a></li>
    <li><a href="{{ site.baseurl }}/plugins/">Kint plugins &raquo;</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Advanced usage

<section id="modifiers" markdown="1">

## Modifiers

Modifiers are a way to change Kint output without having to use a different function. Simply prefix your call to kint with a modifier to apply it:

`!` | Expand all data in this dump automatically
`+` | Disable the depth limit in this dump
`-` | Attempt to clear any buffered output before this dump
`@` | Return the output of this dump instead of echoing it
`~` | Use the text renderer for this dump

Example:

<pre class="prettyprint">
+Kint::dump($data); // Disabled depth limit
!d($data); // Expanded automatically
</pre>

</section>
<section id="helperfuncs" markdown="1">

## Helper functions

Sometimes you want to change Kint behavior without using a plugin, or just add a new function name for Kint. You can do that by making a new helper function.

In this example we're going to make a helper function that only takes one parameter, but returns it afterwards.

<pre class="prettyprint linenums"><?php

// Some Kint features (Variable names, modifiers, mini trace) only work if Kint
// knows where it was called from. But Kint can't know that if it doesn't know
// what the helper function is called. Add your functions to `Kint::$aliases`.
Kint::$aliases[] = 'dr';

function dr($var)
{
    Kint::dump($var);
    return $var;
}
</pre>

### Disabling helper functions in composer

Kint won't define the `d()` and `s()` helper functions if they already exist, but when using composer you may sometimes want to disable them ahead of time.

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

This is basically the text mode but with bash color highlighting and automated terminal width detection. This will be automatically chosen if you run your script from a terminal.

</section>
<section id="integrations" markdown="1">

## Integrations

Kint is integrated into some popular systems. <a href="https://github.com/kint-php/kint/issues/new" target="_blank">Add an issue</a> if you've built an integration you would like added to this list.

### Twig

An official kint-php twig extension is provided in our <a href="https://github.com/kint-php/kint-twig/" target="_blank">kint-twig repository</a>.

```
composer require kint-php/kint-twig
```

<pre class="prettyprint linenums">
<?php

$twig->addExtension(new Kint_TwigExtension());
</pre>

</section>

<h2><a href="{{ site.baseurl }}/plugins/">Kint plugins &raquo;</a></h2>

</div>
