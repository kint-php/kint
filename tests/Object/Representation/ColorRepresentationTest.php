<?php

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

namespace Kint\Test\Object\Representation;

use Kint\Object\Representation\ColorRepresentation;
use Kint\Test\KintTestCase;

class ColorRepresentationTest extends KintTestCase
{
    protected function setUp()
    {
        parent::setUp();

        ColorRepresentation::$color_map['thatcolorbehindyoureyelids'] = 'FEDCB';
    }

    public function colorProvider()
    {
        return [
            'name 1' => [
                'red',
                0xFF,
                0,
                0,
                1.0,
                ColorRepresentation::COLOR_NAME,
            ],
            'name 2' => [
                'fuchsia',
                0xFF,
                0,
                0xFF,
                1.0,
                ColorRepresentation::COLOR_NAME,
            ],
            'name 3' => [
                'BLUE',
                0,
                0,
                0xFF,
                1.0,
                ColorRepresentation::COLOR_NAME,
            ],
            'name 4' => [
                ' white ',
                0xFF,
                0xFF,
                0xFF,
                1.0,
                ColorRepresentation::COLOR_NAME,
            ],
            'name 5' => [
                'transparent',
                0,
                0,
                0,
                0.0,
                ColorRepresentation::COLOR_NAME,
            ],
            'hex-3' => [
                '#FED',
                0xFF,
                0xEE,
                0xDD,
                1.0,
                ColorRepresentation::COLOR_HEX_3,
            ],
            'hex-6' => [
                '#F00BA8',
                0xF0,
                0x0B,
                0xA8,
                1.0,
                ColorRepresentation::COLOR_HEX_6,
            ],
            'hex-6 leading nulls' => [
                '#0000FF',
                0x00,
                0x00,
                0xFF,
                1.0,
                ColorRepresentation::COLOR_HEX_6,
            ],
            'rgb' => [
                'rgb(123, 45, 67)',
                123,
                45,
                67,
                1.0,
                ColorRepresentation::COLOR_RGB,
            ],
            'rgb alpha' => [
                'rgb(123, 45, 67, 0.5)',
                123,
                45,
                67,
                0.5,
                ColorRepresentation::COLOR_RGB,
            ],
            'rgb alpha percent' => [
                'rgb(50%, 60%, 70%, 50%)',
                128,
                153,
                179,
                0.5,
                ColorRepresentation::COLOR_RGB,
            ],
            'rgb alpha whitespace' => [
                ' rgb ( 123 45 67 / 0.5 ) ',
                123,
                45,
                67,
                0.5,
                ColorRepresentation::COLOR_RGB,
            ],
            'rgb uppercase' => [
                'RGB(1, 2, 3)',
                1,
                2,
                3,
                1.0,
                ColorRepresentation::COLOR_RGB,
            ],
            'rgba' => [
                'rgba(123, 45, 67, 0.5)',
                123,
                45,
                67,
                0.5,
                ColorRepresentation::COLOR_RGBA,
            ],
            'rgba without alpha' => [
                'rgba(123, 45, 67)',
                123,
                45,
                67,
                1.0,
                ColorRepresentation::COLOR_RGBA,
            ],
            'hsl' => [
                'hsl(120, 100%, 50%)',
                0,
                0xFF,
                0,
                1.0,
                ColorRepresentation::COLOR_HSL,
            ],
            'hsl alpha' => [
                'hsl(120, 100%, 50%, 0.35)',
                0,
                0xFF,
                0,
                0.35,
                ColorRepresentation::COLOR_HSL,
            ],
            'hsl alpha percent' => [
                'hsl(120, 100%, 50%, 35%)',
                0,
                0xFF,
                0,
                0.35,
                ColorRepresentation::COLOR_HSL,
            ],
            'hsl alpha whitespace' => [
                ' hsl ( 120 100% 50% / 0.35 ) ',
                0,
                0xFF,
                0,
                0.35,
                ColorRepresentation::COLOR_HSL,
            ],
            'hsl hue overflow' => [
                'hsl(480, 100%, 50%)',
                0,
                0xFF,
                0,
                1.0,
                ColorRepresentation::COLOR_HSL,
            ],
            'hsl hue underflow' => [
                'hsl(-240, 100%, 50%)',
                0,
                0xFF,
                0,
                1.0,
                ColorRepresentation::COLOR_HSL,
            ],
            'hsla' => [
                'hsla(120, 100%, 50%, 35%)',
                0,
                0xFF,
                0,
                0.35,
                ColorRepresentation::COLOR_HSLA,
            ],
            'hsla without alpha' => [
                'hsla(120, 100%, 50%)',
                0,
                0xFF,
                0,
                1.0,
                ColorRepresentation::COLOR_HSLA,
            ],
            'hex-4' => [
                '#FA84',
                0xFF,
                0xAA,
                0x88,
                0x4 / 0xF,
                ColorRepresentation::COLOR_HEX_4,
            ],
            'hex-8' => [
                '#FEDCBA98',
                0xFE,
                0xDC,
                0xBA,
                0x98 / 0xFF,
                ColorRepresentation::COLOR_HEX_8,
            ],
            'invalid' => [
                "Wait a minute... This isn't a color!",
                0,
                0,
                0,
                1.0,
                null,
            ],
            'invalid hex' => [
                '#colorsarecool',
                0,
                0,
                0,
                1.0,
                null,
            ],
            'invalid hex length' => [
                '#FEDCB',
                0,
                0,
                0,
                1.0,
                null,
            ],
            'invalid func 1' => [
                'rgb()',
                0,
                0,
                0,
                1.0,
                null,
            ],
            'invalid func 2' => [
                'rgb(1, 2, 3, 4, 5)',
                0,
                0,
                0,
                1.0,
                null,
            ],
            'invalid range' => [
                'rgb(300, 300, 300)',
                0,
                0,
                0,
                1.0,
                null,
            ],
            'invalid alpha range' => [
                'rgb(0, 0, 0, 2)',
                0,
                0,
                0,
                1.0,
                null,
            ],
            'invalid hsl range' => [
                'hsl(0, 120%, 120%)',
                0,
                0,
                0,
                1.0,
                null,
            ],
            'invalid hsl alpha range' => [
                'hsl(0, 0%, 0%, 2)',
                0,
                0,
                0,
                1.0,
                null,
            ],
            'invalid name' => [
                'thatcolorbehindyoureyelids',
                0,
                0,
                0,
                1.0,
                null,
            ],
        ];
    }

