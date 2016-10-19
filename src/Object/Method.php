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
    public $returntype = null;
    public $hints = array('callable', 'method');

    public function __construct(ReflectionFunctionAbstract $method)
    {
        $this->name = $method->getShortName();
        $this->filename = $method->getFilename();
        $this->startline = $method->getStartLine();
        $this->endline = $method->getEndLine();
        $this->docstring = $method->getDocComment();
        $this->operator = $this->static ? Kint_Object::OPERATOR_STATIC : Kint_Object::OPERATOR_OBJECT;
        $this->access = Kint_Object::ACCESS_PUBLIC;

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
        }

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

        $docstring = new Kint_Object_Representation_Docstring(
            $this->docstring,
            $this->filename,
            $this->startline
        );
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
        } elseif (substr($parent->access_path, 0, 4) === 'new ') {
            $this->access_path = '('.$parent->access_path.')->'.$this->name;
        } else {
            $this->access_path = $parent->access_path.'->'.$this->name;
        }
    }

    public function getValueShort()
    {
        if (!$this->value_representation || !($this->value_representation instanceof Kint_Object_Representation_Docstring)) {
            return parent::getValueShort();
        }

        $string = array();
        $lines = explode("\n", $this->value_representation->docstringWithoutComments());

        foreach ($lines as $line) {
            if (Kint_Object_Blob::strlen($line) === 0) {
                break;
            }

            $string[] = $line;
        }

        return implode(' ', $string);
    }

    public function getModifiers()
    {
        $out = array(
            $this->abstract ? 'abstract' : null,
            $this->final ? 'final' : null,
            $this->getAccess(),
            $this->const ? 'const' : null,
            $this->static ? 'static' : null,
        );

        foreach ($out as $index => $word) {
            if ($word === null) {
                unset($out[$index]);
            }
        }

        return implode(' ', $out);
    }

    public function getAccessPath()
    {
        if ($this->access_path !== null) {
            return parent::getAccessPath().'('.$this->getParams().')';
        }
    }

    public function getParams()
    {
        $out = array();

        foreach ($this->parameters as $p) {
            $type = $p->getType();
            if ($type) {
                $out[] = $type.' '.$p->getName();
            } else {
                $out[] = $p->getName();
            }
        }

        return implode(', ', $out);
    }
}
