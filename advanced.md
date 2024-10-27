---
permalink: /advanced/
title: Advanced usage
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#modes">Render modes</a></li>
    <li><a href="#plugins">Plugins</a></li>
    <li><a href="#helperfuncs">Helper functions</a></li>
    <li><a href="{{ site.baseurl }}/settings/">Kint settings reference &raquo;</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Advanced usage

<section id="modes" markdown="1">

## Render modes

Kint has 4 render modes available by default:

### Rich

This is the one you saw on the previous page. It outputs the most data possible in an easy to read way.

Use this output mode with the `d()` helper function.

### Text

The text mode is similar to `var_dump` - it outputs raw text with no formatting for the web. It also doesn't display much extra parsed data and only the raw values of the things you give it.

Use this output mode with the `~` modifier: `~d()`.

### Plain

This is basically the text mode but with html escaping and color highlighting. It still only shows basic information but it does it in a way that's easy to read in a browser.

Use this output mode with the `s()` helper function.

### CLI

This is basically the text mode but with bash color highlighting and automated terminal width detection. This will be automatically chosen if you run your script from a terminal.

</section>
<section id="plugins" markdown="1">

## Plugins

Plugins will transform input data to make it more useful to you. For example, the `JsonPlugin` will let you expand json strings without having to decode it yourself.

To add a plugin add the classname to the `Kint::$plugins` array if it implements `ConstructablePluginInterface`, or the instance if it implements `PluginInterface`.

All parser plugins delivered with Kint are in the `Kint\Parser` namespace.

### Default plugins

These plugins are on in a default Kint installation.

* `ArrayLimitPlugin`  
  Limits output of long arrays.
* `ArrayObjectPlugin`  
  Ensures `ArrayObject` is parsed with the correct mode, and set back afterwards.
* `Base64Plugin`  
  Decodes base64 encoded strings.
* `BinaryPlugin`  
  Displays strings without detected encoding like `xxd`  
  ![]({{ site.baseurl }}/images/plugin-binary.png)
* `BlacklistPlugin`  
  Lets you hide information you don't want dumped, and improve performance by skipping heavy values.
* `ClassHooksPlugin`  
  Shows hook methods for an objects properties.
* `ClassMethodsPlugin`  
  Shows methods available for an object.
* `ClassStaticsPlugin`  
  Shows statics and constants for an object.
* `ClassStringsPlugin`  
  Shows methods, statics, and constants for a class when the top level dump is a class name string.
* `ClosurePlugin`  
  Shows properties and uses for a `Closure`.
* `ColorPlugin`  
  Detects a color string, then shows a color swatch and different ways to write the color.
* `DateTimePlugin`  
  Shows the formatted datetime inline, before unfolding.
* `DOMDocumentPlugin`  
  Add support for `DOMDocument` object parsing.
* `DomPlugin`  
  Add support for `Dom` object parsing.
* `EnumPlugin`  
  Add support for enums.
* `FsPathPlugin`  
  Detects server file paths and shows information about them. See also `SplFileInfoPlugin`.
* `HtmlPlugin`  
  Detects an HTML string and loads it into `DOM\HTMLDocument`.
* `IteratorPlugin`  
  Shows the contents of an `Iterator`.
* `JsonPlugin`  
  Decodes JSON encoded strings.
* `MicrotimePlugin`  
  Automatically shows memory and timing information when you dump `microtime()`.  Call `MicrotimePlugin::clean()` to reset the counters.  
  ![]({{ site.baseurl }}/images/plugin-microtime.png)
* `MysqliPlugin`  
  Adds support for `Mysqli` object parsing.
* `SimpleXMLElementPlugin`  
  Adds support for `SimpleXMLElements` object parsing.
* `SplFileInfoPlugin`  
  Shows information about the file.
* `StreamPlugin`  
  Shows stream metadata for stream resources.
* `TablePlugin`  
  Shows table-like multidimensional arrays as a table.
* `ThrowablePlugin`  
  Shows the exception or error message inline, with the place in the source where it was thrown.
* `TimestampPlugin`  
  Detects integers that seem like timestamps (Between 9 and 10 digits) and formats the time.
* `ToStringPlugin`  
  Shows the string representation of an object with a `__toString()` method.
* `TracePlugin`  
  Detects a backtrace, and gathers information like stack frame source code snippets.
* `XmlPlugin`  
  Detects an XML string and loads it into `SimpleXML`, `DOMDocument`, or `DOM\XMLDocument`.

### Opt-in plugins

* `SerializePlugin`  
  Decodes serialized strings. Serialization has been a security pain forever, so it's disabled by default.
* `ProfilePlugin`  
  Profiles your dump to identify classes, interfaces, instances, or properties causing performance issues.
* `ProxyPlugin`  
    This takes the array of types and bitmask of triggers the plugin should apply to, as well as a callback that should be run. The second argument is either a `ContextInterface` or an `AbstractValue` depending on the trigger.

    This is mostly useful if you don't want to make your own plugin class. Here's an example of using `ProxyPlugin` to make a simple `ToString` plugin:  

    <pre class="prettyprint linenums"><?php

    use Kint\Value\AbstractValue;
    use Kint\Value\Representation\StringRepresentation;
    use Kint\Parser\Parser;
    use Kint\Parser\ProxyPlugin;
    use Throwable;

    $plugin = new ProxyPlugin(
        ['object'],
        Parser::TRIGGER_SUCCESS,
        function (&$var, AbstractValue $v, int $trigger, Parser $parser): AbstractValue {
            $reflection = new ReflectionClass($var);
            if (!$reflection->hasMethod('__toString')) {
                return $v;
            }

            try {
                $string = (string) $var;
            } catch (Throwable $t) {
                return $v;
            }

            $r = new StringRepresentation('toString', $string);
            $v->addRepresentation($r);

            return $v;
        }
    );

    $parser->addPlugin($plugin);
    </pre>

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
        "kint-php/kint": "^6"
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
