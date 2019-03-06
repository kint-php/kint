<?php

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

use Kint\Object\BasicObject;
use Kint\Object\Representation\Representation;

class kint_smarty extends Plugin
{
    public function getTypes()
    {
        return ['object'];
    }

    public function getTriggers()
    {
        return Parser::TRIGGER_BEGIN;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        $className = \get_class($var);
        $supportedClasses = [
            'Smarty_Internal_Template',
            'Smarty_Variable',
            'Smarty',
        ];
        if (!\in_array($className, $supportedClasses)) {
            return;
        }

        if ($className === 'Smarty_Variable') {
            $o = $this->parser->parse($var->value, $o); // simply replace with its value
            $o->type = 'Smarty Variable :: ' . $o->type;

            $this->parser->haltParse();
            return;
        }

        $r = new Representation('Assigned variables');
        foreach ($var->tpl_vars as $name => $val) {
            $r->contents[] = $this->parser->parse(
                $val->value,
                BasicObject::blank($name)
            );
        }
        $o->addRepresentation($r);

        $r = new Representation('Globally assigned variables');
        foreach (\Smarty::$global_tpl_vars as $name => $val) {
            if ($name === 'SCRIPT_NAME') {
                continue;
            }

            $r->contents[] = $this->parser->parse(
                $val->value,
                BasicObject::blank($name)
            );
        }
        $o->addRepresentation($r);

        $r = new Representation('Configuration');
        $var1 = $var->getCompileDir();
        $r->contents[] = $this->parser->parse(
            $var1,
            BasicObject::blank('Compiled files stored in')
        );
        $o->addRepresentation($r);

        $o->classname = $className; // TODO this is necessary for TraceFrameObject.php:95 to not throw an error 
        $o->type = $className;


        $this->parser->haltParse();
    }
}
