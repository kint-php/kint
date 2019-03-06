<?php

use Kint\Object\BasicObject;
use Kint\Object\Representation\Representation;
use Kint\Parser\Parser;
use Kint\Parser\Plugin;

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
			$o->type = 'Smarty Variable :: '.$o->type;
			
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
		foreach (Smarty::$global_tpl_vars as $name => $val) {
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

		$o->classname = $className; // todo this is necessary for TraceFrameObject.php:95 to not throw an error 
		$o->type = $className;


		$this->parser->haltParse();
	}
}
