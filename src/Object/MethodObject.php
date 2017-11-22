<?php

namespace Kint\Object;

use Kint\Object\Representation\DocstringRepresentation;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class MethodObject extends BasicObject
{
    public $type = 'method';
    public $filename;
    public $startline;
    public $endline;
    public $parameters = array();
    public $abstract;
    public $final;
    public $internal;
    public $returntype = null;
    public $return_reference = false;
    public $hints = array('callable', 'method');
    public $showparams = true;

    private $paramcache = null;

    public function __construct(ReflectionFunctionAbstract $method)
    {
        $this->name = $method->getName();
        $this->filename = $method->getFilename();
        $this->startline = $method->getStartLine();
        $this->endline = $method->getEndLine();
        $this->internal = $method->isInternal();
        $this->docstring = $method->getDocComment();
        $this->return_reference = $method->returnsReference();

        foreach ($method->getParameters() as $param) {
            $this->parameters[] = new ParameterObject($param);
        }

        if (KINT_PHP70) {
            $this->returntype = $method->getReturnType();
            if ($this->returntype) {
                $this->returntype = (string) $this->returntype;
            }
        }

        if ($method instanceof ReflectionMethod) {
            $this->static = $method->isStatic();
            $this->operator = $this->static ? BasicObject::OPERATOR_STATIC : BasicObject::OPERATOR_OBJECT;
            $this->abstract = $method->isAbstract();
            $this->final = $method->isFinal();
            $this->owner_class = $method->getDeclaringClass()->name;
            $this->access = BasicObject::ACCESS_PUBLIC;
            if ($method->isProtected()) {
                $this->access = BasicObject::ACCESS_PROTECTED;
            } elseif ($method->isPrivate()) {
                $this->access = BasicObject::ACCESS_PRIVATE;
            }
        }

        $docstring = new DocstringRepresentation(
            $this->docstring,
            $this->filename,
            $this->startline
        );

        $docstring->implicit_label = true;
        $this->addRepresentation($docstring);
        $this->value = $docstring;
    }

    public function setAccessPathFrom(InstanceObject $parent)
    {
        static $magic = array(
            '__call' => true,
            '__callstatic' => true,
            '__clone' => true,
            '__construct' => true,
            '__debuginfo' => true,
            '__destruct' => true,
            '__get' => true,
            '__invoke' => true,
            '__isset' => true,
            '__set' => true,
            '__set_state' => true,
            '__sleep' => true,
            '__tostring' => true,
            '__unset' => true,
            '__wakeup' => true,
        );

        $name = strtolower($this->name);

        if ($name === '__construct') {
            $this->access_path = 'new \\'.$parent->getType();
        } elseif ($name === '__invoke') {
            $this->access_path = $parent->access_path;
        } elseif ($name === '__clone') {
            $this->access_path = 'clone '.$parent->access_path;
            $this->showparams = false;
        } elseif ($name === '__tostring') {
            $this->access_path = '(string) '.$parent->access_path;
            $this->showparams = false;
        } elseif (isset($magic[$name])) {
            $this->access_path = null;
        } elseif ($this->static) {
            $this->access_path = '\\'.$this->owner_class.'::'.$this->name;
        } else {
            $this->access_path = $parent->access_path.'->'.$this->name;
        }
    }

    public function getValueShort()
    {
        if (!$this->value || !($this->value instanceof DocstringRepresentation)) {
            return parent::getValueShort();
        }

        $ds = explode("\n", $this->value->docstringWithoutComments());

        $out = '';

        foreach ($ds as $line) {
            if (strlen(trim($line)) === 0 || $line[0] === '@') {
                break;
            }

            $out .= $line.' ';
        }

        if (strlen($out)) {
            return rtrim($out);
        }
    }

    public function getModifiers()
    {
        $mods = array(
            $this->abstract ? 'abstract' : null,
            $this->final ? 'final' : null,
            $this->getAccess(),
            $this->static ? 'static' : null,
        );

        $out = '';

        foreach ($mods as $word) {
            if ($word !== null) {
                $out .= $word.' ';
            }
        }

        if (strlen($out)) {
            return rtrim($out);
        }
    }

    public function getAccessPath()
    {
        if ($this->access_path !== null) {
            if ($this->showparams) {
                return parent::getAccessPath().'('.$this->getParams().')';
            } else {
                return parent::getAccessPath();
            }
        }
    }

    public function getParams()
    {
        if ($this->paramcache !== null) {
            return $this->paramcache;
        }

        $out = array();

        foreach ($this->parameters as $p) {
            $type = $p->getType();
            if ($type) {
                $type .= ' ';
            }

            $default = $p->getDefault();
            if ($default) {
                $default = ' = '.$default;
            }

            $ref = $p->reference ? '&' : '';

            $out[] = $type.$ref.$p->getName().$default;
        }

        return $this->paramcache = implode(', ', $out);
    }

    public function getPhpDocUrl()
    {
        if (!$this->internal) {
            return null;
        }

        if ($this->owner_class) {
            $class = strtolower($this->owner_class);
        } else {
            $class = 'function';
        }

        $funcname = str_replace('_', '-', strtolower($this->name));

        if (strpos($funcname, '--') === 0 && strpos($funcname, '-', 2) !== 0) {
            $funcname = substr($funcname, 2);
        }

        return 'https://secure.php.net/'.$class.'.'.$funcname;
    }
}
