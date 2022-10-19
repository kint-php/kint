---
permalink: /advanced/
title: Advanced usage
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#modifiers">Modifiers</a></li>
    <li><a href="#modes">Render modes</a></li>
    <li><a href="#twig">Twig integration</a></li>
    <li><a href="#plugins">Plugins</a></li>
    <li><a href="#helperfuncs">Helper functions</a></li>
    <li><a href="{{ site.baseurl }}/settings/">Kint settings reference &raquo;</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Advanced usage

<section id="modifiers" markdown="1">

## Modifiers

Modifiers are a way to change Kint output without having to use a different function. Simply prefix your call to kint with a modifier to apply it:

`!` | Expand all data in this dump automatically
`+` | Disable the depth limit in this dump
`-` | Clear buffered output and flush after dump
`@` | Return the output of this dump instead of echoing it
`~` | Dump in plain text

Example:

<pre class="prettyprint">
+Kint::dump($data); // Disabled depth limit
!d($data); // Expanded automatically
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
<section id="twig" markdown="1">

## Twig Integration

An official kint twig extension is provided in our <a href="https://github.com/kint-php/kint-twig/" target="_blank">kint-twig repository</a>.

```
composer require kint-php/kint-twig
```

<pre class="prettyprint linenums"><?php

$twig->addExtension(new Kint\Twig\TwigExtension());
</pre>

</section>

<section id="plugins" markdown="1">

## Plugins

Plugins will transform input data to make it more useful to you. For example, the `JsonPlugin` will let you expand json strings without having to decode it yourself.

To enable plugins for the main Kint facade add the classname or instance to the `Kint::$plugins` array.

All parser plugins delivered with Kint are in the `Kint\Parser` namespace.

### Default plugins

These plugins are on in a default Kint installation.

* `ArrayLimitPlugin`  
  Limits output of long arrays.
* `ArrayObjectPlugin`  
  Ensures `ArrayObject` is parsed with the correct mode, and set back afterwards.
* `Base64Plugin`  
  Decodes base64 encoded strings.
* `BlacklistPlugin`  
  Lets you hide information you don't want dumped, and improve performance by skipping heavy values.
* `ClassMethodsPlugin`  
  Shows methods available for an object.
* `ClassStaticsPlugin`  
  Shows statics and constants for an object.
* `ClosurePlugin`  
  Shows properties and uses for a `Closure`.
* `ColorPlugin`  
  Detects a color string, then shows a color swatch and different ways to write the color.
* `DateTimePlugin`  
  Shows the formatted datetime inline, before unfolding.
* `EnumPlugin`  
  Shows PHP 8.1 enums.
* `FsPathPlugin`  
  Detects server file paths and shows information about them. See also `SplFileInfoPlugin`.
* `IteratorPlugin`  
  Shows the contents of an `Iterator`.
* `JsonPlugin`  
  Decodes JSON encoded strings.
* `MicrotimePlugin`  
  Automatically shows memory and timing information when you dump `microtime()`.  Call `MicrotimePlugin::reset()` to reset the counters.  
  ![]({{ site.baseurl }}/images/plugin-microtime.png)
* `SimpleXMLElementPlugin`  
  Displays `SimpleXMLElements` with tabs for children and attributes.
* `SplFileInfoPlugin`  
  Shows information about the file.
* `SplObjectStoragePlugin`  
  Shows the `SplObjectStorage` contents.
* `StreamPlugin`  
  Shows stream metadata for stream resources.
* `TablePlugin`  
  Shows table-like multidimensional arrays as a table.
* `ThrowablePlugin`  
  Shows the exception or error message inline, with the place in the source where it was thrown.
* `TimestampPlugin`  
  Detects integers that seem like timestamps (Between 9 and 10 digits) and formats the time.
* `TracePlugin`  
  Detects a backtrace, and gathers information like stack frame source code snippets.
