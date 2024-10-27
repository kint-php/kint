---
permalink: /writing-plugins/
title: Writing plugins
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#interface">Parser plugin interfaces</a></li>
    <li><a href="#example">An example plugin</a></li>
    <li><a href="#render">Renderer plugins</a></li>
    <li><a href="#util">Utils</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Writing plugins

<section id="interface" markdown="1">

## Parser plugin interfaces

Parser plugins have to implement either `PluginCompleteInterface` or `PluginBeginInterface` depending on which triggers they want to respond to. Both of these extend `PluginInterface`.

All plugins need to implement `setParser`, `getTypes` and `getTriggers`.

* `setParser` receives the parser before the dump begins. You can store it for later use or discard it.
* `getTypes` returns the types of data this plugin can operate on. These are types as returned by `gettype`.
* `getTriggers` returns a bitmask of the events that will trigger this plugin. These are all constants of the `Parser` class.
    * `TRIGGER_BEGIN` runs before any parsing is done
    * `TRIGGER_SUCCESS` runs after parsing successfully finishes
    * `TRIGGER_DEPTH_LIMIT` and `TRIGGER_RECURSION` run after parsing is halted
    * `TRIGGER_COMPLETE` is `TRIGGER_SUCCESS | TRIGGER_RECURSION | TRIGGER_DEPTH_LIMIT`

If you use `TRIGGER_BEGIN` your plugin needs to implement `PluginBeginInterface`, otherwise it should implement `PluginCompleteInterface`.

### Begin plugins

When implementing a `PluginBeginInterface` you need to implement `parseBegin`. This takes the input variable and a `ContextInterface` and either returns an `AbstractValue` or `null`.

If a value is returned the parse will halt immediately, if `null` is returned the parse will continue with other begin plugins followed by the normal parse.

### Complete plugins

When implementing a `PluginCompleteInterface` you need to implement `parseComplete`. This takes the input variable, the initially parsed `AbstractValue`, and the trigger in question.

`parseComplete` must always return an `AbstractValue`, which will replace the input value in further plugin calls and when the parser returns.

</section>
<section id="example" markdown="1">

## An example plugin

Let's imagine we're working on a system that's a big black box. Everything has an ID, and we can get all the data we need by calling a function for that ID.

Wouldn't it be great if we could automatically show the data associated with an ID whenever we come across one?

### A barebones plugin

<pre class="prettyprint linenums">
<?php

use Kint\Parser\Parser;
use Kint\Parser\PluginCompleteInterface;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Representation\ValueRepresentation;

class MyPlugin implements PluginCompleteInterface
{
    public function setParser(Parser $p): void
    {
    }

