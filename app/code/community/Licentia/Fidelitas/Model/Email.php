<?php

/**
 * Licentia, Unipessoal LDA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://licentia.pt/magento-license.txt
 *
 * @title      Licentia SMTP Email Marketing
 * @package    Licentia
 * @author     Bento Vilas Boas <bento@licentia.pt>
 * @copyright  Copyright (c) 2012-2016 Licentia - http://licentia.pt
 * @license    http://licentia.pt/magento-license.txt
 */
class Licentia_Fidelitas_Model_Email extends Mage_Core_Model_Email
{


    public function send()
    {
        $storeId = Mage::app()->getStore()->getId();

        if (!Mage::getStoreConfigFlag('fidelitas/transactional/enable', $storeId)) {
            return parent::send();
        }

        if (Mage::getStoreConfigFlag('system/smtp/disable')) {
            return $this;
        }

        $mail = new Zend_Mail();

        if (strtolower($this->getType()) == 'html') {
            $mail->setBodyHtml($this->getBody());
        } else {
            $mail->setBodyText($this->getBody());
        }


        $transport = Mage::helper('fidelitas')->getSmtpTransport($storeId);

        $mail->setFrom($this->getFromEmail(), $this->getFromName())
            ->addTo($this->getToEmail(), $this->getToName())
            ->setSubject($this->getSubject())
            ->setReplyTo($this->getSenderEmail(), $this->getSenderName());

        $mail->send($transport);

        return $this;
    }

}
