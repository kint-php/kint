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
}
