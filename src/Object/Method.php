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

    public function __construct(ReflectionMethod $method)
    {
        $this->static = $method->isStatic();
        $this->abstract = $method->isAbstract();
        $this->final = $method->isFinal();
        $this->owner_class = $method->getDeclaringClass()->name;
        $this->name = $method->getShortName();
        $this->filename = $method->getFilename();
        $this->startline = $method->getStartLine();
        $this->endline = $method->getEndLine();
        $this->docstring = $method->getDocComment();
        $this->operator = $this->static ? Kint_Object::OPERATOR_STATIC : Kint_Object::OPERATOR_OBJECT;

        $this->access = Kint_Object::ACCESS_PUBLIC;
        if ($method->isProtected()) {
            $this->access = Kint_Object::ACCESS_PROTECTED;
        } elseif ($method->isPrivate()) {
            $this->access = Kint_Object::ACCESS_PRIVATE;
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

    public function renderValueShort()
    {
        if (!$this->value_representation || !($this->value_representation instanceof Kint_Object_Representation_Docstring)) {
            return parent::renderValueShort();
        }

        $string = array();
        $lines = explode("\n", $this->value_representation->docstringWithoutComments());

        foreach ($lines as $line) {
            if (Kint_Object_Blob::strlen($line) === 0) {
                break;
            }

            $string[] = $line;
        }

        $string = implode(' ', $string);

        if (Kint_Object_Blob::strlen($string) > Kint::$maxStrLength) {
            return Kint_Object_Blob::escape(substr($string, 0, Kint::$maxStrLength).'...');
        } else {
            return Kint_Object_Blob::escape($string);
        }
    }

    public function renderOperator()
    {
        if ($this->returntype) {
            return parent::renderOperator();
        }
    }

    public function renderType()
    {
        if ($this->returntype === null) {
            return '';
        } else {
            return $this->returntype;
        }
    }

    public function renderModifiers()
    {
        $out = array(
            $this->abstract ? 'abstract' : null,
            $this->final ? 'final' : null,
            $this->renderAccess(),
            $this->const ? 'const' : null,
            $this->static ? 'static' : null,
        );

        foreach ($out as $index => $word) {
            if ($word === null) {
                unset($out[$index]);
            }
        }

        return Kint_Object_Blob::escape(implode(' ', $out));
    }

    public function renderAccessPath()
    {
        if ($this->access_path !== null) {
            return parent::renderAccessPath().$this->renderParams();
        }
    }

    public function renderName()
    {
        return parent::renderName().$this->renderParams();
    }

    private function renderParams()
    {
        $out = array();

        foreach ($this->parameters as $p) {
            $type = $p->renderType();
            if ($type) {
                $out[] = Kint_Object_Blob::escape($type.' '.$p->renderName());
            } else {
                $out[] = Kint_Object_Blob::escape($p->renderName());
            }
        }

        return '('.implode(', ', $out).')';
    }
}