    /**
     * @covers \Kint\Object\Representation\ColorRepresentation::__construct
     * @covers \Kint\Object\Representation\ColorRepresentation::setValues
     * @covers \Kint\Object\Representation\ColorRepresentation::setValuesFromFunction
     * @covers \Kint\Object\Representation\ColorRepresentation::setValuesFromHex
     * @dataProvider colorProvider
     *
     * @param string   $input
     * @param int      $r
     * @param int      $g
     * @param int      $b
     * @param float    $a
     * @param null|int $variant
     */
    public function testConstruct($input, $r, $g, $b, $a, $variant)
    {
        $rep = new ColorRepresentation($input);
        $this->assertSame($r, $rep->r);
        $this->assertSame($g, $rep->g);
        $this->assertSame($b, $rep->b);
        $this->assertSame($a, $rep->a);
        $this->assertSame($variant, $rep->variant);
    }

    /**
     * @covers \Kint\Object\Representation\ColorRepresentation::getColor
     */
    public function testGetColor()
    {
        $rep = new ColorRepresentation("This isn't a color");
        $this->assertFalse($rep->getColor());

        $rep = new ColorRepresentation('#FEDC');
        $this->assertSame('#FEDC', $rep->getColor());
        $this->assertFalse($rep->getColor(ColorRepresentation::COLOR_NAME));
        $this->assertSame('#FED', $rep->getColor(ColorRepresentation::COLOR_HEX_3));
        $this->assertSame('#FFEEDD', $rep->getColor(ColorRepresentation::COLOR_HEX_6));
        $this->assertSame('rgb(255, 238, 221, 0.8)', $rep->getColor(ColorRepresentation::COLOR_RGB));
        $this->assertSame('rgba(255, 238, 221, 0.8)', $rep->getColor(ColorRepresentation::COLOR_RGBA));
        $this->assertSame('hsl(30, 100%, 93%, 0.8)', $rep->getColor(ColorRepresentation::COLOR_HSL));
        $this->assertSame('hsla(30, 100%, 93%, 0.8)', $rep->getColor(ColorRepresentation::COLOR_HSLA));
        $this->assertSame('#FEDC', $rep->getColor(ColorRepresentation::COLOR_HEX_4));
        $this->assertSame('#FFEEDDCC', $rep->getColor(ColorRepresentation::COLOR_HEX_8));

        $rep = new ColorRepresentation('#FED');
        $this->assertSame('#FED', $rep->getColor());
        $this->assertFalse($rep->getColor(ColorRepresentation::COLOR_NAME));
        $this->assertSame('#FED', $rep->getColor(ColorRepresentation::COLOR_HEX_3));
        $this->assertSame('#FFEEDD', $rep->getColor(ColorRepresentation::COLOR_HEX_6));
        $this->assertSame('rgb(255, 238, 221)', $rep->getColor(ColorRepresentation::COLOR_RGB));
        $this->assertSame('rgba(255, 238, 221, 1)', $rep->getColor(ColorRepresentation::COLOR_RGBA));
        $this->assertSame('hsl(30, 100%, 93%)', $rep->getColor(ColorRepresentation::COLOR_HSL));
        $this->assertSame('hsla(30, 100%, 93%, 1)', $rep->getColor(ColorRepresentation::COLOR_HSLA));
        $this->assertSame('#FEDF', $rep->getColor(ColorRepresentation::COLOR_HEX_4));
        $this->assertSame('#FFEEDDFF', $rep->getColor(ColorRepresentation::COLOR_HEX_8));

        $rep = new ColorRepresentation('rgba(1, 2, 3, 0.4)');
        $this->assertFalse($rep->getColor(ColorRepresentation::COLOR_HEX_3));
        $this->assertFalse($rep->getColor(ColorRepresentation::COLOR_HEX_4));
    }

