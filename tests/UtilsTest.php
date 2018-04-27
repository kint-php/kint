<?php

namespace Kint\Test;

use Kint\Utils;
use PHPUnit_Framework_TestCase;
use ReflectionMethod;

class UtilsTest extends PHPUnit_Framework_TestCase
{
    protected $composer_stash;
    protected $installed_stash;
    protected $composer_test_dir;

    public function testConstruct()
    {
        $u = new ReflectionMethod('Kint\\Utils', '__construct');
        $this->assertTrue($u->isPrivate());
    }

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

    public function setUp()
    {
        parent::setUp();

        if (getenv('KINT_FILE')) {
            $this->composer_test_dir = dirname(__DIR__);
        } else {
            $this->composer_test_dir = KINT_DIR;
        }

        $this->composer_stash = file_get_contents($this->composer_test_dir.'/composer.json');
        $this->installed_stash = file_get_contents($this->composer_test_dir.'/vendor/composer/installed.json');
    }

    public function tearDown()
    {
        parent::tearDown();

        if ($this->composer_stash) {
            file_put_contents($this->composer_test_dir.'/composer.json', $this->composer_stash);
            file_put_contents($this->composer_test_dir.'/vendor/composer/installed.json', $this->installed_stash);
            $this->composer_stash = null;
            $this->installed_stash = null;
            if (file_exists($this->composer_test_dir.'/composer/installed.json')) {
                unlink($this->composer_test_dir.'/composer/installed.json');
            }
            if (file_exists($this->composer_test_dir.'/composer')) {
                rmdir($this->composer_test_dir.'/composer');
            }
        }
    }

    /**
     * This is a flimsy test but it's as good as it gets without altering
     * composer.json mid-test without a proper setup/teardown in place.
     *
     * @covers \Kint\Utils::composerGetExtras
     */
    public function testComposerGetExtras()
    {
        file_put_contents($this->composer_test_dir.'/composer.json', json_encode(array(
            'extra' => array(
                'kint' => array('test' => 'data'),
            ),
        )));

        if (getenv('KINT_FILE')) {
            $this->assertEquals(array(), Utils::composerGetExtras('kint'));

            return;
        } else {
            $this->assertEquals(array('test' => 'data'), Utils::composerGetExtras('kint'));
        }

        mkdir($this->composer_test_dir.'/composer');
        unlink($this->composer_test_dir.'/vendor/composer/installed.json');

        file_put_contents($this->composer_test_dir.'/composer/installed.json', json_encode(array(
            array(
                'extra' => array(
                    'kint' => array('more' => 'test', 'data'),
                ),
            ),
            array(
                'extra' => array(
                    'kint' => array('test' => 'ing'),
                ),
            ),
        )));

        $this->assertEquals(array('more' => 'test', 'data', 'test' => 'ing'), Utils::composerGetExtras('kint'));
    }

    public function traceProvider()
    {
        $bt = debug_backtrace(true);
        $bad_bt_1 = $bt;
        $bad_bt_1[0]['test'] = 'woot';
        $bad_bt_2 = $bt;
        $bad_bt_2[0]['function'] = 1234;

        return array(
            'empty' => array(
                'trace' => array(),
                'expect' => false,
            ),
            'backtrace' => array(
                'trace' => $bt,
                'expect' => true,
            ),
            'bad backtrace, extra key' => array(
                'trace' => $bad_bt_1,
                'expect' => false,
            ),
            'bad backtrace, wrong type' => array(
                'trace' => $bad_bt_2,
                'expect' => false,
            ),
            'mythical' => array(
                'trace' => array(
                    array(
                        'function' => 'mythical_internal_function_with_no_args_that_results_in_a_backtrace',
                        'file' => __FILE__,
                        'line' => 1,
                    ),
                ),
                'expect' => true,
            ),
            'normal array' => array(
                'trace' => array(1, 2, 3),
                'expect' => false,
            ),
        );
    }

    /**
     * @dataProvider traceProvider
     * @covers \Kint\Utils::isTrace
     */
    public function testIsTrace(array $trace, $expected)
    {
        $this->assertEquals($expected, Utils::isTrace($trace));
    }

    public function frameProvider()
    {
        return array(
            'function match' => array(
                'frame' => array(
                    'function' => 'testAWeirdFunctionName',
                ),
                'matches' => array('testaweirdfunctionname'),
                'expected' => true,
            ),
            'function no match denormalized' => array(
                'frame' => array(
                    'function' => 'testAWeirdFunctionName',
                ),
                'matches' => array('testAWeirdFunctionName'),
                'expected' => false,
            ),
            'function no match method' => array(
                'frame' => array(
                    'function' => 'testAWeirdFunctionName',
                ),
                'matches' => array(array('test', 'testaweirdfunctionname')),
                'expected' => false,
            ),
            'method no match function' => array(
                'frame' => array(
                    'function' => 'testAWeirdFunctionName',
                    'class' => 'test',
                ),
                'matches' => array('testAWeirdFunctionName'),
                'expected' => false,
            ),
            'method match' => array(
                'frame' => array(
                    'function' => 'testAWeirdFunctionName',
                    'class' => 'test',
                ),
                'matches' => array(array('test', 'testaweirdfunctionname')),
                'expected' => true,
            ),
        );
    }

    /**
     * @dataProvider frameProvider
     * @covers \Kint\Utils::traceFrameIsListed
     */
    public function testTraceFrameIsListed(array $frame, array $matches, $expected)
    {
        $this->assertEquals($expected, Utils::traceFrameIsListed($frame, $matches));
    }

    /**
     * @covers \Kint\Utils::normalizeAliases
     */
    public function testNormalizeAliases()
    {
        $input = array(
            'CamelCaseFunction',
            'snake_case_function',
            'One_of_the_FunctionsMyColleaguesMADE__',
            'stringThatCan\'tBeAfunction',
            'another string that can not be a function',
            array('clASs', 'meThod'),
            array($this, 'meThod'),
            array('a', 'b', 'c'),
            array('\\big\\long\\class\\name', 'method'),
        );

        $expected = array(
            'camelcasefunction',
            'snake_case_function',
            'one_of_the_functionsmycolleaguesmade__',
            array('class', 'method'),
            array('big\\long\\class\\name', 'method'),
        );

        Utils::normalizeAliases($input);

        $this->assertEquals($expected, $input);
    }
}
