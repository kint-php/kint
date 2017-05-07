<?php

class Kint_Renderer_Rich_Table extends Kint_Renderer_Rich_Plugin
{
    public static $respect_str_length = true;

    public function render($r)
    {
        $out = '<pre><table><thead><tr><th></th>';

        $firstrow = reset($r->contents);

        foreach ($firstrow->value->contents as $field) {
            $out .= '<th>'.$this->renderer->escape($field->name).'</th>';
        }

        $out .= '</tr></thead><tbody>';

        foreach ($r->contents as $row) {
            $out .= '<tr><th>';
            $out .= $this->renderer->escape($row->name);
            $out .= '</th>';

            foreach ($row->value->contents as $field) {
                $out .= '<td';
                $type = '';
                $size = '';
                $ref = '';

                if (($s = $field->getType()) !== null) {
                    $type = $this->renderer->escape($s);

                    if ($field->reference) {
                        $ref = '&amp;';
                        $type = $ref.$type;
                    }

                    if (($s = $field->getSize()) !== null) {
                        $size .= ' ('.$this->renderer->escape($s).')';
                    }
                }

                if ($type) {
                    $out .= ' title="'.$type.$size.'"';
                }

                $out .= '>';

                switch ($field->type) {
                    case 'boolean':
                        $out .= $field->value->contents ? '<var>'.$ref.'true</var>' : '<var>'.$ref.'false</var>';
                        break;
                    case 'integer':
                    case 'double':
                        $out .= (string) $field->value->contents;
                        break;
                    case 'null':
                        $out .= '<var>'.$ref.'null</var>';
                        break;
                    case 'string':
                        $val = $field->value->contents;
                        if (Kint_Renderer_Rich::$strlen_max && self::$respect_str_length && Kint_Object_Blob::strlen($val) > Kint_Renderer_Rich::$strlen_max) {
                            $val = substr($val, 0, Kint_Renderer_Rich::$strlen_max).'...';
                        }

                        $out .= $this->renderer->escape($val);
                        break;
                    case 'array':
                        $out .= '<var>'.$ref.'array</var>'.$size;
                        break;
                    case 'object':
                        $out .= '<var>'.$ref.$this->renderer->escape($field->classname).'</var>'.$size;
                        break;
                    case 'resource':
                        $out .= '<var>'.$ref.'resource</var>';
                        break;
                    default:
                        $out .= '<var>'.$ref.'unknown</var>';
                        break;
                }

                if (in_array('blacklist', $field->hints)) {
                    $out .= ' <var>Blacklisted</var>';
                } elseif (in_array('recursion', $field->hints)) {
                    $out .= ' <var>Recursion</var>';
                } elseif (in_array('depth_limit', $field->hints)) {
                    $out .= ' <var>Depth Limit</var>';
                }

                $out .= '</td>';
            }

            $out .= '</tr>';
        }

        $out .= '</tbody></table></pre>';

        return $out;
    }
}
