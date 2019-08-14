<?php

/**
 * Licentia Fidelitas - SMS Notifications for E-Goi
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * @title SMS Notifications
 * @category Marketing
 * @package Licentia
 * @author Bento Vilas Boas <bento@licentia.pt>
 * @Copyright (c) 2016 E-Goi - http://e-goi.com
 * @license Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International
 */
$installer = $this;
$installer->startSetup();

$installer->run("
-- ----------------------------
-- Table structure for `fidelitas_autoresponders`
-- ----------------------------
DROP TABLE IF EXISTS `{$this->getTable('fidelitas_autoresponders')}`;
CREATE TABLE `{$this->getTable('fidelitas_autoresponders')}` (
  `autoresponder_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_ids` varchar(255) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `send_moment` enum('occurs','after') NOT NULL DEFAULT 'occurs',
  `active` enum('0','1') NOT NULL DEFAULT '0',
  `after_days` smallint(2) DEFAULT NULL,
  `after_hours` smallint(1) DEFAULT NULL,
  `product` varchar(255) DEFAULT NULL,
  `message` text,
  `number_subscribers` int(11) DEFAULT NULL,
  `send_once` enum('0','1') DEFAULT '1',
  `search` varchar(255) DEFAULT NULL,
  `search_option` enum('eq','like') DEFAULT 'eq',
  `order_status` varchar(255) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  PRIMARY KEY (`autoresponder_id`),
  KEY `event` (`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8  COMMENT='E-Goi - List of Autoresponders'");


$installer->run("
-- ----------------------------
-- Table structure for `fidelitas_autoresponders_events`
-- ----------------------------
DROP TABLE IF EXISTS `{$this->getTable('fidelitas_autoresponders_events')}`;
CREATE TABLE `{$this->getTable('fidelitas_autoresponders_events')}` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `event` varchar(255) DEFAULT NULL,
  `autoresponder_id` int(11) DEFAULT NULL,
  `customer_id` int(10) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `cellphone` varchar(255) DEFAULT NULL,
  `send_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `sent` enum('0','1') NOT NULL DEFAULT '0',
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`event_id`),
  KEY `autoresponder_id` (`autoresponder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='E-Goi - List of events in queue for autoresponder'");

$installer->endSetup();
