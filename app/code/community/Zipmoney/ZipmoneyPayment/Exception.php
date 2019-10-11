<?php
/**
 * @category  Aligent
 * @package   Zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Exception extends Exception
{
    protected $_iCode = null;

    public function __construct($vMessage = null, $iCode = 0)
    {
        $this->_iCode = $iCode;
        parent::__construct($vMessage, 0);
    }

    public function getErrorCode()
    {
        return $this->_iCode;
    }
}