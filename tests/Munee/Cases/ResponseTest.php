<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Cases;

use Munee\Asset\HeaderSetter;
use Munee\Response;
use Munee\Mocks\MockAssetType;

/**
 * Tests for the \Munee\Response Class
 *
 * @author Cody Lundquist
 */
class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var int
     */
    protected $lastModifiedTime = 123456789;

    /**
     * Make sure the constructor is getting the correct Object passed to it.
     */
    public function testConstruct()
    {
        $this->setExpectedException('Munee\ErrorException');
        new Response(new \stdClass());
    }

    /**
     * Test the initial request from a browser.  Meaning they haven't requested the file before
     *
     * @runInSeparateProcess
     */
    public function testNonCachedResponse()
    {
        $Response = new Response(new MockAssetType());
        $Response->setHeaderController(new HeaderSetter());
        $Response->setHeaders(0);

        $checkHeaders = array();
        $checkHeaders['Cache-Control'] = 'max-age=0, must-revalidate';
        $checkHeaders['Content-Type'] = 'text/test';
        $checkHeaders['Last-Modified'] = gmdate('D, d M Y H:i:s', $this->lastModifiedTime) . ' GMT';
        // ETag is MD5 Hash of the content + last modified date
        $checkHeaders['ETag'] = '00403b660c8f869d9f50c429f6dceb72';

        $setHeaders = $this->getHeaders();

        $this->assertSame($checkHeaders['Cache-Control'], $setHeaders['Cache-Control']);
        unset($setHeaders['Cache-Control']);
        $this->assertContains($checkHeaders['Content-Type'], $setHeaders['Content-Type']);
        unset($setHeaders['Content-Type']);
        $this->assertSame($checkHeaders['Last-Modified'], $setHeaders['Last-Modified']);
        unset($setHeaders['Last-Modified']);
        $this->assertSame($checkHeaders['ETag'], $setHeaders['ETag']);
        unset($setHeaders['ETag']);

        $this->assertSame(0, count($setHeaders));

        $this->assertFalse($Response->notModified);

        header_remove('Cache-Control');
        header_remove('Last-Modified');
        header_remove('ETag');
        header_remove('Content-Type');
    }

    /**
     * Test the subsequent request by setting the correct $_SERVER variables and returning a
     * 304 response.  Unfortunately, xdebug_get_headers() does not return header response codes
     * so I am testing a variable instead.
     *
     * @runInSeparateProcess
     */
    public function testCachedResponse()
    {
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = gmdate('D, d M Y H:i:s', $this->lastModifiedTime) . ' GMT';
        $_SERVER['HTTP_IF_NONE_MATCH'] = '00403b660c8f869d9f50c429f6dceb72';

        $Response = new Response(new MockAssetType());
        $Response->setHeaderController(new HeaderSetter());
        $Response->setHeaders(0);

        $checkHeaders = array();
        $setHeaders = $this->getHeaders();

        $this->assertSame($checkHeaders, $setHeaders);

        $this->assertSame(0, count($setHeaders));
        $this->assertTrue($Response->notModified);

        unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        unset($_SERVER['HTTP_IF_NONE_MATCH']);
    }

    /**
     * Test to see if what the browser request headers has is expired and return a new response
     * with new caching headers.
     *
     * @runInSeparateProcess
     */
    public function testExpiredRequestResponse()
    {
        $expiredTime = $this->lastModifiedTime - 100; // removing 100 seconds
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = gmdate('D, d M Y H:i:s', $expiredTime) . ' GMT';
        $_SERVER['HTTP_IF_NONE_MATCH'] = '00000000000000000000000000000000';

        $Response = new Response(new MockAssetType());
        $Response->setHeaderController(new HeaderSetter());
        $Response->setHeaders(0);

        $checkHeaders = array();
        $checkHeaders['Cache-Control'] = 'max-age=0, must-revalidate';
        $checkHeaders['Content-Type'] = 'text/test';
        $checkHeaders['Last-Modified'] = gmdate('D, d M Y H:i:s', $this->lastModifiedTime) . ' GMT';
        // ETag is MD5 Hash of the content + last modified date
        $checkHeaders['ETag'] = '00403b660c8f869d9f50c429f6dceb72';

        $setHeaders = $this->getHeaders();

        $this->assertSame($checkHeaders['Cache-Control'], $setHeaders['Cache-Control']);
        unset($setHeaders['Cache-Control']);
        $this->assertContains($checkHeaders['Content-Type'], $setHeaders['Content-Type']);
        unset($setHeaders['Content-Type']);
        $this->assertSame($checkHeaders['Last-Modified'], $setHeaders['Last-Modified']);
        unset($setHeaders['Last-Modified']);
        $this->assertSame($checkHeaders['ETag'], $setHeaders['ETag']);
        unset($setHeaders['ETag']);

        $this->assertSame(0, count($setHeaders));

        $this->assertFalse($Response->notModified);

        header_remove('Cache-Control');
        header_remove('Last-Modified');
        header_remove('ETag');
        header_remove('Content-Type');
    }

    /**
     * Make sure rendering is working properly
     */
    public function testRender()
    {
        $Response = new Response(new MockAssetType());

        $this->assertSame('foo', $Response->render());
    }

    /**
     * Helper function to get the current headers set and weed out the ones that don't have a value
     *
     * @return array
     */
    protected function getHeaders()
    {
        $rawHeaders = xdebug_get_headers();
        $ret = array();
        foreach ($rawHeaders as $header) {
            $headerParts = explode(':', $header, 2);
            if (2 == count($headerParts)) {
                if ($headerParts[0] === 'Content-type') {
                    // xdebug incompatible naming
                    $headerParts[0] = 'Content-Type';
                }
                $ret[$headerParts[0]] = trim($headerParts[1]);
            } elseif (isset($ret[$headerParts[0]])) {
                // If a header param is empty, make sure others with the same name are not set as well
                unset($ret[$headerParts[0]]);
            }
        }

        return $ret;
    }
}
