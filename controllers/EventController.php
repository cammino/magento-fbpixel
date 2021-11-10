<?php
/**
 * EventController.php
 * 
 * @category Cammino
 * @package  Cammino_Fbpixel
 * @author   Cammino Digital <suporte@cammino.com.br>
 * @license  http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://github.com/cammino/magento-fbpixel
 */

class Cammino_Fbpixel_EventController extends Mage_Core_Controller_Front_Action
{


    /**
     * Ajax action to send conversion 
     * 
     * @return json
     */
    public function sendAction()
    {
        $request = Mage::app()->getRequest();
        $model = Mage::getModel('fbpixel/conversion');
        $result = $model->sendEvent($request);
        echo $result;
    }
}