    public function getTypes(): array
    {
        return ['integer', 'string'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parseComplete(&amp;$var, AbstractValue $v, int $trigger): AbstractValue
    {
        echo 'My parser found: ';
        var_dump($var);

        return $v;
    }
}

Kint::$plugins[] = new MyPlugin();

d(1234);
</pre>

Since we're taking IDs they will probably be either strings or integers, so we return an array with both types. We're going to use `TRIGGER_SUCCESS` for our plugin.

We'll just put a `var_dump` in `parseComplete` to make sure everything's working and return the input value.

Lastly, we add the plugin to the `Kint::$plugins` array and try it out.

> ![]({{ site.baseurl }}/images/plugin-before.png)

Yay!

### Implementing our plugin's functionality

<pre class="prettyprint linenums:11">
private Parser $parser;

public function setParser(Parser $p): void
{
    $this->parser = $p;
}

public function parseComplete(&$var, AbstractValue $v, int $trigger): AbstractValue
{
    if (!ctype_digit((string) $var)) {
        return $v;
    }

    global $big_black_box;
    $data = $big_black_box->get_data_from_id($var);
    if (empty($data)) {
        return $v;
    }

    $base = new BaseContext('Black box data');
    $base->depth = $v->getContext()->getDepth() + 1;

    $ap = $v->getContext()->getAccessPath();

    if (null !== $ap) {
        $base->access_path = '$GLOBALS[\'big_black_box\']->get_data_from_id('.$ap.')';
    }

    $parsed_data = $this->parser->parse($data, $base);
    $r = new ValueRepresentation('Black box data', $parsed_data);
    $v->addRepresentation($r);

    return $v;
}
</pre>

Firstly, we're going to be using the parser, so we store it with `setParser()`.

When we get a variable to parse, we first check that what we have is actually an ID. If it's a random string we don't need to waste time trying to get data from it, so we'll just return the input value.

Get the data we want to add to the dump. If we couldn't find any we'll just return.

Make our `BaseContext` -- this needs to contain information from the parent scope like the variable's name, access path, depth, whether it's public or private, etc.

If we have an access path to the variable we're parsing now, we can continue the access path to the data by wrapping the current one in the code we need to get the data. This means if the ID is found at `$array['key']->prop` then `$data['children']` will have an accurate access path like:

```php
$GLOBALS['big_black_box']->get_data_from_id($array['key']->prop)['children']
```

Parse the data with the base context into a new `AbstractValue`, then create a `ValueRepresentation` to display it. We could also use a `StringRepresentation` to display text or a `ContainerRepresentation` to display a list of multiple values.

Lastly, we add the representation to the value and return it.

> ![]({{ site.baseurl }}/images/plugin-after.png)

Yay!

---

You can look at the source code for the plugins shipped with Kint by default for more detailed examples on various behaviors.

</section>
<section id="render" markdown="1">

## Renderer plugins

Since renderers aren't unified they also have different plugin systems. Rich plugins must implement either `Kint\Renderer\Rich\ValuePluginInterface` or `Kint\Renderer\Rich\TabPluginInterface` and text plugins must implement `Kint\Renderer\Text\PluginInterface`.

`TabPluginInterface` will be called based on the output of `RepresentationInterface::getHint()`, while `ValuePluginInterface` and `PluginInterface` will be called based on the output of `AbstractValue::getHint()`.

If you require more sweeping changes you can also write your own renderer from scratch, or subclass the existing ones.

</section>
<section id="util" markdown="1">

## Utilities

These methods can come in handy.

`Kint\Parser::getCallerClass()` | Returns the class that called Kint or `null`.
`Kint\Parser::getDepthLimit()` | Returns the depth limit on the parser.
`Kint\Parser::parse()` | Parses a value.

---

`Kint\Utils::getHumanReadableBytes()` | Returns an array with a unit and value of a human readable representation of the amount of bytes.
`Kint\Utils::isSequential()` | Returns true if the array is sequential.
`Kint\Utils::isAssoc()` | Returns true if the array has any string keys.
`Kint\Utils::isTrace()` | Returns true if the array appears to be a valid backtrace.
`Kint\Utils::traceFrameIsListed()` | Returns true if the array `class` and `function` match a list of callables in the format of `Kint::$aliases`.
`Kint\Utils::normalizeAliases()` | Normalizes callables in the format of `Kint::$aliases`.
`Kint\Utils::isValidPhpName()` | Returns true if the string is valid as a php name.
`Kint\Utils::isValidPhpNamespace()` | Returns true if the string is valid as php names separated by backslashes.
`Kint\Utils::errorSanitizeString()` | Truncates a string at a nul byte for use in `trigger_error`.
`Kint\Utils::getTypeString()` | Returns a string of the `ReflectionType` across versions of PHP.
`Kint\Utils::truncateString()` | Truncates a string to a specified length, and appends an ellipsis if needed.
`Kint\Utils::detectEncoding()` | Detects a strings encoding based on `Utils::$char_encodings` and `Utils::$legacy_encodings`.
`Kint\Utils::strlen()` | `mb_strlen` with encoding detection.
`Kint\Utils::substr()` | `mb_substr` with encoding detection.

</section>

</div>
