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

$installer->run("ALTER TABLE `{$installer->getTable('newsletter_subscriber')}` ADD COLUMN  `fidelitas_updated_at` datetime DEFAULT CURRENT_TIMESTAMP");

$installer->run("CREATE TRIGGER `fidelitas_change_update` BEFORE UPDATE ON `{$installer->getTable('newsletter_subscriber')}` FOR EACH ROW set NEW.fidelitas_updated_at = IF(NEW.subscriber_status=1 AND OLD.subscriber_status!=1,NOW(),OLD.fidelitas_updated_at)");

$installer->endSetup();
