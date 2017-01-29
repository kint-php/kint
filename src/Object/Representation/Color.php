<?php

class Kint_Object_Representation_Color extends Kint_Object_Representation
{
    public $r = 0;
    public $g = 0;
    public $b = 0;
    public $a = 1;
    public $variant = null;
    public $implicit_label = true;
    public $hints = array('color');

    const COLOR_NAME = 1;
    const COLOR_HEX_3 = 2;
    const COLOR_HEX_6 = 3;
    const COLOR_RGB = 4;
    const COLOR_RGBA = 5;
    const COLOR_HSL = 6;
    const COLOR_HSLA = 7;
    const COLOR_HEX_4 = 8;
    const COLOR_HEX_8 = 9;

    public function getColor($variant = null)
    {
        switch ($variant) {
            case self::COLOR_NAME:
                $hex = sprintf('%02x%02x%02x', $this->r, $this->g, $this->b);

                return array_search($hex, Kint_Object_Color::$color_map);
            case self::COLOR_HEX_3:
                if ($this->r % 0x11 === 0 && $this->g % 0x11 === 0 && $this->b % 0x11 === 0) {
                    return sprintf('#%1X%1X%1X', round($this->r / 0x11), round($this->g / 0x11), round($this->b / 0x11));
                } else {
                    return false;
                }
            case self::COLOR_HEX_6:
                return sprintf('#%02X%02X%02X', $this->r, $this->g, $this->b);
            case self::COLOR_RGB:
                return sprintf('rgb(%d, %d, %d)', $this->r, $this->g, $this->b);
            case self::COLOR_RGBA:
                return sprintf('rgba(%d, %d, %d, %s)', $this->r, $this->g, $this->b, round($this->a, 4));
            case self::COLOR_HSL:
                $val = Kint_Object_Color::rgbToHsl($this->r, $this->g, $this->b);

                return vsprintf('hsl(%d, %d%%, %d%%)', $val);
            case self::COLOR_HSLA:
                $val = Kint_Object_Color::rgbToHsl($this->r, $this->g, $this->b);

                return sprintf('hsla(%d, %d%%, %d%%, %s)', $val[0], $val[1], $val[2], round($this->a, 4));
            case self::COLOR_HEX_4:
                if ($this->r % 0x11 === 0 && $this->g % 0x11 === 0 && $this->b % 0x11 === 0 && ($this->a * 255) % 0x11 === 0) {
                    return sprintf('#%1X%1X%1X%1X', round($this->r / 0x11), round($this->g / 0x11), round($this->b / 0x11), round($this->a * 0xF));
                } else {
                    return false;
                }
            case self::COLOR_HEX_8:
                return sprintf('#%02X%02X%02X%02X', $this->r, $this->g, $this->b, round($this->a * 0xFF));
            case null:
                return $this->contents;
        }

        return false;
    }

    public function __construct($value)
    {
        parent::__construct('Color');

        $this->contents = $value;
        $this->setValues($value);
    }

    public function hasAlpha($variant = null)
    {
        if ($variant === null) {
            $variant = $this->variant;
        }

        switch ($variant) {
            case self::COLOR_NAME:
                return $this->a !== 1;
            case self::COLOR_RGBA:
            case self::COLOR_HSLA:
            case self::COLOR_HEX_4:
            case self::COLOR_HEX_8:
                return true;
            default:
                return false;
        }
    }

    protected function setValues($value)
    {
        $value = strtolower(trim($value));
        // Find out which variant of color input it is
        if (isset(Kint_Object_Color::$color_map[$value])) {
            $variant = self::COLOR_NAME;
        } elseif (substr($value, 0, 1) === '#') {
            $value = substr($value, 1);

            if (dechex(hexdec($value)) !== $value) {
                return;
            }

            switch (strlen($value)) {
                case 3:
                    $variant = self::COLOR_HEX_3;
                    break;
                case 6:
                    $variant = self::COLOR_HEX_6;
                    break;
                case 4:
                    $variant = self::COLOR_HEX_4;
                    break;
                case 8:
                    $variant = self::COLOR_HEX_8;
                    break;
            }
        } else {
            if (!preg_match('/^((?:rgb|hsl)a?)\s*\(([0-9\.%,\s]+)\)$/i', $value, $match)) {
                return;
            }

            switch ($match[1]) {
                case 'rgb':
                    $variant = self::COLOR_RGB;
                    break;
                case 'rgba':
                    $variant = self::COLOR_RGBA;
                    break;
                case 'hsl':
                    $variant = self::COLOR_HSL;
                    break;
                case 'hsla':
                    $variant = self::COLOR_HSLA;
                    break;
            }

            $value = explode(',', $match[2]);

            if ($this->hasAlpha($variant)) {
                if (count($value) !== 4) {
                    return;
                }
            } elseif (count($value) !== 3) {
                return;
            }

            foreach ($value as $i => &$color) {
                $color = trim($color);

                if (strpos($color, '%') !== false) {
                    $color = str_replace('%', '', $color);

                    if ($i === 3) {
                        $color = $color / 100;
                    } elseif (in_array($variant, array(self::COLOR_RGB, self::COLOR_RGBA))) {
                        $color = round($color / 100 * 255);
                    } elseif ($i === 0 && in_array($variant, array(self::COLOR_HSL, self::COLOR_HSLA))) {
                        $color = $color / 100 * 360;
                    }
                }

                if ($i === 0 && in_array($variant, array(self::COLOR_HSL, self::COLOR_HSLA))) {
                    $color = ($color % 360 + 360) % 360;
                }
            }
        }

        // Assign the correct properties based on the variant
        switch ($variant) {
            case self::COLOR_HEX_4:
                $this->a = hexdec($value[3]) / 0xF;
                // Fallthrough
            case self::COLOR_HEX_3:
                $this->r = hexdec($value[0]) * 0x11;
                $this->g = hexdec($value[1]) * 0x11;
                $this->b = hexdec($value[2]) * 0x11;
                break;
            case self::COLOR_NAME:
                $value = Kint_Object_Color::$color_map[$value].'FF';
            case self::COLOR_HEX_8:
                $this->a = hexdec(substr($value, 6, 2)) / 0xFF;
                // Fallthrough
            case self::COLOR_HEX_6:
                $value = str_split($value, 2);
                $this->r = hexdec($value[0]);
                $this->g = hexdec($value[1]);
                $this->b = hexdec($value[2]);
                break;
            case self::COLOR_RGBA:
                $this->a = $value[3];
                // Fallthrough
            case self::COLOR_RGB:
                list($this->r, $this->g, $this->b) = $value;
                break;
            case self::COLOR_HSLA:
                $this->a = $value[3];
                // Fallthrough
            case self::COLOR_HSL:
                $value = Kint_Object_Color::hslToRgb($value[0], $value[1], $value[2]);
                list($this->r, $this->g, $this->b) = $value;
                break;
        }

        // If something has gone horribly wrong
        if ($this->r > 0xFF || $this->g > 0xFF || $this->b > 0xFF || $this->a > 1) {
            $this->variant = null;
        } else {
            $this->variant = $variant;
        }
    }
}