    /**
     * @covers \Kint\Object\Representation\ColorRepresentation::hasAlpha
     */
    public function testHasAlpha()
    {
        $rep = new ColorRepresentation('#FEDC');
        $this->assertTrue($rep->hasAlpha());
        $this->assertTrue($rep->hasAlpha(ColorRepresentation::COLOR_NAME));
        $this->assertFalse($rep->hasAlpha(ColorRepresentation::COLOR_HEX_3));
        $this->assertFalse($rep->hasAlpha(ColorRepresentation::COLOR_HEX_6));
        $this->assertTrue($rep->hasAlpha(ColorRepresentation::COLOR_RGB));
        $this->assertTrue($rep->hasAlpha(ColorRepresentation::COLOR_HSL));
        $this->assertTrue($rep->hasAlpha(ColorRepresentation::COLOR_RGBA));
        $this->assertTrue($rep->hasAlpha(ColorRepresentation::COLOR_HSLA));
        $this->assertTrue($rep->hasAlpha(ColorRepresentation::COLOR_HEX_4));
        $this->assertTrue($rep->hasAlpha(ColorRepresentation::COLOR_HEX_8));

        $rep = new ColorRepresentation('#FED');
        $this->assertFalse($rep->hasAlpha());
        $this->assertFalse($rep->hasAlpha(ColorRepresentation::COLOR_NAME));
        $this->assertFalse($rep->hasAlpha(ColorRepresentation::COLOR_HEX_3));
        $this->assertFalse($rep->hasAlpha(ColorRepresentation::COLOR_HEX_6));
        $this->assertFalse($rep->hasAlpha(ColorRepresentation::COLOR_RGB));
        $this->assertFalse($rep->hasAlpha(ColorRepresentation::COLOR_HSL));
        $this->assertTrue($rep->hasAlpha(ColorRepresentation::COLOR_RGBA));
        $this->assertTrue($rep->hasAlpha(ColorRepresentation::COLOR_HSLA));
        $this->assertTrue($rep->hasAlpha(ColorRepresentation::COLOR_HEX_4));
        $this->assertTrue($rep->hasAlpha(ColorRepresentation::COLOR_HEX_8));
    }

