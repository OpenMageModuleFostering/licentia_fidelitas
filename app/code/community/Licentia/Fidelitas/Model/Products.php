<?php

/**
 * Licentia Fidelitas - Advanced Email and SMS Marketing Automation for E-Goi
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Creative Commons Attribution-NonCommercial 4.0 International
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc/4.0/
 *
 * @title      Advanced Email and SMS Marketing Automation
 * @category   Marketing
 * @package    Licentia
 * @author     Bento Vilas Boas <bento@licentia.pt>
 * @copyright  Copyright (c) 2012 Licentia - http://licentia.pt
 * @license    Creative Commons Attribution-NonCommercial 4.0 International
 */
class Licentia_Fidelitas_Model_Products extends Mage_Core_Model_Abstract
{

    protected $_productIds = array();


    /**
     * @return Licentia_Fidelitas_Model_Subscribers
     */
    public function getSubscriber()
    {

        return Mage::registry('fidelitas_subscriber');

    }

    public function getSubscriberEmail()
    {

        if ($this->getSubscriber()->getEmail()) {
            return $this->getSubscriber()->getEmail();
        }
        return false;
    }


    public function getWidget($data)
    {

        $storeId = $this->getSubscriber()->getStoreId();

        if (!$storeId) {
            $storeId = Mage::app()->getDefaultStoreView()->getId();
        }

        Mage::app()->getStore()->setId($storeId);


        $segments = explode(',', $data['segments']);
        $segments = array_values($segments);
        $segments['number_products'] = $data['number_products'];

        $productsIds = array();

        $productsIds[] = $this->getRelatedProductsFromLastOrder($segments);
        $productsIds[] = $this->getRelatedProducts($segments);
        $productsIds[] = $this->getAbandonedCart($segments);
        $productsIds[] = $this->getViewsProducts($segments);
        $productsIds[] = $this->getWishlistProducts($segments);
        $productsIds[] = $this->getCategoriesProducts($segments);
        $productsIds[] = $this->getAttributesProducts($segments);
        $productsIds[] = $this->getRecentProducts($segments);


        $prod = array();
        foreach ($productsIds as $list) {
            if (is_array($list)) {
                foreach ($list as $value) {
                    if (!isset($prod[$value])) {
                        $prod[$value] = 0;
                    } else {
                        $prod[$value] = $prod[$value] + 1;
                    }
                }
            }
        }

        $productsIds = $prod;

        krsort($productsIds);

        if (count($productsIds) > $segments['number_products']) {
            $productsIds = array_slice($productsIds, 0, $segments['number_products']);
        }
        $productsIds = array_keys($productsIds);


        $catalog = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToFilter('entity_id', array('in' => $productsIds))
            ->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addUrlRewrite()
            ->addStoreFilter();

        switch ($data['sort_results']) {
            CASE 'random':
                $catalog->getSelect()->order('rand()');
                break;
            CASE 'created_at':
                $catalog->addAttributeToSort('created_at', 'DESC');
                break;
            CASE 'price_asc':
                $catalog->addAttributeToSort('price', 'ASC');
                break;
            CASE 'price_desc':
            default:
                $catalog->addAttributeToSort('price', 'DESC');
                break;
        }

        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($catalog);

        return $catalog;
    }

    public function getWishlistProducts($info)
    {

        $customerId = $this->getSubscriber()->getCustomerId();

        if (!$customerId) {
            return false;
        }

        if (!in_array('wishlist', $info))
            return false;

        if (isset($this->_productIds['wishlist'])) {
            return $this->_productIds['wishlist'];
        }

        $wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($customerId)
            ->getItemCollection()
            ->setOrder('added_at', 'asc');


        $productsIds = array();

        foreach ($wishlist as $item) {
            $productsIds[] = $item->getProductId();
        }

        if (count($productsIds) > $info['number_products']) {
            $productsIds = array_slice($productsIds, 0, $info['number_products']);
        }

        $this->_productIds['wishlist'] = $productsIds;

        return $this->_productIds['wishlist'];
    }

