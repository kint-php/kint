<?php

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\Representation\Representation;

class SerializePlugin extends Plugin
{
    /**
     * Disables automatic unserialization on arrays and objects.
     *
     * As the PHP manual notes:
     *
     * > Unserialization can result in code being loaded and executed due to
     * > object instantiation and autoloading, and a malicious user may be able
     * > to exploit this.
     *
     * The natural way to stop that from happening is to just refuse to unserialize
     * stuff by default. Which is what we're doing for anything that's not scalar.
     *
     * @var bool
     */
    public static $safe_mode = true;
    public static $options = array(true);

    public function getTypes()
    {
        return array('string');
    }

    public function getTriggers()
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        $trimmed = rtrim($var);

        if ($trimmed !== 'N;' && !preg_match('/^(?:[COabis]:\d+[:;]|d:\d+(?:\.\d+);)/', $trimmed)) {
            return;
        }

        if (!self::$safe_mode || !in_array($trimmed[0], array('C', 'O', 'a'))) {
            $blacklist = false;

            // Second parameter only supported on PHP 7
            if (KINT_PHP70) {
                // Suppress warnings on unserializeable variable
                $data = @unserialize($trimmed, self::$options);
            } else {
                $data = @unserialize($trimmed);
            }

            if ($data === false && substr($trimmed, 0, 4) !== 'b:0;') {
                return;
            }
        } else {
            $blacklist = true;
        }

        $base_obj = new BasicObject();
        $base_obj->depth = $o->depth + 1;
        $base_obj->name = 'unserialize('.$o->name.')';

        if ($o->access_path) {
            $base_obj->access_path = 'unserialize('.$o->access_path;
            if (!KINT_PHP70 || self::$options === array(true)) {
                $base_obj->access_path .= ')';
            } elseif (self::$options === array(false)) {
                $base_obj->access_path .= ', false)';
            } else {
                $base_obj->access_path .= ', Serialize::$options)';
            }
        }

        $r = new Representation('Serialized');

        if ($blacklist) {
            $base_obj->hints[] = 'blacklist';
            $r->contents = $base_obj;
        } else {
            $r->contents = $this->parser->parse($data, $base_obj);
        }

        $o->addRepresentation($r, 0);
    }
}
