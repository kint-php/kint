<?php

namespace Kint\Renderer;

use Kint\Kint;
use Kint\Object\BasicObject;
use Kint\Object\BlobObject;

class PlainRenderer extends TextRenderer
{
    public static $pre_render_sources = array(
        'script' => array(
            array('Kint\\Renderer\\Text\\MicrotimePlugin', 'renderJs'),
        ),
        'style' => array(
            array('Kint\\Renderer\\PlainRenderer', 'renderCss'),
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

    protected function utf8ToHtmlentity($string)
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

    public function renderTitle(BasicObject $o)
    {
        if (self::$disable_utf8) {
            return $this->utf8ToHtmlentity(parent::renderTitle($o));
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

        if (!self::$been_run || $this->return_mode) {
            foreach (self::$pre_render_sources as $type => $values) {
                $contents = '';
                foreach ($values as $v) {
                    $contents .= call_user_func($v, $this);
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

            if (!$this->return_mode) {
                self::$been_run = true;
            }
        }

        return $output.'<div class="kint-plain">';
    }

    public function postRender()
    {
        if (self::$disable_utf8) {
            return $this->utf8ToHtmlentity(parent::postRender()).'</div>';
        } else {
            return parent::postRender().'</div>';
        }
    }

    public function ideLink($file, $line)
    {
        $path = $this->escape(Kint::shortenPath($file)).':'.$line;
        $ideLink = Kint::getIdeLink($file, $line);

        if (!$ideLink) {
            return $path;
        }

        $class = '';

        if (preg_match('/https?:\/\//i', $ideLink)) {
            $class = 'class="kint-ide-link" ';
        }

        return '<a '.$class.'href="'.$this->escape($ideLink).'">'.$path.'</a>';
    }

    public function escape($string, $encoding = false)
    {
        if ($encoding === false) {
            $encoding = BlobObject::detectEncoding($string);
        }

        $original_encoding = $encoding;

        if ($encoding === false || $encoding === 'ASCII') {
            $encoding = 'UTF-8';
        }

        $string = htmlspecialchars($string, ENT_NOQUOTES, $encoding);

        // this call converts all non-ASCII characters into numeirc htmlentities
        if (function_exists('mb_encode_numericentity') && $original_encoding !== 'ASCII') {
            $string = mb_encode_numericentity($string, array(0x80, 0xffff, 0, 0xffff), $encoding);
        }

        return $string;
    }
}
