<?php

class Kint_Renderer_Plain extends Kint_Renderer_Text
{
    public static $pre_render_sources = array(
        'script' => array(),
        'style' => array(
            array('Kint_Renderer_Plain', 'renderCss'),
        ),
        'raw' => array(),
    );

    /**
     * Path to the CSS file to load by default.
     *
     * @var string
     */
    public static $theme = 'plain.css';

    /**
     * Output htmlentities instead of utf8.
     *
     * @var bool
     */
    public static $disable_utf8 = false;

    protected static $been_run = false;

    protected $mod_return = false;
    protected $file_link_format = false;

    public function __construct(array $params = array())
    {
        parent::__construct($params);

        if (!empty($params['modifiers']) && in_array('@', $params['modifiers'])) {
            $this->mod_return = true;
        }
        if (isset($params['settings']['file_link_format'])) {
            $this->file_link_format = $params['settings']['file_link_format'];
        }
    }

    protected function utf8_to_htmlentity($string)
    {
        return str_replace(
            array('┌', '═', '┐', '│', '└', '─', '┘'),
            array('&#9484;', '&#9552;', '&#9488;', '&#9474;', '&#9492;', '&#9472;', '&#9496;'),
            $string
        );
    }

    public function colorValue($string)
    {
        return '<i>'.$string.'</i>';
    }

    public function colorType($string)
    {
        return '<b>'.$string.'</b>';
    }

    public function colorTitle($string)
    {
        return '<u>'.$string.'</u>';
    }

    public function renderTitle(Kint_Object $o)
    {
        if (self::$disable_utf8) {
            return $this->utf8_to_htmlentity(parent::renderTitle($o));
        } else {
            return parent::renderTitle($o);
        }
    }

    protected static function renderCss()
    {
        if (file_exists(KINT_DIR.'/resources/compiled/'.self::$theme)) {
            return file_get_contents(KINT_DIR.'/resources/compiled/'.self::$theme);
        } else {
            return file_get_contents(self::$theme);
        }
    }

    public function preRender()
    {
        $output = '';

        if (!self::$been_run || $this->mod_return) {
            foreach (self::$pre_render_sources as $type => $values) {
                $contents = '';
                foreach ($values as $v) {
                    if (is_callable($v)) {
                        $contents .= call_user_func($v, $this);
                    } elseif (is_string($v)) {
                        $contents .= $v;
                    }
                }

                if (!strlen($contents)) {
                    continue;
                }

                switch ($type) {
                    case 'script':
                        $output .= '<script class="kint-script">'.$contents.'</script>';
                        break;
                    case 'style':
                        $output .= '<style class="kint-style">'.$contents.'</style>';
                        break;
                    default:
                        $output .= $contents;
                }
            }

            if (!$this->mod_return) {
                self::$been_run = true;
            }
        }

        return $output.'<div class="kint-plain">';
    }

    public function postRender()
    {
        if (self::$disable_utf8) {
            return $this->utf8_to_htmlentity(parent::postRender()).'</div>';
        } else {
            return parent::postRender().'</div>';
        }
    }

    public function ideLink($file, $line)
    {
        $shortenedPath = Kint_Object_Blob::escape(Kint::shortenPath($file));
        if (!$this->file_link_format) {
            return $shortenedPath.':'.$line;
        }

        $ideLink = Kint::getIdeLink($file, $line);
        $class = (strpos($ideLink, 'http://') === 0) ? 'class="kint-ide-link" ' : '';

        return "<a {$class}href=\"{$ideLink}\">{$shortenedPath}:{$line}</a>";
    }
}
