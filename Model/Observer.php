<?php
/**
 * Observer.php
 * 
 * @category Cammino
 * @package  Cammino_Fbpixel
 * @author   Cammino Digital <suporte@cammino.com.br>
 * @license  http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://github.com/cammino/magento-fbpixel
 */

class Cammino_Fbpixel_Model_Observer
{
    protected $_fbpixelHelper;

    /**
     * Init facebook helper
     * 
     * @return null
     */
    function __construct()
    {
        $this->_fbpixelHelper = Mage::helper('fbpixel');
    }

    /**
     * Function responsible for get the product added to cart
     * and set id, qty and supergroup to an session variable
     * 
     * @return null
     */
    public function addToCart()
    {
        $id  = Mage::app()->getRequest()->getParam('product', 0);
        $qty = Mage::app()->getRequest()->getParam('qty', 1);
        $superGroup = Mage::app()->getRequest()->getParam('super_group');

        Mage::getModel('core/session')->setFbpixelAddProductToCart(
            new Varien_Object(
                array(
                    'id'  => (int) $id,
                    'qty' => (int) $qty,
                    'super_group' => (array) $superGroup
                )
            )
        );
    }

    /**
     * Function responsible for get order when it finished
     * and set orderId to an session variable
     * 
     * @return null
     */
    public function orderSuccess()
    {
        $incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($incrementId);

        $orderId = (int) $order->getId();
        Mage::getModel('core/session')->setFbpixelOrder($orderId);
    }
}