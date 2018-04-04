<?php

namespace Kint\Test;

use Kint\Kint;
use Kint\Test\Fixtures\Php56TestClass;
use Kint\Test\Fixtures\TestClass;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class KintTest extends KintTestCase
{
    /**
     * @covers \Kint\Kint::settings
     */
    public function testSettings()
    {
        $r = new ReflectionClass('Kint');

        $props = $r->getProperties(ReflectionProperty::IS_STATIC);
        $props_array = array();
        foreach ($props as $prop) {
            if ($prop->isPublic() && $prop->isStatic()) {
                $props_array[$prop->getName()] = $prop->getValue();
            }
        }

        $props = $r->getStaticProperties();

        $this->assertEquals($props_array, $stash = Kint::settings());

        $props_array['enabled_mode'] = Kint::$enabled_mode = false;
        $props_array['file_link_format'] = Kint::$file_link_format = 'linky';
        $props_array['app_root_dirs'] = Kint::$app_root_dirs = array('/' => '<fsroot>');
        $props_array['max_depth'] = Kint::$max_depth = 0;
        $props_array['expanded'] = Kint::$expanded = true;
        $props_array['cli_detection'] = Kint::$cli_detection = false;

        $this->assertNotEquals($props, $r->getStaticProperties());
        $this->assertEquals($props_array, Kint::settings($stash));
        $this->assertEquals($props, $r->getStaticProperties());
    }

    public function pathProvider()
    {
        return array(
            'standard file' => array(
                'path' => __FILE__,
                'expect' => '<tests>/KintTest.php',
            ),
            'standard dir' => array(
                'path' => __DIR__,
                'expect' => '<tests>',
            ),
            'parent dir' => array(
                'path' => KINT_DIR,
                'expect' => '<kint>',
            ),
            'sub file' => array(
                'path' => KINT_DIR.'/src//test',
                'expect' => '<kint>/src/test',
            ),
            'common path' => array(
                'path' => dirname(KINT_DIR).'/test/',
                'expect' => '.../test',
            ),
            'root path' => array(
                'path' => '/',
                'expect' => '/',
            ),
            'no common path' => array(
                'path' => '/asdfasdf/test/',
                'expect' => '/asdfasdf/test',
            ),
        );
    }

    /**
     * @dataProvider pathProvider
     * @covers \Kint\Kint::shortenPath
     */
    public function testShortenPath($path, $expect)
    {
        Kint::$app_root_dirs = array(
            KINT_DIR => '<kint>',
            KINT_DIR.'/test' => '<test>',
            '' => '<Nothing!>',
            __DIR__ => '<tests>',
            KINT_DIR.'/tes' => '<tes>',
        );

        $this->assertEquals($expect, Kint::shortenPath($path));
    }

    /**
     * @covers \Kint\Kint::getIdeLink
     */
    public function testGetIdeLink()
    {
        Kint::$file_link_format = '<a href="%f:%l">%f:%l</a>';

        $file = uniqid('', true);
        $line = uniqid('', true);

        $this->assertEquals('<a href="'.$file.':'.$line.'">'.$file.':'.$line.'</a>', Kint::getIdeLink($file, $line));
    }

    public function getCalleeInfoProvider()
    {
        $basetrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $dumpframe = array(
            'class' => 'Kint',
            'function' => 'dump',
        );

        $data['empty trace'] = array(
            'trace' => array(
            ),
            'param_count' => 1234,
            'expect' => array(
                null,
                array(),
                null,
                null,
                array(),
            ),
        );

        $data['full trace'] = array(
            'trace' => array_merge(array($dumpframe), $basetrace),
            'param_count' => 1234,
            'expect' => array(
                null,
                array(),
                $dumpframe,
                array(
                    'function' => __FUNCTION__,
                    'class' => __CLASS__,
                    'type' => '->',
                ),
            ),
        );

        $data['trace with params'] = array(
            'trace' => array_merge(
                    array(
                        $dumpframe + array(
                            'file' => TestClass::DUMP_FILE,
                            'line' => TestClass::DUMP_LINE,
                        ),
                    ),
                    $basetrace
                ),
            'param_count' => 3,
            'expect' => array(
                array(
                    array('name' => '$x', 'path' => '$x', 'expression' => false),
                    array('name' => '$y', 'path' => '$y', 'expression' => false),
                    array('name' => '$z', 'path' => '$z', 'expression' => false),
                ),
                array(),
                $dumpframe + array(
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE,
                ),
            ),
        );

        $data['trace with modifiers'] = array(
            'trace' => array_merge(
                    array(
                        $dumpframe + array(
                            'file' => TestClass::DUMP_FILE,
                            'line' => TestClass::DUMP_LINE + 1,
                        ),
                    ),
                    $basetrace
                ),
            'param_count' => 0,
            'expect' => array(
                array(),
                array('!', '+'),
                $dumpframe + array(
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 1,
                ),
            ),
        );

        $data['trace function with modifier'] = array(
            'trace' => array_merge(
                    array(
                        array(
                            'function' => 'd',
                            'file' => TestClass::DUMP_FILE,
                            'line' => TestClass::DUMP_LINE + 2,
                        ),
                    ),
                    $basetrace
                ),
            'param_count' => 1,
            'expect' => array(
                array(
                    array(
                        'name' => '$x',
                        'path' => '$x',
                        'expression' => false,
                    ),
                ),
                array('~'),
                array(
                    'function' => 'd',
                    'file' => TestClass::DUMP_FILE,
                    'line' => TestClass::DUMP_LINE + 2,
                ),
            ),
        );

        // HHVM doesn't support multiple unpack parameters
        if (KINT_PHP56 && !defined('HHVM_VERSION')) {
            $data['trace with unpack'] = array(
                'trace' => array_merge(
                        array(
                            $dumpframe + array(
                                'file' => Php56TestClass::DUMP_FILE,
                                'line' => Php56TestClass::DUMP_LINE,
                            ),
                        ),
                        $basetrace
                    ),
                'param_count' => 4,
                'expect' => array(
                    array(
                        array(
                            'name' => '$x',
                            'path' => '$x',
                            'expression' => false,
                        ),
                        array(
                            'name' => '$y',
                            'path' => '$y',
                            'expression' => false,
                        ),
                        array(
                            'name' => 'reset($z)',
                            'path' => 'reset($z)',
                            'expression' => false,
                        ),
                        array(
                            'name' => 'array_values($z)[1]',
                            'path' => 'array_values($z)[1]',
                            'expression' => false,
                        ),
                    ),
                    array(),
                    $dumpframe + array(
                        'file' => Php56TestClass::DUMP_FILE,
                        'line' => Php56TestClass::DUMP_LINE,
                    ),
                ),
            );

            $data['trace with double unpack'] = array(
                'trace' => array_merge(
                        array(
                            $dumpframe + array(
                                'file' => Php56TestClass::DUMP_FILE,
                                'line' => Php56TestClass::DUMP_LINE + 1,
                            ),
                        ),
                        $basetrace
                    ),
                'param_count' => 10,
                'expect' => array(
                    array(),
                    array(),
                    $dumpframe + array(
                        'file' => Php56TestClass::DUMP_FILE,
                        'line' => Php56TestClass::DUMP_LINE + 1,
                    ),
                ),
            );
        }

        return $data;
    }

    /**
     * @dataProvider getCalleeInfoProvider
     * @covers \Kint\Kint::getCalleeInfo
     */
    public function testGetCalleeInfo($trace, $param_count, $expect)
    {
        $func = new ReflectionMethod('Kint', 'getCalleeInfo');
        $func->setAccessible(true);

        $output = $func->invoke(null, $trace, $param_count);

        $output = array_slice($output, 0, count($expect));

        $this->assertSame($expect, $output);
    }
}
