<?php

class Kint_Renderer_Js extends Kint_Renderer
{
    /**
     * @var array variable name to store JS dumps in
     *
     * Set to variable name or false to disable storage
     */
    public static $dump_storage = 'kintDump';

    public function render(Kint_Object $o)
    {
        if (self::$dump_storage) {
            return self::$dump_storage.'.push('.json_encode($this->simplify($o)).');console.log('.self::$dump_storage.'['.self::$dump_storage.'.length-1]);';
        } else {
            return 'console.log('.json_encode($this->simplify($o)).');';
        }
    }

    private function simplify(Kint_Object $o)
    {
        if (is_array($o->value_representation->contents)) {
            $ret = array();
            foreach ($o->value_representation->contents as $child) {
                if (empty($child->value_representation)) {
                    continue;
                }

                $ret[$child->name] = $this->simplify($child);
            }

            if ($o->type !== 'array') {
                $ret = (object) $ret;
            }

            return $ret;
        } else {
            return $o->value_representation->contents;
        }
    }

    public function preRender()
    {
        if (self::$dump_storage === false) {
            return '<script>';
        } else {
            return '<script>if(typeof '.self::$dump_storage.'==="undefined")'.self::$dump_storage.'=[];';
        }
    }

    public function postRender()
    {
        return '</script>';
    }
}
