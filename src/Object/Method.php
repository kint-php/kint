<?php

class Kint_Object_Method extends Kint_Object
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
    public $hints = array('callable', 'method');
    public $showparams = true;

    private $paramcache = null;

    public function __construct($method)
    {
        // PHP 5.1 compat (Docs don't correctly show which version ReflectionFunctionAbstract was added)
        if (!($method instanceof ReflectionMethod) && !($method instanceof ReflectionFunction)) {
            throw new InvalidArgumentException('Argument must be an instance of ReflectionFunctionAbstract');
        }

        $this->name = $method->getName();
        $this->filename = $method->getFilename();
        $this->startline = $method->getStartLine();
        $this->endline = $method->getEndLine();
        $this->internal = $method->isInternal();
        $this->docstring = $method->getDocComment();

        foreach ($method->getParameters() as $param) {
            $this->parameters[] = new Kint_Object_Parameter($param);
        }

        if (KINT_PHP70) {
            $this->returntype = $method->getReturnType();
            if ($this->returntype) {
                $this->returntype = (string) $this->returntype;
            }
        }

        if (!$this->returntype && $this->docstring) {
            if (preg_match('/@return\s+(.*)\r?\n/m', $this->docstring, $matches)) {
                if (!empty($matches[1])) {
                    $this->returntype = $matches[1];
                }
            }
        }

        if ($method instanceof ReflectionMethod) {
            $this->static = $method->isStatic();
            $this->operator = $this->static ? Kint_Object::OPERATOR_STATIC : Kint_Object::OPERATOR_OBJECT;
            $this->abstract = $method->isAbstract();
            $this->final = $method->isFinal();
            $this->owner_class = $method->getDeclaringClass()->name;
            $this->access = Kint_Object::ACCESS_PUBLIC;
            if ($method->isProtected()) {
                $this->access = Kint_Object::ACCESS_PROTECTED;
            } elseif ($method->isPrivate()) {
                $this->access = Kint_Object::ACCESS_PRIVATE;
            }
        }

        $docstring = new Kint_Object_Representation_Docstring(
            $this->docstring,
            $this->filename,
            $this->startline
        );

        $docstring->implicit_label = true;
        $this->addRepresentation($docstring);
    }

    public function setAccessPathFrom(Kint_Object_Instance $parent)
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
            if (KINT_PHP53) {
                $this->access_path = 'new \\'.$parent->getType();
            } else {
                $this->access_path = 'new '.$parent->getType();
            }
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
            if (KINT_PHP53) {
                $this->access_path = '\\'.$this->owner_class.'::'.$this->name;
            } else {
                $this->access_path = $this->owner_class.'::'.$this->name;
            }
        } else {
            $this->access_path = $parent->access_path.'->'.$this->name;
        }
    }

    public function getValueShort()
    {
        if (!$this->value || !($this->value instanceof Kint_Object_Representation_Docstring)) {
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
