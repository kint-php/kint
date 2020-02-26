---
permalink: /plugins/
title: Plugins
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#notable">Notable plugins</a></li>
    <li><a href="#opt-in">Opt-in plugins</a></li>
    <li><a href="#rest">Default plugins</a></li>
    <li><a href="#text">Text mode plugins</a></li>
    <li><a href="{{ site.baseurl }}/settings/">Kint settings &raquo;</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Plugins

Kint ships with a ton of plugins for built-in PHP functionality. Most of these are for data retrieval -- gathering extra information about a variable or displaying it in a different way.

You can enable or disable plugins in the `Kint::$plugins` array.

<section id="notable" markdown="1">

## Notable plugins

### The blacklist plugin

Lets you hide information you don't want dumped, and improve performance by skipping heavy objects.

`Kint\Parser\BlacklistPlugin::$blacklist` | List of classes to blacklist.
`Kint\Parser\BlacklistPlugin::$shallow_blacklist` | List of classes to blacklist unless you dump it specifically. This is great for heavy objects that impact performance like DI containers.
`Kint\Parser\BlacklistPlugin::$array_limit` | Size of arrays to blacklist. Default 1000
`Kint\Parser\BlacklistPlugin::$shallow_array_limit` | Size of arrays to blacklist unless you dump it specifically. Default 10000

### The microtime plugin

Automatically shows memory and timing information when you dump `microtime()`. Call `Kint\Parser\MicrotimePlugin::clean()` to reset the counters.

![]({{ site.baseurl }}/images/plugin-microtime.png)

### The proxy plugin

Mostly used for tests, this is also useful if you don't want to make your own plugin class. It takes the array of types and bitmask of triggers the plugin should apply to, as well as a callback that should be run. Here's an example of using ProxyPlugin to replicate the ToString plugin:

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

</section>
<section id="opt-in" markdown="1">

## Opt-in plugins

These plugins are off by default.

### Kint\Parser\BinaryPlugin

Displays non-utf8 strings like `xxd`

![]({{ site.baseurl }}/images/plugin-binary.png)

`Kint\Renderer\Rich\BinaryPlugin::$line_length` | The amount of bytes per line of output. Default `0x10`
`Kint\Renderer\Rich\BinaryPlugin::$chunk_length` | The amount of bytes per chunk of output. Default `0x4`

### Kint\Parser\DOMDocumentPlugin

Add support for DOMDocument object parsing.

`Kint\Parser\DOMDocumentPlugin::$blacklist` | DOMNode properties to skip parsing. This is here because the DOMDocument system can introduce a huge amount of recursion.
`Kint\Parser\DOMDocumentPlugin::$verbose` | Show methods and properties. Default false.

### Kint\Parser\MysqliPlugin