    public function getCategoriesProducts($info)
    {


        if (!in_array('categories', $info))
            return false;

        if (isset($this->_productIds['categories'])) {
            return $this->_productIds['categories'];
        }

        $info[] = 'views';

        $items = $this->getViewsProducts($info);

        $productsIds = array();
        $cats = array();

        foreach ($items as $item) {
            $product = Mage::getModel('catalog/product')->load($item);

            if (!$product->getId())
                continue;

            $rp = $product->getCategoryIds();
            foreach ($rp as $value) {
                $cats[] = $value;
            }
        }

        $cats = array_unique($cats);

        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id=entity_id', null, 'left');
        $collection->distinct(true);
        $collection->addAttributeToFilter('category_id', array('in' => array('finset' => implode(',', $cats))));
        $collection->addAttributeToSort('price', 'desc');
        $collection->setPageSize($info['number_products']);


        foreach ($collection as $product) {
            $productsIds[] = $product->getId();
        }

        $this->_productIds['categories'] = $productsIds;

        return $this->_productIds['categories'];
    }

    public function getAttributesProducts($info)
    {


        if (!in_array('attributes', $info))
            return false;

        if (isset($this->_productIds['attributes'])) {
            return $this->_productIds['attributes'];
        }


        $info[] = 'views';

        $items = $this->getViewsProducts($info);

        $products = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSort('price', 'desc')
            ->setPageSize($info['number_products'])
            ->addAttributeToFilter('entity_id', array('in' => $items));

        $productsIds = array();

        $attrs = array();

        foreach ($products as $product) {

            $attributes = $product->getAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute->getData('is_filterable')) {
                    if (!isset($attrs[$attribute->getName()])) {
                        $attrs[$attribute->getName()] = 1;
                    } else {
                        $attrs[$attribute->getName()] = $attrs[$attribute->getName()] + 1;
                    }
                }
            }
        }

        ksort($attrs);

        $attr = array_keys($attrs);
        $attr = $attr[0];

        $catalog = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToFilter($attr, array('neq' => 'fidelitas'));

        foreach ($catalog as $prod) {
            $value = $prod->getId();
            if (!isset($productsIds[$value])) {
                $productsIds[$value] = 1;
            } else {
                $productsIds[$value] = $productsIds[$value] + 1;
            }
        }

        krsort($productsIds);

        $productsIds = array_keys($productsIds);

        $this->_productIds['attributes'] = $productsIds;

        return $this->_productIds['attributes'];
    }

    public function getRelatedProductsFromLastOrder($info)
    {

        $customerId = $this->getSubscriber()->getCustomerId();

        if (!$customerId) {
            return false;
        }

        if (!in_array('related_order', $info))
            return false;

        if (isset($this->_productIds['related_order'])) {
            return $this->_productIds['related_order'];
        }

        $orders = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('state', 'complete')
            ->setPageSize(1)
            ->addAttributeToFilter('customer_id', $customerId);

        $productsIds = array();

        foreach ($orders as $orderObject) {

            $items = $orderObject->getItemsCollection();
            foreach ($items as $item) {

                $product = Mage::getModel('catalog/product')->load($item->getProductId());

                if (!$product->getId())
                    continue;


                $rp = $product->getRelatedProductIds();
                foreach ($rp as $value) {
                    if (!isset($productsIds[$value])) {
                        $productsIds[$value] = 1;
                    } else {
                        $productsIds[$value] = $productsIds[$value] + 1;
                    }
                }
            }
        }


        krsort($productsIds);

        if (count($productsIds) > $info['number_products']) {
            $productsIds = array_slice($productsIds, 0, $info['number_products']);
        }

        $productsIds = array_keys($productsIds);

        $this->_productIds['related_order'] = $productsIds;

        return $this->_productIds['related_order'];
    }

    public function getRelatedProducts($info)
    {

        $customerId = $this->getSubscriber()->getCustomerId();

        if (!$customerId) {
            return false;
        }

        if (!in_array('related', $info))
            return false;

        if (isset($this->_productIds['related'])) {
            return $this->_productIds['related'];
        }

        $orders = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('state', 'complete')
            ->addAttributeToFilter('customer_id', $customerId);

        $productsIds = array();

        foreach ($orders as $order) {
            $items = $order->getItemsCollection();

            foreach ($items as $item) {
                $product = Mage::getModel('catalog/product')->load($item->getProductId());

                if (!$product->getId())
                    continue;

                $rp = $product->getRelatedProductIds();
                foreach ($rp as $value) {
                    if (!isset($productsIds[$value])) {
                        $productsIds[$value] = 1;
                    } else {
                        $productsIds[$value] = $productsIds[$value] + 1;
                    }
                }
            }
        }

        krsort($productsIds);

        if (count($productsIds) > $info['number_products']) {
            $productsIds = array_slice($productsIds, 0, $info['number_products']);
        }

        $productsIds = array_keys($productsIds);

        $this->_productIds['related'] = $productsIds;

        return $this->_productIds['related'];
    }

    public function getAbandonedCart($info)
    {

        $email = $this->getSubscriber()->getEmail();

        if (!$email) {
            return false;
        }

        if (!in_array('abandoned', $info))
            return false;

        if (isset($this->_productIds['abandoned'])) {
            return $this->_productIds['abandoned'];
        }

        $orders = Mage::getResourceModel('sales/quote_collection')
            ->addFieldToSelect('*')
            ->addFieldToFilter('store_id', Mage::app()->getStore()->getId())
            ->addFieldToFilter('customer_email', $email)
            ->addFieldToFilter('items_count', array('neq' => '0'))
            ->addFieldToFilter('is_active', '1');

        $productsIds = array();

        foreach ($orders as $order) {
            $items = $order->getItemsCollection();

            foreach ($items as $item) {

                if (!isset($productsIds[$item->getProductId()])) {
                    $productsIds[$item->getProductId()] = 1;
                } else {
                    $productsIds[$item->getProductId()] = $productsIds[$item->getProductId()] + 1;
                }
            }
        }

        krsort($productsIds);

        if (count($productsIds) > $info['number_products']) {
            $productsIds = array_slice($productsIds, 0, $info['number_products']);
        }

        $productsIds = array_keys($productsIds);

        $this->_productIds['abandoned'] = $productsIds;


        return $this->_productIds['abandoned'];
    }

    public function getRecentProducts($info)
    {

        if (!in_array('new', $info))
            return false;

        if (isset($this->_productIds['new'])) {
            return $this->_productIds['new'];
        }

        $todayDate = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds());

        $collection->addAttributeToFilter('news_from_date', array('or' => array(
            0 => array('date' => true, 'to' => $todayDate),
            1 => array('is' => new Zend_Db_Expr('null'))),
        ), 'left')
            ->addAttributeToFilter('news_to_date', array('or' => array(
                0 => array('date' => true, 'from' => $todayDate),
                1 => array('is' => new Zend_Db_Expr('null'))),
            ), 'left')
            ->addAttributeToSort('news_from_date', 'desc')
            ->setPageSize($info['number_products']);


        $productsIds = array();

        foreach ($collection as $value) {
            $productsIds[] = $value->getId();
        }

        $this->_productIds['new'] = $productsIds;

        return $this->_productIds['new'];
    }

    public function getViewsProducts($info)
    {

        $customerId = $this->getSubscriber()->getCustomerId();

        if (!$customerId) {
            return false;
        }

        if (!in_array('views', $info))
            return false;

        if (isset($this->_productIds['views'])) {
            return $this->_productIds['views'];
        }

        $report = Mage::getModel('reports/event')->getCollection()
            ->addFieldToFilter('event_type_id', 1)
            ->addOrder('views', 'desc')
            ->addFieldToFilter('subject_id', $customerId);

        $report->getSelect()
            ->columns(array('views' => new Zend_Db_Expr('COUNT(object_id)')))
            ->group('object_id')
            ->limit($info['number_products']);


        $result = $report->getData();

        $productsIds = array();

        foreach ($result as $value) {
            $productsIds[$value['object_id']] = $value['views'];
        }

        krsort($productsIds);

        if (count($productsIds) > $info['number_products']) {
            $productsIds = array_slice($productsIds, 0, $info['number_products']);
        }

        $productsIds = array_keys($productsIds);

        $this->_productIds['views'] = $productsIds;

        return $this->_productIds['views'];
    }

}