* `XmlPlugin`  
  Detects an XML string and loads it into `SimpleXML` or `DOMDocument`.

### Opt-in plugins

* `BinaryPlugin`  
  Displays non-utf8 strings like `xxd`  
  ![]({{ site.baseurl }}/images/plugin-binary.png)
* `DOMDocumentPlugin`  
  Add support for `DOMDocument` object parsing.
* `MysqliPlugin`  
  Adds support for `Mysqli` object parsing. Due to the way it's implemented in PHP, this will cause warnings on certain `Mysqli` objects if [screaming](https://secure.php.net/book.scream) is enabled.
* `ProxyPlugin`  
    Mostly used for tests, this is also useful if you don't want to make your own plugin class. It takes the array of types and bitmask of triggers the plugin should apply to, as well as a callback that should be run. Here's an example of using `ProxyPlugin` to replicate the `ToString` plugin:  

    <pre class="prettyprint linenums"><?php

    use Kint\Zval\Value;
    use Kint\Zval\Representation\Representation;
    use Kint\Parser\Parser;
    use Kint\Parser\ProxyPlugin;

    $plugin = new ProxyPlugin(
        ['object'],
        Parser::TRIGGER_SUCCESS,
        function (&$var, Value &$o, $trigger, Parser $parser) {
            $reflection = new ReflectionClass($var);
            if (!$reflection->hasMethod('__toString')) {
                return;
            }

            $r = new Representation('toString');
            $r->contents = (string) $var;

            $o->addRepresentation($r);
        }
    );

    $parser->addPlugin($plugin);
    </pre>
* `SerializePlugin`  
  Decodes serialized strings. Serialization has been a security pain forever, so it's disabled by default.
* `ToStringPlugin`  
  Shows the string representation of an object with a `__toString()` method. Some poorly-behaved libraries will cause fatal errors when this method is called, so it has sadly been moved to opt-in.

### Custom plugins

You can write your own plugins for Kint too. Check out the the <a href="{{ site.baseurl }}/writing-plugins/">plugin guide</a>.

</section>
<section id="helperfuncs" markdown="1">

## Helper functions

Sometimes you want to change Kint behavior without using a plugin, or just add a new function name for Kint. You can do that by making a new helper function.

A set of useful kint helpers is provided in our <a href="https://github.com/kint-php/kint-helpers/" target="_blank">kint-helpers repository</a>. You can install it with composer or just include the `init.php` file after Kint has loaded.

```
composer require kint-php/kint-helpers
```

---

You can also write your own helper functions for simple changes in functionality. For example, if you wanted to call Kint from `dump()` you could do this:

<pre class="prettyprint linenums"><?php

// Some Kint features (Variable names, modifiers, mini trace) only work if Kint
// knows where it was called from. But Kint can't know that if it doesn't know
// what the helper function is called. Add your functions to `Kint::$aliases`.
Kint::$aliases[] = 'dump';

function dump(...$vars)
{
    return Kint::dump(...$vars);
}
</pre>

### Disabling helper functions in composer

Kint won't define the `d()` and `s()` helper functions if they already exist, but when using composer you may sometimes want to disable them ahead of time.

By adding an `extra.kint.disable-helpers` key to your `composer.json`, Kint will skip defining the helper functions. You can use this in your root `composer.json`, or any package installed alongside Kint, and it should work.

<pre class="prettyprint linenums">
{
    "require-dev": {
        "kint-php/kint": "^5"
    },
    "extra": {
        "kint": {
            "disable-helpers": true
        }
    }
}
</pre>

You can also define `KINT_SKIP_HELPERS` as `true` for the same effect, which is helpful if you're using the phar file, but this needs to be set before the autoloader begins.

<pre class="prettyprint linenums"><?php

define('KINT_SKIP_HELPERS', true);

include 'vendor/autoload.php';
</pre>

</section>

<h2><a href="{{ site.baseurl }}/settings/">Kint settings reference &raquo;</a></h2>

</div>
