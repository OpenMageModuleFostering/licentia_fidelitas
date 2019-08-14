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
class Licentia_Fidelitas_Model_Email_Template extends Mage_Core_Model_Email_Template
{

    public function send($email, $name = null, array $variables = array())
    {
        $storeId = Mage::app()->getStore()->getId();

        if (!Mage::getStoreConfigFlag('fidelitas/transactional/enable', $storeId)) {
            return parent::send($email, $name, $variables);
        }
        if (!$this->isValidForSend()) {
            Mage::logException(new Exception('This letter cannot be sent.')); // translation is intentionally omitted
            return false;
        }

        $emails = array_values((array)$email);
        $names = is_array($name) ? $name : (array)$name;
        $names = array_values($names);
        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }

        $variables['email'] = reset($emails);
        $variables['name'] = reset($names);

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($variables, true);
        $subject = $this->getProcessedTemplateSubject($variables);

        $mail = $this->getMail();

        foreach ($emails as $key => $email) {
            $mail->addTo($email, '=?utf-8?B?' . base64_encode($names[$key]) . '?=');
        }

        if ($this->isPlain()) {
            $mail->setBodyText($text);
        } else {
            $mail->setBodyHTML($text);
        }

        $mail->setSubject('=?utf-8?B?' . base64_encode($subject) . '?=');
        $mail->setFrom($this->getSenderEmail(), $this->getSenderName());

        if (Mage::getStoreConfig('fidelitas/transactional/domain')) {
            $mail->addHeader('X-Domain', Mage::getStoreConfig('fidelitas/transactional/domain'), $storeId);
        }

        $mail->addHeader('X-Open-Tracking-Enabled', true);
        $mail->addHeader('X-Click-Tracking-Enabled', true);

        try {
            $transport = Mage::helper('fidelitas')->getSmtpTransport($storeId);
            $mail->send($transport);
            $this->_mail = null;
        } catch (Exception $e) {
            $this->_mail = null;
            Mage::logException($e);
            return false;
        }

        return true;

    }


}
