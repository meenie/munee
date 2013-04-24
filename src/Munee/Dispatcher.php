<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee;

/**
 * The outermost layer of Munee that wraps everything in a Try/Catch block
 *
 * @author Cody Lundquist
 */
class Dispatcher
{
    /**
     * Dispatch default options
     *
     * @var array
     */
    static $defaultOptions = array('setHeaders' => true);

    /**
     * 1) Initialise the Request
     * 2) Grab the AssetType based on the request and initialise it
     * 3) Instantiate the Response class, set the headers, and then return the content
     *
     * Rap everything in a Try/Catch block for error handling
     *
     * @param Request $Request
     * @param array $options
     *
     * @return string
     *
     * @catch NotFoundException
     * @catch ErrorException
     */
    public static function run(Request $Request, $options = array())
    {
        try {
            /**
             * Merge in default options
             */
            $options = array_merge(self::$defaultOptions, $options);
            /**
             * Set the header controller. Can be overwritten by the dispatcher options
             */
            if (
                isset($options['headerController']) &&
                $options['headerController'] instanceof Asset\HeaderSetter
            ) {
                $headerController = $options['headerController'];
            } else {
                $headerController = new Asset\HeaderSetter;
            }
            /**
             * Initialise the Request
             */
            $Request->init();
            /**
             * Grab the correct AssetType
             */
            $AssetType = Asset\Registry::getClass($Request);
            /**
             * Initialise the AssetType
             */
            $AssetType->init();
            /**
             * Create a response
             */
            $Response = new Response($AssetType);
            $Response->setHeaderController($headerController);
            /**
             * Set the headers if told to do so
             */
            if ($options['setHeaders']) {
                /**
                 * Set the headers.
                 */
                $Response->setHeaders();
            }
            /**
             * If the content hasn't been modified return null so only headers are sent
             * otherwise return the content
             */
            return $Response->notModified ? null : $Response->render();
        } catch (Asset\NotFoundException $e) {
            $headerController->statusCode('HTTP/1.0', 404, 'Not Found');
            $headerController->headerField('Status', '404 Not Found');
            return 'Error: ' . $e->getMessage();
        } catch (ErrorException $e) {
            $errors = 'Error: ' . $e->getMessage();
            while ($e = $e->getPrevious()) {
                $errors .= "<br>" . $e->getMessage();
            }

            return $errors;
        }
    }
}