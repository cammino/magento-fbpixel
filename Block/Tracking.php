<?php
class Cammino_Fbpixel_Block_Tracking extends Mage_Core_Block_Template
{
    // Local Variable
    protected $fbpixelHelper;

    // Constructor
    function __construct()
    {
        $this->fbpixelHelper = Mage::helper('fbpixel');
    }

    // Check if module is active (String)
    public function isFbpixelActive()
    {
        return (bool) Mage::getStoreConfig('fbpixel/fbpixel_group/fbpixel_active');
    }

    // Get the Gokeep Store ID (String)
    public function getFbpixelStoreId()
    {
        return (string) Mage::getStoreConfig('fbpixel/fbpixel_group/fbpixel_store_id');
    }

    // Function responsible for delegating which tag will be rendered based on page (String)
    public function getPageTrackingCode()
    {
        try {
            return $this->setPage();
        } catch (Exception $e) {
            Mage::log($e, null, 'fbpixel.log');
            return "";
        }
    }

    // Function responsible for delegating which tag will be rendered based on observers (String)
    public function getObserverTrackingCode($page)
    {
        try {
            if($page == "cart") {
                return $this->setObserverCart();
            } else if($page == "order"){
                return $this->setObserverOrder();
            } else if($page == "lead") {
                return $this->setObserverLead();
            } else {
                return "";
            }
        } catch (Exception $e) {
            Mage::log($e, null, 'gokeep.log');
            return "";
        }
    }

    // Identifies the page and call the function responsible for generating the tag
    // current_product validation must come before the current_category verification (String)
    private function setPage()
    {
        // Product View
        if(Mage::registry('current_product')) 
        {
            return $this->getTagProductView();
        }

        // Category Page
        // if ($this->getRegistryCategory() || $this->isSearchPage())
        // {
        //     return $this->getTagProductImpression();
        // }

        return "";
    }

    // Identify if there is a variable in the session to render the tag and checks which tag will be rendered based on the action cart (add, update, delete) - (String)
    private function setObserverCart()
    {
        if (Mage::getModel('core/session')->getFbpixelAddProductToCart() != null)
        {
            return $this->getTagCartAdd();
        }

        return "";
    }

    // Identify if there is a variable in the session to render the tag for order success (String)
    private function setObserverOrder()
    {
        if (Mage::getModel('core/session')->getFbpixelOrder() != null)
        {
            return $this->getTagOrder();
        }
        return "";
    }

    // Get product view tag (String)
    public function getTagProductView() 
    {
        $product = $this->getProduct();

        $data = array(
            "content_type"     => 'product',
            "content_ids"      => (int) $this->fbpixelHelper->getProductid($product),
            "content_name"     => (string) $this->fbpixelHelper->getProductName($product),
            "content_category" => (string) $this->getRegistryCategory(),
            "value"            => (float)  $this->fbpixelHelper->getProductPrice($product),
            "currency"         => 'BRL'
        );

        $json = json_encode($data);

        return "fbq('track', 'ViewContent', $json);";
    }  

    // Get cart add tag (String)
    private function getTagCartAdd()
    {
        $sessionItem = Mage::getModel('core/session')->getFbpixelAddProductToCart();
        $products[] = Mage::getModel('catalog/product')->load($sessionItem->getId());        
        $superGroup = $sessionItem->getSuperGroup();

        $url = count($products) > 0 ? $this->fbpixelHelper->getProductUrl($products[0]) : "";
        $img = count($products) > 0 ? $this->fbpixelHelper->getProductImage($products[0]) : "";

        if (($superGroup != null) && (count((array)$superGroup) > 0)) {
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
            $ids[] = (int) $this->fbpixelHelper->getProductId($product);
            $names[] = (string) $this->fbpixelHelper->getProductName($product);
            $value += (float) $this->fbpixelHelper->getProductPrice($product);
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

    // Get order tag (String)
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
            
            $ids[] = (int) $this->fbpixelHelper->getProductId($product);
            $names[] = (string) $this->fbpixelHelper->getProductName($product);
        }

        $json = array(
            "order_id"         => (int) $orderId,
            "num_items"        => count($ids),
            "content_type"     => 'product',
            "content_ids"      => $ids,
            "content_name"     => 'Order Receipt',
            "value"            => (float) $order->getGrandTotal(),
            "currency"         => 'BRL'
        );

        Mage::getModel('core/session')->unsFbpixelOrder();
        $jsonresult = json_encode($json);
        return "fbq('track', 'Purchase', $jsonresult);";
    }

    // Get actual product (Object)
    public function getProduct()
    {
        return Mage::registry('current_product');
    }

    // Get the registry category in product detail and product list (String)
    public function getRegistryCategory()
    {
        $category = Mage::registry('current_category');

        return $category ? $category->getName() : "";
    }   
}