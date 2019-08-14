<?php

/**
 * Licentia Fidelitas - SMS Notifications for E-Goi
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * @title    SMS Notifications
 * @category Marketing
 * @package  Licentia
 * @author   Bento Vilas Boas <bento@licentia.pt>
 * @Copyright (c) 2016 E-Goi - http://e-goi.com
 * @license  Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International
 */
$installer = $this;
$installer->startSetup();

$installer->run("DELETE FROM `{$installer->getTable('fidelitas_subscribers')}` WHERE email IS NULL OR LENGTH(email)<5");
$installer->run("DELETE FROM `{$installer->getTable('newsletter_subscriber')}` WHERE subscriber_email IS NULL OR LENGTH(subscriber_email)<5");

$installer->endSetup();
