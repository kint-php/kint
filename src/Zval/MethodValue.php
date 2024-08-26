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

namespace Kint\Zval;

use Kint\Utils;
use Kint\Zval\Representation\MethodDefinitionRepresentation;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class MethodValue extends Value
{
    use ParameterHoldingTrait;

    public const MAGIC_NAMES = [
        '__construct' => true,
        '__destruct' => true,
        '__call' => true,
        '__callstatic' => true,
        '__get' => true,
        '__set' => true,
        '__isset' => true,
        '__unset' => true,
        '__sleep' => true,
        '__wakeup' => true,
        '__serialize' => true,
        '__unserialize' => true,
        '__tostring' => true,
        '__invoke' => true,
        '__set_state' => true,
        '__clone' => true,
        '__debuginfo' => true,
    ];

    public ?string $type = 'method';
    public ?string $filename;
    public ?int $startline;
    public ?int $endline;
    public bool $abstract = false;
    public bool $final = false;
    public bool $internal;
    public ?string $docstring;
    public ?string $returntype = null;
    public bool $return_reference;
    /** @psalm-var array<string, true> */
    public array $hints = [
        'callable' => true,
        'method' => true,
    ];
    public bool $showparams = true;

    public function __construct(ReflectionFunctionAbstract $method)
    {
        parent::__construct($method->getName());

        $t = $method->getFileName();
        $this->filename = false === $t ? null : $t;
        $t = $method->getStartLine();
        $this->startline = false === $t ? null : $t;
        $t = $method->getEndLine();
        $this->endline = false === $t ? null : $t;
        $this->internal = $method->isInternal();
        $t = $method->getDocComment();
        $this->docstring = false === $t ? null : $t;
        $this->return_reference = $method->returnsReference();

        foreach ($method->getParameters() as $param) {
            $this->parameters[] = new ParameterValue($param);
        }

        $rt = $method->getReturnType();
        if ($rt) {
            $this->returntype = Utils::getTypeString($rt);
        }

        if ($method instanceof ReflectionMethod) {
            $this->static = $method->isStatic();
            $this->operator = $this->static ? Value::OPERATOR_STATIC : Value::OPERATOR_OBJECT;
            $this->abstract = $method->isAbstract();
            $this->final = $method->isFinal();
            $this->owner_class = $method->getDeclaringClass()->name;
            $this->access = Value::ACCESS_PUBLIC;
            if ($method->isProtected()) {
                $this->access = Value::ACCESS_PROTECTED;
            } elseif ($method->isPrivate()) {
                $this->access = Value::ACCESS_PRIVATE;
            }
        }

        if ($this->internal) {
            return;
        }

        $docstring = new MethodDefinitionRepresentation(
            $this->filename,
            $this->startline,
            $this->owner_class,
            $this->docstring
        );

        $docstring->implicit_label = true;
        $this->addRepresentation($docstring);
        $this->value = $docstring;
    }

    public function setAccessPathFrom(InstanceValue $parent): void
    {
        $name = \strtolower($this->getName());

        if ('__construct' === $name) {
            $this->access_path = 'new \\'.$parent->getType();
        } elseif ('__invoke' === $name) {
            $this->access_path = $parent->access_path;
        } elseif ('__clone' === $name) {
            $this->access_path = 'clone '.$parent->access_path;
            $this->showparams = false;
        } elseif ('__tostring' === $name) {
            $this->access_path = '(string) '.$parent->access_path;
            $this->showparams = false;
        } elseif (isset(self::MAGIC_NAMES[$name])) {
            $this->access_path = null;
        } elseif ($this->static) {
            $this->access_path = '\\'.$this->owner_class.'::'.$this->name;
        } else {
            $this->access_path = $parent->access_path.'->'.$this->name;
        }
    }

    public function getValueShort(): ?string
    {
        if (!$this->value || !($this->value instanceof MethodDefinitionRepresentation)) {
            return parent::getValueShort();
        }

        $ds = $this->value->getDocstringWithoutComments();

        if (null === $ds) {
            return null;
        }

        $ds = \explode("\n", $ds);

        $out = '';

        foreach ($ds as $line) {
            if (0 === \strlen(\trim($line)) || '@' === $line[0]) {
                break;
            }

            $out .= $line.' ';
        }

        if (\strlen($out)) {
            return \rtrim($out);
        }

        return null;
    }

    public function getModifiers(): ?string
    {
        $out = null;

        if ($this->abstract) {
            $out = 'abstract ';
        } elseif ($this->final) {
            $out = 'final ';
        }

        if (null !== ($s = $this->getAccess())) {
            $out .= $s.' ';
        }

        if ($this->static) {
            $out .= 'static';
        }

        if (null !== $out) {
            /** @psalm-var non-empty-string rtrim($out) */
            return \rtrim($out);
        }

        return null;
    }

    public function getAccessPath(): ?string
    {
        if (null !== $this->access_path && $this->showparams) {
            return parent::getAccessPath().'('.$this->getParams().')';
        }

        return parent::getAccessPath();
    }

    public function getPhpDocUrl(): ?string
    {
        if (!$this->internal) {
            return null;
        }

        if ($this->owner_class) {
            $class = \str_replace('\\', '-', \strtolower($this->owner_class));
        } else {
            $class = 'function';
        }

        $funcname = \str_replace('_', '-', \strtolower($this->getName()));

        if (0 === \strpos($funcname, '--') && 0 !== \strpos($funcname, '-', 2)) {
            $funcname = \substr($funcname, 2);
        }

        return 'https://www.php.net/'.$class.'.'.$funcname;
    }
}
