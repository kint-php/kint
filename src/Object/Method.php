<?php

namespace Kint\Object;

use Kint\Object;

class Method extends Object
{
    public $type = 'method';
    public $filename;
    public $startline;
    public $endline;
    public $parameters = array();
    public $abstract;
    public $final;
    public $returntype = null;

    public function __construct(\ReflectionMethod $method)
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
        $this->operator = Object::OPERATOR_OBJECT;

        $this->access = Object::ACCESS_PUBLIC;
        if ($method->isProtected()) {
            $this->access = Object::ACCESS_PROTECTED;
        } elseif ($method->isPrivate()) {
            $this->access = Object::ACCESS_PRIVATE;
        }

        foreach ($method->getParameters() as $param) {
            $this->parameters[] = new Object\Parameter($param);
        }

        if ($this->docstring) {
            if (preg_match('/@return\s+(.*)\r?\n/m', $this->docstring, $matches)) {
                if (!empty($matches[1])) {
                    $this->returntype = $matches[1];
                }
            }
        }

        $lines = array();
        foreach (explode("\n", $this->docstring) as $line) {
            $line = trim($line);

            if (in_array($line, array('/**', '/*', '*/'))) {
                continue;
            } elseif (strpos($line, '*') === 0) {
                $line = ltrim(substr($line, 1));
            }

            $lines[] = $line;
        }

        if (count(array_filter($lines, 'strlen'))) {
            $docstring = new Object\Representation('Docstring');
            $docstring->contents = implode("\n", $lines);
            $docstring->renderers[] = 'method';
            $this->addRepresentation($docstring);
        }
    }

    public function renderValueShort()
    {
        $rep = $this->getRepresentation('docstring');
        if (!$rep) {
            return;
        }

        $string = array();
        $lines = explode("\n", $rep->contents);

        foreach ($lines as $line) {
            $line = trim($line);
            if (substr($line, 0, 1) === '@' || Blob::strlen($line) === 0) {
                break;
            }
            $string[] = $line;
        }

        if (!count($string)) {
            return;
        }

        $string = implode("\n", $string);

        if (Blob::strlen($string) > \Kint::$maxStrLength) {
            return Blob::escape(substr($string, 0, \Kint::$maxStrLength).'...');
        } else {
            return Blob::escape($string);
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

        return Blob::escape(implode(' ', $out));
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
                $out[] = Blob::escape($type.' '.$p->renderName());
            } else {
                $out[] = Blob::escape($p->renderName());
            }
        }

        return '('.implode(', ', $out).')';
    }
}
