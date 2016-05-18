<?php

namespace Kint\Renderer;

use Kint\Object;
use Kint\Parser;

class Js
{
    public static $firstRun = false;

    private static function _unparse(Object $kintVar)
    {
        if ($kintVar->value !== null && ($kintVar->size === null || $kintVar->extendedValue === null)) {
            if ($kintVar->type === 'string') {
                return substr($kintVar->value, 1, -1);
            } else {
                return $kintVar->value;
            }
        }

        $ret = array();

        if ($kintVar->extendedValue !== null) {
            foreach ($kintVar->extendedValue as $key => $var) {
                if ($var->name !== null) {
                    $key = $var->name;
                    if ($key[0] === "'" && substr($key, -1) === "'") {
                        $key = substr($key, 1, -1);
                    }
                    if (ctype_digit($key)) {
                        $key = (int) $key;
                    }
                }
                $ret[$key] = self::_unparse($var);
            }
        }

        if (class_exists($kintVar->type)) {
            $ret = (object) $ret;
        }

        return $ret;
    }

    public static function decorate(Object $kintVar)
    {
        return 'kintDump.push('.json_encode(self::_unparse($kintVar)).');'
            .'console.log(kintDump[kintDump.length-1]);';
    }

    public static function decorateTrace($traceData)
    {
        foreach ($traceData as &$frame) {
            if (isset($frame['args'])) {
                Parser::reset();
                $frame['args'] = self::_unparse(Parser::factory($frame['args']));
            }

            if (isset($frame['object'])) {
                Parser::reset();
                $frame['object'] = self::_unparse(Parser::factory($frame['object']));
            }
        }

        return 'kintDump.push('.json_encode($traceData).');'
            .'console.log(kintDump[kintDump.length-1]);';
    }

    /**
     * called for each dump, opens the html tag.
     */
    public static function wrapStart()
    {
        return "<script>if(typeof kintDump==='undefined')var kintDump=[];";
    }

    /**
     * closes wrapStart() started html tags.
     */
    public static function wrapEnd()
    {
        return '</script>';
    }

    public static function init()
    {
        return '';
    }
}
