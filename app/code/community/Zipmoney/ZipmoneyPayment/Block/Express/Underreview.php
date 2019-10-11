<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Block_Express_Underreview extends Zipmoney_ZipmoneyPayment_Block_Abstract
{
    public function getMessage()
    {
        return $this->__('Your application is currently under review by zipMoney and will be processed very shortly.');
    }

    public function getDescription()
    {
        $vMessage = $this->__('Don\'t worry, it usually takes less than 10 minutes! For any enquiries please');
        $vMessage .= '<br />';
        $vMessage .= $this->__('contact: customercare@zipmoney.com.au');
        return $vMessage;
    }
}