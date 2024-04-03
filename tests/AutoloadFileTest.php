<?php

namespace LevelLevel;

use PHPUnit\Framework\TestCase;
use Roots\Bedrock\Autoloader;

use function Roots\Bedrock\determine_autoload_dir;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AutoloadFileTest extends TestCase{
    public function definitions():array{
        return array(
            'No definitions set' => array(
                array(),
                realpath( __DIR__ . '/../../' ), // The parent of the 0-loader is two directories up from this file.
            ),
            'Autoload dir predefined' => array(
                array(
                    'LL_AUTOLOAD_DIR' => '/tmp/'
                ),
                '/tmp/',
            ),
            'Use parent theme (template directory) autoload' => array(
                array(
                    'LL_AUTOLOAD_USE_PARENT' => true,
                ),
                './template_directory/',
            ),
            'Use child theme autoload directory' => array(
                array(
                    'LL_AUTOLOAD_USE_CHILD' => true
                ),
                './stylesheet_directory/',
            ),
            'Use parent folder (wp_content)' => array(
                array(
                    'LL_AUTOLOAD_CONTENT_DIR' => true
                ),
                realpath( __DIR__ . '/../../' ), // The parent of the 0-loader is two directories up from this file.
            ),
            'Combined (precendence test)' => array(
                array(
                    'LL_AUTOLOAD_CONTENT_DIR' => true,
                    'LL_AUTOLOAD_DIR' => '/tmp/',
                ),
                '/tmp/',
            ),
            'Combined 2 (precendence test 2)' => array(
                array(
                    'LL_AUTOLOAD_CONTENT_DIR' => true,
                    'LL_AUTOLOAD_USE_CHILD' => true
                ),
                realpath( __DIR__ . '/../../' ), // The parent of the 0-loader is two directories up from this file.
            )
        );
    }

    /**
     * @dataProvider definitions     
     */
    public function testCanDetermineAutoloadDir(array $definitions, string $result) : void {
        foreach($definitions as $key => $value){
            define($key, $value);
        }
        $this->assertEquals((new Autoloader())->llDetermineAutoloadeDir(), $result);
    }
}
