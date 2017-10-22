<?php

namespace Kint\Test;

use Kint;
use ReflectionClass;
use ReflectionProperty;

class KintTest extends KintTestCase
{
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
     */
    public function testShortenPath($path, $expect)
    {
        Kint::$app_root_dirs = array(
            KINT_DIR => '<kint>',
            KINT_DIR.'/test' => '<test>',
            __DIR__ => '<tests>',
            KINT_DIR.'/tes' => '<tes>',
        );

        $this->assertEquals($expect, Kint::shortenPath($path));
    }

    public function testGetIdeLink()
    {
        Kint::$file_link_format = '<a href="%f:%l">%f:%l</a>';

        $file = uniqid('', true);
        $line = uniqid('', true);

        $this->assertEquals('<a href="'.$file.':'.$line.'">'.$file.':'.$line.'</a>', Kint::getIdeLink($file, $line));
    }

    public function setUp()
    {
        parent::setUp();

        if ($this->getName() === 'testComposerGetExtras' && !getenv('KINT_FILE')) {
            rename(KINT_DIR.'/composer.json', KINT_DIR.'/composer.json.bak');

            file_put_contents(KINT_DIR.'/composer.json', json_encode(array(
                'extra' => array(
                    'kint' => array('test' => 'data'),
                ),
            )));
        }
    }

    public function tearDown()
    {
        parent::tearDown();

        if ($this->getName() === 'testComposerGetExtras' && !getenv('KINT_FILE')) {
            rename(KINT_DIR.'/composer.json.bak', KINT_DIR.'/composer.json');
        }
    }

    /**
     * Test Kint::composerGetExtras.
     *
     * This is a flimsy test but it's as good as it gets without altering
     * composer.json mid-test without a proper setup/teardown in place
     */
    public function testComposerGetExtras()
    {
        if (getenv('KINT_FILE')) {
            $this->markTestSkipped('Not testing composerGetExtras in single file build');
        }

        $this->assertEquals(array('test' => 'data'), Kint::composerGetExtras('kint'));
    }
}