    /**
     * @covers \Kint\Object\Representation\ColorRepresentation::hslToRgb
     * @covers \Kint\Object\Representation\ColorRepresentation::hueToRgb
     */
    public function testHslToRgb()
    {
        $this->assertSame([0, 255, 0], ColorRepresentation::hslToRgb(120, 100, 50));
        $this->assertSame([255, 255, 255], ColorRepresentation::hslToRgb(120, 100, 100));
        $this->assertSame([0, 0, 0], ColorRepresentation::hslToRgb(120, 100, 0));
        $this->assertSame([255, 0, 0], ColorRepresentation::hslToRgb(0, 100, 50));
        $this->assertSame([255, 128, 128], ColorRepresentation::hslToRgb(0, 100, 75));
        $this->assertSame([255, 0, 0], ColorRepresentation::hslToRgb(360, 100, 50));

        // Hue between 50% and 66%
        $this->assertSame([0, 170, 255], ColorRepresentation::hslToRgb(200, 100, 50));
    }

    /**
     * @covers \Kint\Object\Representation\ColorRepresentation::rgbToHsl
     */
    public function testRgbToHsl()
    {
        $this->assertSame([0.0, 100.0, 50.0], ColorRepresentation::rgbToHsl(255, 0, 0));
        $this->assertSame([120.0, 100.0, 50.0], ColorRepresentation::rgbToHsl(0, 255, 0));
        $this->assertSame([240.0, 100.0, 50.0], ColorRepresentation::rgbToHsl(0, 0, 255));
        $this->assertSame([0.0, 0.0, 0.0], ColorRepresentation::rgbToHsl(0, 0, 0));
        $this->assertSame([0.0, 0.0, 100.0], ColorRepresentation::rgbToHsl(255, 255, 255));

        // Lightness below half
        $this->assertSame([30.0, 100.0, 10.0], ColorRepresentation::rgbToHsl(51, 25.5, 0));

        // Hue below 0
        $this->assertSame([300.0, 100.0, 50.0], ColorRepresentation::rgbToHsl(255, 0, 255));
    }

    /**
     * @covers \Kint\Object\Representation\ColorRepresentation::rgbToHsl
     * @expectedException \InvalidArgumentException
     */
    public function testRgbToHslInputLow()
    {
        ColorRepresentation::rgbToHsl(0, 0, -1);
    }

    /**
     * @covers \Kint\Object\Representation\ColorRepresentation::rgbToHsl
     * @expectedException \InvalidArgumentException
     */
    public function testRgbToHslInputHigh()
    {
        ColorRepresentation::rgbToHsl(0, 0, 256);
    }

    /**
     * @covers \Kint\Object\Representation\ColorRepresentation::hslToRgb
     * @expectedException \InvalidArgumentException
     */
    public function testHslToRgbHueHigh()
    {
        ColorRepresentation::hslToRgb(361, 0, 0);
    }

    /**
     * @covers \Kint\Object\Representation\ColorRepresentation::hslToRgb
     * @expectedException \InvalidArgumentException
     */
    public function testHslToRgbSatLightHigh()
    {
        ColorRepresentation::hslToRgb(0, 101, 101);
    }

    /**
     * @covers \Kint\Object\Representation\ColorRepresentation::hslToRgb
     * @expectedException \InvalidArgumentException
     */
    public function testHslToRgbInputLow()
    {
        ColorRepresentation::hslToRgb(-1, 0, 0);
    }
}
