<?php
/**
 * Data.php
 * 
 * @category Cammino
 * @package  Cammino_Fbpixel
 * @author   Cammino Digital <suporte@cammino.com.br>
 * @license  http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://github.com/cammino/magento-fbpixel
 */

class Cammino_Fbpixel_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Get product id
     * 
     * @param object $product Magento product
     * 
     * @return int
     */
    public function getProductId($product)
    {
        return $product->getId();
    }

     /**
     * Get product name
     * 
     * @param object $product Magento product
     * 
     * @return string
     */
    public function getProductName($product)
    {
        return $product->getName();
    }

    /**
     * Get product price
     * 
     * @param object $product Magento product
     * 
     * @return float|string
     */
    public function getProductPrice($product)
    {
        // phpcs:disable Zend.NamingConventions.ValidVariableName
        $productType = $product->getTypeId() != null ? $product->getTypeId() : $product->product_type;
        // phpcs:enable Zend.NamingConventions.ValidVariableName

        if ($productType == "simple") {
            return $this->getSimpleProductPrice($product);
        } else if ($productType == "grouped") {
            return $this->getGroupedProductPrice($product);
        } else if ($productType == "bundle") {
            return $this->getBundleProductPrice($product);
        } else if ($productType == "configurable") {
            return $this->getConfigurableProductPrice($product);
        } else {
            return "";
        }
    }

    /**
     * Get configurable product price
     * 
     * @param object $product Magento product
     * 
     * @return float
     */
    public function getConfigurableProductPrice($product) 
    {
        return $this->getCatalogPromoPrice($product);
    }

    /**
     * Get simple product price
     * 
     * @param object $product Magento product
     * 
     * @return float
     */
    public function getSimpleProductPrice($product)
    {
        return $this->getCatalogPromoPrice($product);
    }

    /**
     * Get grouped product price
     * 
     * @param object $product Magento product
     * 
     * @return float
     */
    public function getGroupedProductPrice($product)
    {
        $associated = $this->getAssociatedProducts($product);
        $prices = array();
        $minimal = 0;

        foreach ($associated as $item) {
            if ($item->getFinalPrice() > 0) {
                array_push($prices, $this->getCatalogPromoPrice($item));
            }
        }

        rsort($prices, SORT_NUMERIC);

        if (count($prices) > 0) {
            $minimal = end($prices);    
        }

        return $minimal;
    }

    /**
     * Get bundle product price
     * 
     * @param object $product Magento product
     * 
     * @return float
     */
    public function getBundleProductPrice($product)
    {
        $optionCollection = $product->getTypeInstance(true)->getOptionsIds($product);
        $selectionsCollection = Mage::getModel('bundle/selection')->getCollection();
        $selectionsCollection->getSelect()->where('option_id in (?)', $optionCollection)->where('is_default = ?', 1);
        $defaultPrice = 0;

        foreach ($selectionsCollection as $_selection) {
            $_selectionProduct = Mage::getModel('catalog/product')->load($_selection->getProductId());
            $_selectionPrice = $product->getPriceModel()->getSelectionFinalTotalPrice(
                $product,
                $_selectionProduct,
                0,
                $_selection->getSelectionQty(),
                false,
                true
            );
            $defaultPrice += ($_selectionPrice * $_selection->getSelectionQty());
        }

        return $defaultPrice;
    }

    /**
     * Get price of last product changed in quote
     * 
     * @param object $productId Magento product id
     * 
     * @return float
     */
    public function getPriceProductQuote($productId)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $lastItem = null;
        $price = 0;

        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductId() == $productId) {
                $lastItem = $lastItem == null ? $item : $lastItem;
                $lastItem = $lastItem->getCreatedAt() < $item->getCreatedAt() ? $item : $lastItem;
                $price = $lastItem->getPrice();
            }
        }

        return $price;
    }

    /**
     * Get a collection of associated products of one product
     * 
     * @param object $product Magento product
     * 
     * @return object
     */
    public function getAssociatedProducts($product)
    {
        $collection = $product->getTypeInstance(true)->getAssociatedProductCollection($product)
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', 1);

        return $collection;
    }

    /**
     * Get product sku
     * 
     * @param object $product Magento product
     * 
     * @return string
     */
    public function getProductSku($product)
    {
        return $product->getSku();
    }

    /**
     * Get image url of the product
     * 
     * @param object $product Magento product
     * 
     * @return string
     */
    public function getProductImage($product)
    {
        return $product->getImageUrl();
    }

    /**
     * Get the url of the product
     * 
     * @param object $product Magento product
     * 
     * @return string
     */
    public function getProductUrl($product)
    {
        return Mage::helper('catalog/product')->getProductUrl($product->getId());
    }

     /**
     * Convert string in json
     * 
     * @param array $string Random values to be converted in json
     * 
     * @return string
     */
    public function getJson($string)
    {
        return json_encode($string);
    }

    /**
     * Calc item price from order
     * 
     * @param array $orderItem Magento Order Item
     * 
     * @return float
     */
    public function getOrderItemPrice($orderItem)
    {
        return (($orderItem->getRowTotal() - $orderItem->getDiscount()) / $orderItem->getQtyOrdered());
    }

    /**
    * Function responsible for process catalogo promo rules
    *
    * @param object $price Product object
    *
    * @return float
    */
    public function getCatalogPromoPrice($product)
    {
        $now = Mage::getSingleton('core/date')->timestamp( time() );
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        $customerGroup = 0;
        $productId = $product->getId();
        $promoPrice = Mage::getResourceModel('catalogrule/rule')->getRulePrice($now, $websiteId, $customerGroup, $productId);

        if (($promoPrice <= $product->getFinalPrice()) && ($promoPrice > 0)) {
            return $promoPrice;
        } else {
            return $product->getFinalPrice();
        }
    }
}