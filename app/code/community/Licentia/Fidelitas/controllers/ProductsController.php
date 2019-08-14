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
class Licentia_Fidelitas_ProductsController extends Mage_Core_Controller_Front_Action
{
    public function getAction()
    {
        $subscriber = new Varien_Object();
        $email = $this->getRequest()->getParam('email');
        $uid = $this->getRequest()->getParam('uid');

        $params = $this->getRequest()->getParams();

        $paramsDefault = array();
        $paramsDefault['number_products'] = 10;
        $paramsDefault['title'] = '';
        $paramsDefault['sort_results'] = 'price';
        $paramsDefault['segments'] = 'new';
        $paramsDefault['template'] = 'products';
        $paramsDefault['image_size'] = '120';
        $paramsDefault['columns_count'] = 4;

        $params = array_merge($paramsDefault, $params);


        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $subscriber = Mage::getModel('fidelitas/subscribers')->load($email, 'email');
        }

        if (!$subscriber->getId()) {
            $subscriber = Mage::getModel('fidelitas/subscribers')->load($uid, 'uid');
        }

        Mage::register('fidelitas_subscriber', $subscriber);

        $block = $this->getLayout()->createBlock('Licentia_Fidelitas_Block_Products', 'fidelitas_products');
        $block->setData('params', $params);

        echo $block->toHtml();

        die();
    }

}
