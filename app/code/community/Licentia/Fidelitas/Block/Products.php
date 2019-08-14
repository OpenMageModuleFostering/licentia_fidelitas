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
class Licentia_Fidelitas_Block_Products extends Mage_Catalog_Block_Product_Abstract implements Mage_Widget_Block_Interface
{

    protected function _toHtml()
    {

        $params = $this->getData('params');

        $this->setTemplate('fidelitas/' . $params['template'] . '.phtml');

        $model = Mage::getModel('fidelitas/products')->getWidget($params);

        $this->setData('product_collection', $model);
        $this->setData('title', $params['title']);

        return parent::_toHtml();
    }

}
