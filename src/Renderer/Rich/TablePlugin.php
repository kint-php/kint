<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Jonathan Vollebregt (jnvsor@gmail.com), Rokas Šleinius (raveren@gmail.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Kint\Renderer\Rich;

use Kint\Renderer\RichRenderer;
use Kint\Utils;
use Kint\Zval\BlobValue;
use Kint\Zval\InstanceValue;
use Kint\Zval\Representation\Representation;
use Kint\Zval\Value;

class TablePlugin extends AbstractPlugin implements TabPluginInterface
{
    public static bool $respect_str_length = true;

    public function renderTab(Representation $r): ?string
    {
        if (!\is_array($r->contents) || !\count($r->contents)) {
            return null;
        }

        $firstrow = \reset($r->contents);

        if (!\is_array($firstrow->value->contents ?? null)) {
            return null;
        }

        $out = '<pre><table><thead><tr><th></th>';

        /**
         * @psalm-suppress PossiblyNullIterator
         * Psalm bug #11055
         */
        foreach ($firstrow->value->contents as $field) {
            $out .= '<th>'.$this->renderer->escape($field->getName()).'</th>';
        }

        $out .= '</tr></thead><tbody>';

        foreach ($r->contents as $row) {
            $out .= '<tr><th>'.$this->renderer->escape($row->getName()).'</th>';

            /** @psalm-var object{value: object{contents: Value[]}} $row */
            foreach ($row->value->contents as $field) {
                $out .= '<td';
                $type = '';
                $size = '';
                $ref = '';

                if (null !== ($s = $field->getType())) {
                    $type = $this->renderer->escape($s);

                    if ($field->reference) {
                        $ref = '&amp;';
                        $type = $ref.$type;
                    }

                    if (null !== ($s = $field->getSize())) {
                        $size .= ' ('.$this->renderer->escape($s).')';
                    }
                }

                if ($type) {
                    $out .= ' title="'.$type.$size.'"';
                }

                $out .= '>';

                switch ($field->type) {
                    case 'boolean':
                        $out .= ((bool) ($field->value->contents ?? false)) ? '<var>'.$ref.'false</var>' : '<var>'.$ref.'true</var>';
                        break;
                    case 'integer':
                    case 'double':
                        if (\is_numeric($field->value->contents ?? null)) {
                            /**
                             * @psalm-var string|int|double|null $field->value->contents
                             * Psalm bug #11055
                             */
                            $out .= (string) $field->value->contents;
                        } else {
                            $out .= '<var>'.$type.'</var>';
                        }
                        break;
                    case 'null':
                        $out .= '<var>'.$ref.'null</var>';
                        break;
                    case 'string':
                        if ($field instanceof BlobValue && false !== $field->encoding && \is_string($field->value->contents ?? null)) {
                            /**
                             * @psalm-var string $val
                             * Psalm bug #11055
                             */
                            $val = $field->value->contents;
                            if (RichRenderer::$strlen_max && self::$respect_str_length) {
                                $val = Utils::truncateString($val, RichRenderer::$strlen_max);
                            }

                            $out .= $this->renderer->escape($val);
                        } else {
                            $out .= '<var>'.$type.'</var>';
                        }
                        break;
                    case 'array':
                        $out .= '<var>'.$ref.'array</var>'.$size;
                        break;
                    case 'object':
                        if ($field instanceof InstanceValue) {
                            $out .= '<var>'.$ref.$this->renderer->escape($field->classname).'</var>'.$size;
                        } else {
                            $out .= '<var>'.$type.'</var>';
                        }
                        break;
                    case 'resource':
                        $out .= '<var>'.$ref.'resource</var>';
                        break;
                    default:
                        $out .= '<var>'.$ref.'unknown</var>';
                        break;
                }

                if (isset($field->hints['blacklist'])) {
                    $out .= ' <var>Blacklisted</var>';
                } elseif (isset($field->hints['recursion'])) {
                    $out .= ' <var>Recursion</var>';
                } elseif (isset($field->hints['depth_limit'])) {
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
