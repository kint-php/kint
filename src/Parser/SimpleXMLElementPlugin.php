<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Jonathan Vollebregt (jnvsor@gmail.com), Rokas Šleinius (raveren@gmail.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Kint\Parser;

use Kint\Utils;
use Kint\Zval\Representation\Representation;
use Kint\Zval\SimpleXMLElementValue;
use Kint\Zval\Value;
use SimpleXMLElement;

class SimpleXMLElementPlugin extends AbstractPlugin
{
    /**
     * Show all properties and methods.
     */
    public static bool $verbose = false;

    protected ClassMethodsPlugin $methods_plugin;

    public function __construct(Parser $parser)
    {
        parent::__construct($parser);

        $this->methods_plugin = new ClassMethodsPlugin($parser);
    }

    public function setParser(Parser $p): void
    {
        parent::setParser($p);

        $this->methods_plugin->setParser($p);
    }

    public function getTypes(): array
    {
        return ['object'];
    }

    public function getTriggers(): int
    {
        // SimpleXMLElement is a weirdo. No recursion (Or rather everything is
        // recursion) and depth limit will have to be handled manually anyway.
        return Parser::TRIGGER_BEGIN;
    }

    public function parse(&$var, Value &$o, int $trigger): void
    {
        if (!$var instanceof SimpleXMLElement) {
            return;
        }

        $parser = $this->getParser();
        $pdepth = $parser->getDepthLimit();
        $parser->haltParse();

        $x = new SimpleXMLElementValue($o->name, \get_class($var), \spl_object_hash($var), \spl_object_id($var));
        $x->transplant($o);
        $x->size = null;
        $x->value = null;

        $depthlimit = $pdepth && $x->depth >= $pdepth;
        $has_children = self::hasChildElements($var);
        $string_body = !$has_children && \strlen((string) $var);

        if ($depthlimit && $has_children) {
            $x->hints['depth_limit'] = true;
        } else {
            if ($a = $this->getAttributeRepresentation($x, $var)) {
                $x->addRepresentation($a, 0);
            }

            if (!$depthlimit && $has_children && $c = $this->getChildrenRepresentation($x, $var)) {
                /** @psalm-var Value[] $c->contents */
                $x->size = \count($c->contents);
                $x->addRepresentation($c, 0);
                $x->value = $c;
            }

            if (self::$verbose) {
                $this->methods_plugin->parse($var, $x, Parser::TRIGGER_SUCCESS);
            }

            if ($string_body) {
                $base_obj = new Value($x->name);
                $base_obj->depth = $x->depth + 1;
                if (null !== $x->access_path) {
                    $x->access_path = '(string) '.$x->access_path;
                    $base_obj->access_path = $x->access_path;
                }

                $string = (string) $var;

                $ts = new Representation('toString');
                $ts->implicit_label = true;
                $ts->contents = $this->getParser()->parse($string, $base_obj);

                $x->addRepresentation($ts, 0);
                $x->value = $ts;
            }
        }

        $o = $x;
    }

    protected function getAttributeRepresentation(SimpleXMLElementValue $element, SimpleXMLElement $var): ?Representation
    {
        $parser = $this->getParser();
        $namespaces = \array_merge(['' => null], $var->getDocNamespaces());

        // Attributes
        $a = new Representation('Attributes');
        $a->contents = [];

        foreach ($namespaces as $nsAlias => $nsUrl) {
            if ((bool) $nsAttribs = $var->attributes($nsAlias, true)) {
                foreach ($nsAttribs as $name => $attrib) {
                    $obj = new Value($name);
                    $obj->depth = $element->depth + 1;
                    $obj->access = Value::ACCESS_NONE;
                    $obj->operator = Value::OPERATOR_ARRAY;

                    if (null !== $element->access_path) {
                        $obj->access_path = '(string) '.$element->access_path;
                        if ('' !== $nsAlias) {
                            $obj->access_path .= '->attributes('.\var_export($nsAlias, true).', true)';
                        }
                        $obj->access_path .= '['.\var_export($name, true).']';
                    }

                    if ('' !== $nsAlias) {
                        $obj->name = $nsAlias.':'.$obj->name;
                    }

                    $string = (string) $attrib;
                    $attribute = $parser->parse($string, $obj);

                    $a->contents[] = $attribute;
                }
            }
        }

        if ($a->contents) {
            return $a;
        }

        return null;
    }

    /**
     * Alright kids, let's learn about SimpleXMLElement::children!
     * children can take a namespace url or alias and provide a list of
     * child nodes. This is great since just accessing the members through
     * properties doesn't work on SimpleXMLElement when they have a
     * namespace at all!
     *
     * Unfortunately SimpleXML decided to go the retarded route of
     * categorizing elements by their tag name rather than by their local
     * name (to put it in Dom terms) so if you have something like this:
     *
     * <root xmlns:localhost="http://localhost/">
     *   <tag />
     *   <tag xmlns="http://localhost/" />
     *   <localhost:tag />
     * </root>
     *
     * * children(null) will get the first 2 results
     * * children('', true) will get the first 2 results
     * * children('http://localhost/') will get the last 2 results
     * * children('localhost', true) will get the last result
     *
     * So let's just give up and stick to aliases because fuck that mess!
     */
    protected function getChildrenRepresentation(SimpleXMLElementValue $element, SimpleXMLElement $var): Representation
    {
        $parser = $this->getParser();
        $namespaces = \array_merge(['' => null], $var->getDocNamespaces());

        // Children
        $c = new Representation('Children');
        $c->contents = [];

        foreach ($namespaces as $nsAlias => $nsUrl) {
            if ((bool) $nsChildren = $var->children($nsAlias, true)) {
                $nsap = [];
                foreach ($nsChildren as $name => $child) {
                    $obj = new Value((string) $name);
                    $obj->depth = $element->depth + 1;
                    $obj->access = Value::ACCESS_NONE;
                    $obj->operator = Value::OPERATOR_OBJECT;
                    $obj->hints['omit_spl_id'] = true;

                    if ('' !== $nsAlias) {
                        $obj->name = $nsAlias.':'.$name;
                    }

                    if (null !== $element->access_path) {
                        if ('' === $nsAlias) {
                            $obj->access_path = $element->access_path.'->';
                        } else {
                            $obj->access_path = $element->access_path.'->children('.\var_export($nsAlias, true).', true)->';
                        }

                        if (Utils::isValidPhpName((string) $name)) {
                            $obj->access_path .= (string) $name;
                        } else {
                            $obj->access_path .= '{'.\var_export((string) $name, true).'}';
                        }

                        if (isset($nsap[$obj->access_path])) {
                            ++$nsap[$obj->access_path];
                            $obj->access_path .= '['.$nsap[$obj->access_path].']';
                        } else {
                            $nsap[$obj->access_path] = 0;
                        }
                    }

                    $c->contents[] = $out = $parser->parse($child, $obj);
                }
            }
        }

        return $c;
    }

    /**
     * More SimpleXMLElement bullshit.
     *
     * If we want to know if the element contains text we can cast to string.
     * Except if it contains text mixed with elements simplexml for some stupid
     * reason decides to concatenate the text from between those elements
     * rather than all the text in the hierarchy...
     *
     * So we have NO way of getting text nodes between elements, but we can
     * still tell if we have elements right? If we have elements we assume it's
     * not a string and call it a day!
     *
     * Well if you cast the element to an array attributes will be on it so
     * you'd have to remove that key, and if it's a string it'll also have the
     * 0 index used for the string contents too...
     *
     * Wait, can we use the 0 index to tell if it's a string? Nope! CDATA
     * doesn't show up AT ALL when casting to anything but string, and we'll
     * still get those concatenated strings of mostly whitespace if we just do
     * (string) and check the length.
     *
     * Luckily, I found the only way to do this reliably is through children().
     * We still have to loop through all the namespaces and see if there's a
     * match but then we have the problem of the attributes showing up again...
     *
     * Or at least that's what var_dump says. And when we cast the result to
     * bool it's true too... But if we cast it to array then it's suddenly empty!
     *
     * Long story short the function below is the only way to reliably check if
     * a SimpleXMLElement has children
     */
    protected static function hasChildElements(SimpleXMLElement $var): bool
    {
        $namespaces = \array_merge(['' => null], $var->getDocNamespaces());

        foreach ($namespaces as $nsAlias => $nsUrl) {
            if ((array) $var->children($nsAlias, true)) {
                return true;
            }
        }

        return false;
    }
}
