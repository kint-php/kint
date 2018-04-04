<?php

namespace Kint\Test;

use Kint\Utils;
use PHPUnit_Framework_TestCase;

class UtilsTest extends PHPUnit_Framework_TestCase
{
    public function humanReadableBytesProvider()
    {
        return array(
            '1 byte' => array(
                1,
                array(
                    'value' => 1.0,
                    'unit' => 'B',
                ),
            ),
            '1023 bytes' => array(
                1023,
                array(
                    'value' => 1023.0,
                    'unit' => 'B',
                ),
            ),
            '1024 bytes' => array(
                1024,
                array(
                    'value' => 1.0,
                    'unit' => 'KB',
                ),
            ),
            '1 meg' => array(
                pow(2, 20),
                array(
                    'value' => 1.0,
                    'unit' => 'MB',
                ),
            ),
            '1 gig' => array(
                pow(2, 30),
                array(
                    'value' => 1.0,
                    'unit' => 'GB',
                ),
            ),
            '1 tig' => array(
                pow(2, 40),
                array(
                    'value' => 1.0,
                    'unit' => 'TB',
                ),
            ),
            '>1 tig' => array(
                pow(2, 50),
                array(
                    'value' => 1024.0,
                    'unit' => 'TB',
                ),
            ),
            'halfway' => array(
                15000,
                array(
                    'value' => 15000 / 1024,
                    'unit' => 'KB',
                ),
            ),
        );
    }

    /**
     * @covers \Kint\Utils::getHumanReadableBytes
     * @dataProvider humanReadableBytesProvider
     */
    public function testGetHumanReadableBytes($input, $expect)
    {
        $this->assertSame($expect, Utils::getHumanReadableBytes($input));
    }

    public function sequentialArrayProvider()
    {
        return array(
            'sequential array' => array(
                array(1, 2, 3),
                true,
            ),
            'explicit sequential array' => array(
                array(0 => 1, 1 => 2, 2 => 3),
                true,
            ),
            'arrays start at 1' => array(
                array(1 => 1, 2 => 2, 3 => 3),
                false,
            ),
            'wrong order' => array(
                array(0 => 1, 2 => 2, 1 => 3),
                false,
            ),
            'string keys' => array(
                array(0 => 1, 1 => 2, 'two' => 3),
                false,
            ),
            'string int keys' => array(
                array('0' => 1, '1' => 2, '2' => 3),
                true,
            ),
            'padded string int keys' => array(
                array('00' => 1, '01' => 2, '02' => 3),
                false,
            ),
        );
    }

    /**
     * @covers \Kint\Utils::isSequential
     * @dataProvider sequentialArrayProvider
     */
    public function testIsSequential($input, $expect)
    {
        $this->assertSame($expect, Utils::isSequential($input));
    }
}
