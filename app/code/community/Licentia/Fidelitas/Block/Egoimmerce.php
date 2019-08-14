<?php

class Licentia_Fidelitas_Block_Egoimmerce extends Mage_Core_Block_Template
{

    public function getPageName()
    {
        return $this->_getData('page_name');
    }

    protected function _getOrdersTrackingCode()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }

        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('entity_id', array('in' => $orderIds));
        $result = array();

        /** @var Mage_Sales_Model_Order $order */
        foreach ($collection as $order) {

            /** @var Mage_Sales_Model_Order_Item $item */
            foreach ($order->getAllVisibleItems() as $item) {

                //get category name
                $product_id = $item->getProductId();
                $_product = Mage::getModel('catalog/product')->load($product_id);
                $cats = $_product->getCategoryIds();
                $category_id = end($cats);
                $category = Mage::getModel('catalog/category')->load($category_id);
                $category_name = $category->getName();

                $qty = number_format((int)$item->getQtyOrdered(), 0, '.', '');

                $result[] = sprintf("_egoiaq.push(['addEcommerceItem',  '%s', '%s', '%s', %s, %s]);", $this->jsQuoteEscape($item->getSku()), $this->jsQuoteEscape($item->getName()), $category_name, $item->getBasePrice(), $qty);
            }

            foreach ($collection as $order) {
                if ($order->getGrandTotal()) {
                    $subtotal = $order->getGrandTotal() - $order->getShippingAmount() - $order->getShippingTaxAmount();
                } else {
                    $subtotal = '0.00';
                }
                $result[] = sprintf("_egoiaq.push(['trackEcommerceOrder', '%s', %s, %s, %s, %s]);", $order->getIncrementId(), $order->getBaseGrandTotal(), $subtotal, $order->getBaseTaxAmount(), $order->getBaseShippingAmount()
                );
            }
        }
        return implode("\n", $result);
    }

    protected function _getEcommerceCartUpdate()
    {

        $cart = Mage::getModel('checkout/cart')->getQuote()->getAllVisibleItems();

        foreach ($cart as $cartitem) {

            //get category name
            $product_id = $cartitem->getProductId();
            $_product = Mage::getModel('catalog/product')->load($product_id);
            $cats = $_product->getCategoryIds();
            $category = Mage::getModel('catalog/category')->load(end($cats));
            $category_name = $category->getName();
            $nameofproduct = str_replace('"', "", $cartitem->getName());

            if ($cartitem->getPrice() == 0 || $cartitem->getPrice() < 0.00001) {
                continue;
            }

            echo '_egoiaq.push(["addEcommerceItem", "' . $cartitem->getSku() . '","' . $nameofproduct . '","' . $category_name . '",' . $cartitem->getPrice() . ',' . (int)$cartitem->getQty() . ']);';
            echo "\n";
        }

        //total in cart
        $grandTotal = Mage::getModel('checkout/cart')->getQuote()->getGrandTotal();
        if ($grandTotal == 0) {
            echo '';
        } else {
            echo '_egoiaq.push(["trackEcommerceCartUpdate", ' . $grandTotal . ']);';
        }
        echo "\n";
    }

    protected function _getProductPageview()
    {

        $currentproduct = Mage::registry('current_product');

        if (!($currentproduct instanceof Mage_Catalog_Model_Product)) {
            return;
        }

        $cats = $currentproduct->getCategoryIds();
        $category = Mage::getModel('catalog/category')->load(end($cats));
        $categoryName = $category->getName();
        $product = str_replace('"', "", $currentproduct->getName());


        echo '_egoiaq.push(["setEcommerceView", "' . $currentproduct->getSku() . '", "' . $product . '","' . $categoryName . '",' . $currentproduct->getPrice() . ']);';
        Mage::unregister('current_category');
    }

    protected function _getCategoryPageview()
    {
        $currentcategory = Mage::registry('current_category');

        if (!($currentcategory instanceof Mage_Catalog_Model_Category)) {
            return;
        }
        echo '_egoiaq.push(["setEcommerceView", false,false,"' . $currentcategory->getName() . '"]);';
        Mage::unregister('current_product');
    }

    protected function _toHtml()
    {
        if (!Mage::helper('fidelitas')->isEgoimmerceAvailable()) {
            return '';
        }
        return str_replace("\n\n", "\n", parent::_toHtml());
    }

}
