<?php
/**
 * Observer
 *
 * This file is responsible for execute functions from observers
 *
 * @category   Fbpixel
 * @package    Tracking
 * @author     Cammino Digital <contato@cammino.com.br>
 */

// Observer
class Cammino_Fbpixel_Model_Observer 
{
	protected $fbpixelHelper;

	// Constructor
	function __construct()
    {
        $this->fbpixelHelper = Mage::helper('fbpixel');
    }

    // Observer responsible for get the product added to cart (null)
	public function addToCart()
	{
		$id  = Mage::app()->getRequest()->getParam('product', 0);
		$qty = Mage::app()->getRequest()->getParam('qty', 1);
		$superGroup = Mage::app()->getRequest()->getParam('super_group');
		
		Mage::getModel('core/session')->setFbpixelAddProductToCart(
			new Varien_Object(array(
				'id'  => (int) $id,
				'qty' => (int) $qty,
				'super_group' => (array) $superGroup
			))
		);
	}

	// Observer responsible for get order when it finished (null)
	public function orderSuccess()
	{
		$incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($incrementId);

		$orderId = (int) $order->getId();
		Mage::getModel('core/session')->setFbpixelOrder($orderId);
	}

}
