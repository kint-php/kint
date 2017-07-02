<?php

class Kint_Renderer_Text extends Kint_Renderer
{
    public static $object_renderers = array(
        'blacklist' => 'Kint_Renderer_Text_Blacklist',
        'depth_limit' => 'Kint_Renderer_Text_DepthLimit',
        'nothing' => 'Kint_Renderer_Text_Nothing',
        'recursion' => 'Kint_Renderer_Text_Recursion',
        'trace' => 'Kint_Renderer_Text_Trace',
    );

    /**
     * Parser plugins must be instanceof one of these or
     * it will be removed for performance reasons.
     */
    public static $parser_plugin_whitelist = array(
        'Kint_Parser_Blacklist',
        'Kint_Parser_Stream',
        'Kint_Parser_Trace',
    );

    /**
     * The maximum length of a string before it is truncated.
     *
     * Falsey to disable
     *
     * @var int
     */
    public static $strlen_max = 0;

    /**
     * The default width of the terminal for headers.
     *
     * @var int
     */
    public static $default_width = 80;

    /**
     * Indentation width.
     *
     * @var int
     */
    public static $default_indent = 4;

    /**
     * Decorate the header and footer.
     *
     * @var bool
     */
    public static $decorations = true;

    public $header_width = 80;
    public $indent_width = 4;

    protected $plugin_objs = array();
    protected $previous_caller;
    protected $callee;
    protected $show_minitrace = true;

    public function __construct(array $params = array())
    {
        parent::__construct($params);

        $params += array(
            'callee' => null,
            'caller' => null,
        );

        $this->callee = $params['callee'];
        $this->previous_caller = $params['caller'];
        $this->show_minitrace = !empty($params['settings']['display_called_from']);
        $this->header_width = self::$default_width;
        $this->indent_width = self::$default_indent;
    }

    public function render(Kint_Object $o)
    {
        if ($plugin = $this->getPlugin(self::$object_renderers, $o->hints)) {
            if (strlen($output = $plugin->render($o))) {
                return $output;
            }
        }

        $out = '';

        if ($o->depth == 0) {
            $out .= $this->colorTitle($this->renderTitle($o)).PHP_EOL;
        }

        $out .= $this->renderHeader($o);
        $out .= $this->renderChildren($o).PHP_EOL;

        return $out;
    }

    public function boxText($text, $width)
    {
        if (Kint_Object_Blob::strlen($text) > $width - 4) {
            $text = Kint_Object_Blob::substr($text, 0, $width - 7).'...';
        }

        $text .= str_repeat(' ', $width - 4 - Kint_Object_Blob::strlen($text));

        $out = '┌'.str_repeat('─', $width - 2).'┐'.PHP_EOL;
        $out .= '│ '.$this->escape($text).' │'.PHP_EOL;
        $out .= '└'.str_repeat('─', $width - 2).'┘';

        return $out;
    }

    public function renderTitle(Kint_Object $o)
    {
        if (($name = $o->getName()) === null) {
            $name = 'literal';
        }

        if (self::$decorations) {
            return $this->boxText($name, $this->header_width);
        } elseif (Kint_Object_Blob::strlen($name) > $this->header_width) {
            return Kint_Object_Blob::substr($name, 0, $this->header_width - 3).'...';
        } else {
            return $name;
        }
    }

    public function renderHeader(Kint_Object $o)
    {
        $output = array();

        if ($o->depth) {
            if (($s = $o->getModifiers()) !== null) {
                $output[] = $s;
            }

            if ($o->name !== null) {
                $output[] = $this->escape(var_export($o->name, true));

                if (($s = $o->getOperator()) !== null) {
                    $output[] = $this->escape($s);
                }
            }
        }

        if (($s = $o->getType()) !== null) {
            if ($o->reference) {
                $s = '&'.$s;
            }

            $output[] = $this->colorType($this->escape($s));
        }

        if (($s = $o->getSize()) !== null) {
            $output[] = '('.$this->escape($s).')';
        }

        if (($s = $o->getValueShort()) !== null) {
            if (self::$strlen_max && Kint_Object_Blob::strlen($s) > self::$strlen_max) {
                $s = substr($s, 0, self::$strlen_max).'...';
            }
            $output[] = $this->colorValue($this->escape($s));
        }

        return str_repeat(' ', $o->depth * $this->indent_width).implode(' ', $output);
    }

    public function renderChildren(Kint_Object $o)
    {
        if ($o->type === 'array') {
            $output = ' [';
        } elseif ($o->type === 'object') {
            $output = ' (';
        } else {
            return '';
        }

        $children = '';

        if ($o->value && is_array($o->value->contents)) {
            foreach ($o->value->contents as $child) {
                $children .= $this->render($child);
            }
        }

        if ($children) {
            $output .= PHP_EOL.$children;
            $output .= str_repeat(' ', $o->depth * $this->indent_width);
        }

        if ($o->type === 'array') {
            $output .= ']';
        } elseif ($o->type === 'object') {
            $output .= ')';
        }

        return $output;
    }

    public function colorValue($string)
    {
        return $string;
    }

    public function colorType($string)
    {
        return $string;
    }

    public function colorTitle($string)
    {
        return $string;
    }

    public function postRender()
    {
        if (self::$decorations) {
            $output = str_repeat('═', $this->header_width);
        } else {
            $output = '';
        }

        if (!$this->show_minitrace) {
            return $this->colorTitle($output);
        } else {
            if ($output) {
                $output .= PHP_EOL;
            }

            return $this->colorTitle($output.$this->calledFrom().PHP_EOL);
        }
    }

    public function parserPlugins(array $plugins)
    {
        $return = array();

        foreach ($plugins as $index => $plugin) {
            foreach (self::$parser_plugin_whitelist as $whitelist) {
                if ($plugin instanceof $whitelist) {
                    $return[] = $plugin;
                    continue 2;
                }
            }
        }

        return $return;
    }

    protected function calledFrom()
    {
        $output = '';

        if (isset($this->callee['file'])) {
            $output .= 'Called from '.$this->ideLink($this->callee['file'], $this->callee['line']);
        }

        $caller = '';

        if (isset($this->previous_caller['class'])) {
            $caller .= $this->previous_caller['class'];
        }
        if (isset($this->previous_caller['type'])) {
            $caller .= $this->previous_caller['type'];
        }
        if (isset($this->previous_caller['function'])
            && !in_array($this->previous_caller['function'], array('include', 'include_once', 'require', 'require_once'))
        ) {
            $caller .= $this->previous_caller['function'].'()';
        }

        if ($caller) {
            $output .= ' ['.$caller.']';
        }

        return $output;
    }

    public function ideLink($file, $line)
    {
        return $this->escape(Kint::shortenPath($file)).':'.$line;
    }

    protected function getPlugin(array $plugins, array $hints)
    {
        if ($plugins = $this->matchPlugins($plugins, $hints)) {
            $plugin = end($plugins);

            if (!isset($this->plugin_objs[$plugin])) {
                $this->plugin_objs[$plugin] = new $plugin($this);
            }

            return $this->plugin_objs[$plugin];
        }
    }

    public function escape($string, $encoding = false)
    {
        return $string;
    }
}