Adds support for Mysqli object parsing. Due to the way mysqli is implemented in PHP, this will cause warnings on certain `Mysqli` objects if [screaming](https://secure.php.net/book.scream) is enabled.

### Kint\Parser\SerializePlugin

Decodes serialized strings.

Because deserializing objects can pose a security risk, the following options are available.

`Kint\Parser\SerializePlugin::$safe_mode` | Don't allow deserialization of arrays or objects. Default true.
`Kint\Parser\SerializePlugin::$options` | Options to pass to <a href="http://php.net/manual/en/function.unserialize.php#refsect1-function.unserialize-parameters" target="_blank">unserialize</a>. Only for PHP7.

### Kint\Parser\ToStringPlugin

Shows the string representation of an object with a `__toString()` method. Some poorly-behaved libraries will cause fatal errors when this method is caused, so it has sadly been moved to optional.

`Kint\Parser\ToStringPlugin::$blacklist` | Objects to ignore. Default includes `SimpleXMLElement`, `SplFileObject`

</section>
<section id="rest" markdown="1">

## Default plugins

These are the remaining Kint parsers. They are on by default.

### Kint\Parser\ArrayObjectPlugin

Ensures `ArrayObject` is parsed with the correct mode, and set back afterwards.

### Kint\Parser\Base64Plugin

Decodes base64 encoded strings.

`Kint\Parser\Base64Plugin::$min_length_hard` | Minimum length before the binary parser will start. Default 16.
`Kint\Parser\Base64Plugin::$min_length_soft` | Minimum length before the binary representation will take precedence over the string representation. Default 50.

### Kint\Parser\ClassMethodsPlugin

Shows methods available for an object.

### Kint\Parser\ClassStaticsPlugin

Shows statics and constants for an object.

### Kint\Parser\ClosurePlugin

Shows properties and uses for a closure.

### Kint\Parser\ColorPlugin

Detects a color string, then shows a color swatch and different ways to write the color.

`Kint\Zval\Representation\ColorRepresentation::$color_map` | A map of HTML color names to hex codes

### Kint\Parser\DateTimePlugin

Shows the formatted datetime inline, before unfolding.

### Kint\Parser\FsPathPlugin

Detects server file paths and shows information about them. See also Kint\Parser\SplFileInfoPlugin.

`Kint\Parser\FsPathPlugin::$blacklist` | List of paths to ignore. Default includes `/` and `.`

### Kint\Parser\IteratorPlugin

Shows the contents of an iterator.

`Kint\Parser\IteratorPlugin::$blacklist` | Classes to prevent iterating over. Default includes `PDOStatement`, `mysqli_result`, `DOMNodeList`, `DOMNamedNodeMap`, `SplFileObject`

### Kint\Parser\JsonPlugin

Decodes JSON encoded strings.

### Kint\Parser\SimpleXMLElementPlugin

Displays SimpleXMLElements with tabs for children and attributes.

`Kint\Parser\SimpleXMLElementPlugin::$verbose` | Show methods and properties. Default false.

### Kint\Parser\SplFileInfoPlugin

Shows information about the file.

### Kint\Parser\SplObjectStoragePlugin

Shows the SplObjectStorage contents.

### Kint\Parser\StreamPlugin

Shows stream metadata for stream resources.

### Kint\Parser\TablePlugin

Shows table-like multidimensional arrays as a table.

`Kint\Renderer\Rich\TablePlugin::$respect_str_length` | Whether the cells of the table should respect `Kint\Renderer\RichRenderer::$strlen_max`. Default `true`

### Kint\Parser\ThrowablePlugin

Shows the exception or error message inline, before unfolding.

### Kint\Parser\TimestampPlugin

Detects integers that seem like timestamps (Between 9 and 10 digits) and formats the time.

`Kint\Parser\TimestampPlugin::$blacklist` | Integers to ignore. Default includes common integers like `PHP_INT_MAX`

### Kint\Parser\TracePlugin

Detects a backtrace, and gathers information like stack frame source code snippets.

`Kint\Parser\TracePlugin::$blacklist` | Function calls to skip. A string for a function, or an array of class name and method name. Default includes `spl_autoload_call`

### Kint\Parser\XmlPlugin

Detects an XML string and loads it.

`Kint\Parser\XmlPlugin::$parse_method` | One of `SimpleXML` or `DOMDocument`. DOMDocument is slower, and you'll need to enable `Kint\Parser\DOMDocumentPlugin` for it to work. Default `SimpleXML`

</section>
<section id="text" markdown="1">

## Text mode plugins

Since text mode renderers can't show most of the parsed information anyway, the text renderer disables some plugins before parsing begins. `Kint\Renderer\TextRenderer::$parser_plugin_whitelist` is default set to only allow the following plugins:

* `Kint\Parser\BlacklistPlugin`
* `Kint\Parser\MicrotimePlugin`
* `Kint\Parser\StreamPlugin`
* `Kint\Parser\TracePlugin`

</section>

<h2><a href="{{ site.baseurl }}/settings/">Kint settings &raquo;</a></h2>

</div>
