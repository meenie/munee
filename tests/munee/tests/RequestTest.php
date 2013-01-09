<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\tests;

use munee\Request;

/**
 * Tests for the \munee\Request Class
 *
 * @author Cody Lundquist
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Set Up
     *
     * Create some tmp asset files
     */
    protected function setUp()
    {
        $jsDir = WEBROOT . DS . 'js';
        if (! file_exists($jsDir)) {
            mkdir($jsDir);
        }

        file_put_contents($jsDir . DS . 'foo.js', '//Temp foo.js File');
        file_put_contents($jsDir . DS . 'bar.js', '//Temp bar.js File');
    }

    /**
     * Tear Down
     *
     * Remove the tmp asset files
     */
    protected function tearDown()
    {
        $jsDir = WEBROOT . DS . 'js';
        unlink($jsDir . DS . 'foo.js');
        unlink($jsDir . DS . 'bar.js');
        rmdir($jsDir);
    }

    /**
     * Make sure there is at least a files query string parameter
     */
    public function testNoFilesQueryStringParam()
    {
        $Request = new Request();

        $this->setExpectedException('munee\ErrorException');
        $Request->init();
    }

    /**
     * Constructor Test
     */
    public function testConstructor()
    {
        $Request = new Request(array('foo' => 'bar'));
        $this->assertSame(array('foo' => 'bar'), $Request->options);
    }

    /**
     * Make sure files are parsed properly and the extension is set
     */
    public function testInit()
    {
        $Request = new Request();

        $_GET = array(
            'files' => '/js/foo.js,/js/bar.js'
        );
        $Request->init();

        $this->assertSame('js', $Request->ext);
        $this->assertSame(array(WEBROOT . '/js/foo.js', WEBROOT . '/js/bar.js'), $Request->files);
    }

    /**
     * Make sure the correct exception is thrown when a file does not exist
     */
    public function testFileNotExist()
    {
        $Request = new Request();

        $_GET = array(
            'files' => '/js/does-not-exist.js'
        );

        $this->setExpectedException('munee\asset\NotFoundException');
        $Request->init();
    }

    /**
     * Make sure all passed in files can be handled by the asset type class
     */
    public function testExtensionNotSupported()
    {
        $Request = new Request();

        $_GET = array(
            'files' => '/js/foo.jpg,/js/bar.js'
        );

        $this->setExpectedException('munee\ErrorException');
        $Request->init();
    }

    /**
     * Make sure they can not go above webroot when requesting a file
     */
    public function testGoingAboveWebroot()
    {
        $Request = new Request();

        $_GET = array(
            'files' => '/../..././js/bad.js,/js/bar.js'
        );

        $this->setExpectedException('munee\asset\NotFoundException');
        $Request->init();
    }

    /**
     * Make legacy code is still being supported
     */
    public function testLegacyCode()
    {
        $Request = new Request();

        $_GET = array(
            'files' => '/minify/js/foo.js'
        );

        $Request->init();

        $this->assertSame(array(WEBROOT . '/js/foo.js'), $Request->files);
        $this->assertSame(array('minify' => 'true'), $Request->getRawParams());
    }

    /**
     * Make sure you are getting the correct raw parameters
     */
    public function testGetRawParams()
    {
        $Request = new Request();

        $_GET = array(
            'files' => '/js/foo.js,/js/bar.js',
            'minify' => 'true',
            'notAllowedParam' => 'foo'
        );

        $Request->init();
        $rawParams = $Request->getRawParams();
        $this->assertSame(array('minify' => 'true', 'notAllowedParam' => 'foo'), $rawParams);
    }

    /**
     * Make sure the Parameter Parser is doing it's job correctly
     */
    public function testParseParams()
    {
        $Request = new Request();

        $_GET = array(
            'files' => '/js/foo.js,/js/bar.js',
            'foo' => 'true',
            'b' => 'ba[42]',
            'notAllowedParam' => 'foo'

        );

        $Request->init();

        $this->assertEquals(array(), $Request->params);

        $allowedParams = array(
            'foo' => array(
                'alias' => 'f',
                'default' => 'false',
                'cast' => 'boolean'
            ),
            'bar' => array(
                'alias' => 'b',
                'arguments' => array(
                    'baz' => array(
                        'alias' => 'ba',
                        'regex' => '\d+',
                        'cast' => 'integer',
                        'default' => 24
                    ),
                )
            ),
            'qux' => array(
                'default' => 'no'
            )
        );

        $Request->parseParams($allowedParams);
        $assertedParams = array(
            'foo' => true,
            'bar' => array(
                'baz' => 42
            ),
            'qux' => 'no'
        );

        $this->assertSame($assertedParams, $Request->params);
    }

    /**
     * Make sure the param validation is working properly
     */
    public function testWrongParamValue()
    {
        $Request = new Request();

        $_GET = array(
            'files' => '/js/foo.js',
            'foo' => 'not good'
        );

        $Request->init();

        $allowedParams = array(
            'foo' => array(
                'alias' => 'f',
                'regex' => 'true|false',
                'default' => 'false',
                'cast' => 'boolean'
            )
        );

        $this->setExpectedException('munee\ErrorException');
        $Request->parseParams($allowedParams);
    }
}