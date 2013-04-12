<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset;

/**
 * Handles setting HTTP headers.
 *
 * @author Terence C
 */
class HeaderSetter
{
    /**
     * Set HTTP status code.
     *
     * @param string $protocol
     * @param string $code
     * @param string $message
     * 
     * @return object
     */
    public function statusCode($protocol, $code, $message)
    {
        header("{$protocol} {$code} {$message}");
        
        return $this;
    }
    
    /**
     * Set HTTP header field/value.
     *
     * @param string $field
     * @param string $value
     * 
     * @return object
     */
    public function headerField($field, $value)
    {
        header("{$field}: {$value}");
        
        return $this;
    }
}