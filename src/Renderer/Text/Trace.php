<?php

class Kint_Renderer_Text_Trace extends Kint_Renderer_Text_Plugin
{
    public function render($o)
    {
        $out = '';

        if ($o->depth == 0) {
            $out .= $this->renderer->colorTitle($this->renderer->renderTitle($o)).PHP_EOL;
        }

        $out .= $this->renderer->renderHeader($o).':'.PHP_EOL;

        $indent = str_repeat(' ', ($o->depth + 1) * $this->renderer->indent_width);

        $i = 1;
        foreach ($o->value->contents as $frame) {
            $framedesc = $indent.str_pad($i.': ', 4, ' ');

            if ($frame->trace['file']) {
                $framedesc .= $this->renderer->ideLink($frame->trace['file'], $frame->trace['line']).PHP_EOL;
            } else {
                $framedesc .= 'PHP internal call'.PHP_EOL;
            }

            $framedesc .= $indent.'    ';

            if ($frame->trace['class']) {
                $framedesc .= $this->renderer->escape($frame->trace['class']);

                if ($frame->trace['object']) {
                    $framedesc .= $this->renderer->escape('->');
                } else {
                    $framedesc .= '::';
                }
            }

            if (is_string($frame->trace['function'])) {
                $framedesc .= $this->renderer->escape($frame->trace['function']).'(...)';
            } elseif ($frame->trace['function'] instanceof Kint_Object_Method) {
                $framedesc .= $this->renderer->escape($frame->trace['function']->getName());
                $framedesc .= '('.$this->renderer->escape($frame->trace['function']->getParams()).')';
            }

            $out .= $this->renderer->colorType($framedesc).PHP_EOL.PHP_EOL;

            if ($source = $frame->getRepresentation('source')) {
                $line_wanted = $source->line;
                $source = $source->source;

                // Trim empty lines from the start and end of the source
                foreach ($source as $linenum => $line) {
                    if (trim($line) || $linenum === $line_wanted) {
                        break;
                    } else {
                        unset($source[$linenum]);
                    }
                }

                foreach (array_reverse($source, true) as $linenum => $line) {
                    if (trim($line) || $linenum === $line_wanted) {
                        break;
                    } else {
                        unset($source[$linenum]);
                    }
                }

                foreach ($source as $lineno => $line) {
                    if ($lineno == $line_wanted) {
                        $out .= $indent.$this->renderer->colorValue($this->renderer->escape($line)).PHP_EOL;
                    } else {
                        $out .= $indent.$this->renderer->escape($line).PHP_EOL;
                    }
                }
            }

            ++$i;
        }

        return $out;
    }
}
