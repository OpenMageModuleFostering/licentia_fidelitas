<?php

/**
 * Licentia Fidelitas - Advanced Email and SMS Marketing Automation for E-Goi
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * @title      Advanced Email and SMS Marketing Automation
 * @category   Marketing
 * @package    Licentia
 * @author     Bento Vilas Boas <bento@licentia.pt>
 * @copyright  Copyright (c) 2016 E-Goi - http://e-goi.com
 * @license    Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International
 */
class Licentia_Fidelitas_Block_Adminhtml_Account extends Mage_Adminhtml_Block_Template
{

    public function __construct()
    {
        parent::__construct();

        $result = Mage::getModel('fidelitas/account')->getAccount();
        
        $this->setTitle($this->__('Account Details'));
        $this->setCompany($result);

        $this->setTemplate('fidelitas/account/account.phtml');
    }

}
