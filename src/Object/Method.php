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
        $this->operator = $this->static ? Kint_Object::OPERATOR_STATIC : Kint_Object::OPERATOR_OBJECT;
        $this->access = Kint_Object::ACCESS_PUBLIC;

        foreach ($method->getParameters() as $param) {
            $this->parameters[] = new Kint_Object_Parameter($param);
        }

        if ($this->docstring) {
            if (preg_match('/@return\s+(.*)\r?\n/m', $this->docstring, $matches)) {
                if (!empty($matches[1])) {
                    $this->returntype = $matches[1];
                }
            }
        }

        if ($method instanceof ReflectionMethod) {
            $this->static = $method->isStatic();
            $this->abstract = $method->isAbstract();
            $this->final = $method->isFinal();
            $this->owner_class = $method->getDeclaringClass()->name;
            if ($method->isProtected()) {
                $this->access = Kint_Object::ACCESS_PROTECTED;
            } elseif ($method->isPrivate()) {
                $this->access = Kint_Object::ACCESS_PRIVATE;
            }

            $docstring = new Kint_Object_Representation_Docstring(
                $this->docstring,
                $this->filename,
                $this->startline,
                $this->owner_class === $method->class ? null : $this->owner_class
            );
        } else {
            $docstring = new Kint_Object_Representation_Docstring(
                $this->docstring,
                $this->filename,
                $this->startline
            );
        }

        $docstring->implicit_label = true;
        $this->addRepresentation($docstring);
    }

    public function setAccessPathFrom(Kint_Object $parent, $class)
    {
        if ($this->name === '__construct') {
            if (KINT_PHP53) {
                $this->access_path = 'new \\'.$class;
            } else {
                $this->access_path = 'new '.$class;
            }
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
            return $out;
        }
    }

    public function getModifiers()
    {
        $mods = array(
            $this->abstract ? 'abstract' : null,
            $this->final ? 'final' : null,
            $this->getAccess(),
            $this->const ? 'const' : null,
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
            return parent::getAccessPath().'('.$this->getParams().')';
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

            $ref = $p->reference ? '&' : '';

            if ($type) {
                $out[] = $type.' '.$ref.$p->getName();
            } else {
                $out[] = $ref.$p->getName();
            }
        }

        return $this->paramcache = implode(', ', $out);
    }
}
