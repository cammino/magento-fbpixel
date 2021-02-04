<?php
/**
 * Tracking.php
 * 
 * @category Cammino
 * @package  Cammino_Fbpixel
 * @author   Cammino Digital <suporte@cammino.com.br>
 * @license  http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://github.com/cammino/magento-fbpixel
 */

class Cammino_Fbpixel_Block_Tracking extends Mage_Core_Block_Template
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
     * Function responsible for check if module is enable
     * 
     * @return bool
     */
    public function isFbpixelActive()
    {
        return (bool) Mage::getStoreConfig('fbpixel/fbpixel_group/fbpixel_active');
    }

    /**
     * Function responsible for return Facebook Pixel ID
     * 
     * @return string
     */
    public function getFbpixelStoreId()
    {
        return (string) Mage::getStoreConfig('fbpixel/fbpixel_group/fbpixel_store_id');
    }

    /**
     * Function responsible for delegating which tag 
     * will be rendered based on page
     * 
     * @return string
     */
    public function getPageTrackingCode()
    {
        try {
            return $this->_setPage();
        } catch (Exception $e) {
            Mage::log($e, null, 'fbpixel.log');
            return "";
        }
    }

    /**
     * Function responsible for delegating which tag 
     * will be rendered based on observers
     * 
     * @param string $page Magento page identifier
     * 
     * @return string
     */
    public function getObserverTrackingCode($page)
    {
        try {
            if ($page == "cart") {
                return $this->_setObserverCart();
            } else if ($page == "order") {
                return $this->_setObserverOrder();
            } else if ($page == "lead") {
                return $this->setObserverLead();
            } else {
                return "";
            }
        } catch (Exception $e) {
            Mage::log($e, null, 'fbpixel.log');
            return "";
        }
    }

    /**
     * Identifies the page and call the function responsible for generating the tag
     * will be rendered based on observers
     *
     * current_product validation must come before the current_category verification
     *
     * @return string
     */
    private function _setPage()
    {
        // Product View
        if (Mage::registry('current_product')) {
            return $this->getTagProductView();
        }

        // Category Page
        // if ($this->getRegistryCategory() || $this->isSearchPage())
        // {
        //     return $this->getTagProductImpression();
        // }

        return "";
    }

    /**
     * Identify if there is a variable in the session to render the tag and checks
     * which tag will be rendered based on the action cart (add, update, delete)
     *
     * @return string
     */
    private function _setObserverCart()
    {
        if (Mage::getModel('core/session')->getFbpixelAddProductToCart() != null) {
            return $this->_getTagCartAdd();
        }

        return "";
    }

    /**
     * Identify if there is a variable in the session to render
     * the tag for order success
     *
     * @return string
     */
    private function _setObserverOrder()
    {
        if (Mage::getModel('core/session')->getFbpixelOrder() != null) {
            return $this->getTagOrder();
        }

        return "";
    }

    /**
     * Get product view tag
     *
     * @return string
     */
    public function getTagProductView() 
    {
        $product = $this->getProduct();

        $data = array(
            "content_type"     => 'product',
            "content_ids"      => (int) $this->_fbpixelHelper->getProductid($product),
            "content_name"     => (string) $this->_fbpixelHelper->getProductName($product),
            "content_category" => (string) $this->getRegistryCategory(),
            "value"            => (float) $this->_fbpixelHelper->getProductPrice($product),
            "currency"         => 'BRL'
        );

        $json = json_encode($data);

        return "fbq('track', 'ViewContent', $json);";
    }  

    /**
     * Get cart add tag
     *
     * @return string
     */
    private function _getTagCartAdd()
    {
        $sessionItem = Mage::getModel('core/session')->getFbpixelAddProductToCart();
        $products[] = Mage::getModel('catalog/product')->load($sessionItem->getId());        
        $superGroup = $sessionItem->getSuperGroup();

        $url = count($products) > 0 ? $this->_fbpixelHelper->getProductUrl($products[0]) : "";
        $img = count($products) > 0 ? $this->_fbpixelHelper->getProductImage($products[0]) : "";

        if (($superGroup != null) && (count((array) $superGroup) > 0)) {
            $products = array();
            foreach ($superGroup as $superGroupId => $superGroupQty) {
                $products[] = Mage::getModel('catalog/product')->load($superGroupId);
            }
        }

        Mage::getModel('core/session')->unsFbpixelAddProductToCart();

        $ids = array();
        $names = array();
        $value = 0;

        foreach ($products as $product) {
            $ids[] = (int) $this->_fbpixelHelper->getProductId($product);
            $names[] = (string) $this->_fbpixelHelper->getProductName($product);
            $value += (float) $this->_fbpixelHelper->getProductPrice($product);
        }

        $data = array(
            "content_type"     => 'product',
            "content_ids"      => $ids,
            "content_name"     => 'Cart',
            "value"            => $value,
            "currency"         => 'BRL'
        );

        $json = json_encode($data);
        return "fbq('track', 'AddToCart', $json);";
    }

    /**
     * Get order tag
     *
     * @return string
     */
    public function getTagOrder()
    {
        $orderId = Mage::getModel('core/session')->getFbpixelOrder();
        $order = Mage::getModel('sales/order')->load($orderId);
        $orderItems = $order->getAllVisibleItems();

        // Count item's total
        $totalItems = $orderItems;
        $i = 0;
        $ids = '';
        $names = array();

        foreach ($orderItems as $orderItem) {
            $product = Mage::getModel('catalog/product')->load($orderItem->getProductId());
            $url = $product->getProductUrl();
            $img = $product->getImageUrl();            
            $i++;

            if ($orderItem->getProductType() == "grouped") {
                $buyRequest = $orderItem->getBuyRequest();
                if (isset($buyRequest["super_product_config"]) && isset($buyRequest["super_product_config"]["product_id"])) {
                    $parentId = $buyRequest["super_product_config"]["product_id"];
                    $parentProduct = Mage::getModel("catalog/product")->load($parentId);
                    $url = $parentProduct->getProductUrl();
                    $img = $parentProduct->getImageUrl();
                }
            }
            
            $ids[] = (int) $this->_fbpixelHelper->getProductId($product);
            $names[] = (string) $this->_fbpixelHelper->getProductName($product);
        }

        $json = array(
            "order_id"         => (int) $orderId,
            "num_items"        => count($ids),
            "content_type"     => 'product',
            "content_ids"      => $ids,
            "content_name"     => 'Order Receipt',
            "value"            => (float) $order->getGrandTotal(),
            "currency"         => 'BRL',
            "customer_type"    => $this->getOrderCustomerType($order)
        );

        Mage::getModel('core/session')->unsFbpixelOrder();
        $jsonresult = json_encode($json);
        return "fbq('track', 'Purchase', $jsonresult);";
    }

    /**
     * Get actual product
     *
     * @return object
     */
    public function getProduct()
    {
        return Mage::registry('current_product');
    }

    /**
     * Get the registry category in product detail and product list
     *
     * @return string
     */
    public function getRegistryCategory()
    {
        $category = Mage::registry('current_category');
        
        return $category ? $category->getName() : "";
    }   

    /**
     * Get customer type from order
     *
     * @return string
     */
    public function getOrderCustomerType($order) {
        try {
            $customerTypeAttribute = Mage::getModel('eav/config')->getAttribute('customer', 'tipopessoa');
            $customerType = $customerTypeAttribute->getSource()->getOptionText($order->getCustomerTipopessoa());
            return $customerType;
        } catch(Exception $ex) {
            return '';
        }   
    }

    /**
    * Check if user is logged
    *
    * @return bool
    */
    public function isUserLogged()
    {
        return (Mage::getSingleton('customer/session')->isLoggedIn());
    }

    /**
    * Get logged user data for advanced matching
    *
    * @return json
    */
    public function getUserData()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $data = array();

        if ($customer) {
            $data = array(
                'em'            => $customer->getEmail(),
                'fn'            => $customer->getFirstname(),
                'ln'            => $customer->getLastname(),
                'external_id'   => $customer->getEmail()
            );

            $address = $customer->getDefaultBillingAddress();

            if ($address) {
                $data['ph'] = preg_replace( '/[^0-9]/', '', $address->getTelephone());
                $data['ct'] = $address->getCity();
                $data['st'] = $address->getRegion();
                $data['zp'] = preg_replace( '/[^0-9]/', '', $address->getPostcode());
                $data['country'] = $address->getCountry();
            }
        }

        return json_encode($data);
    }
}