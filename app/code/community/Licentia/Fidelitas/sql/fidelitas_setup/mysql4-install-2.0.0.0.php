<?php


$installer = $this;
$installer->startSetup();

$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_abandoned')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_abandoned_log')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_account')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_autoresponders')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_autoresponders_events')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_campaigns')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_campaigns_followup')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_campaigns_links')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_campaigns_splits')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_conversions')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_conversions_segments')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_coupons')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_cron_report')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_extra')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_groups')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_history')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_lists')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_lists_stores')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_reports')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_segments_evolutions')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_segments_evolutions_summary')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_segments_subscribers')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_segments_subscribers_list')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_senders')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_stats')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_subscribers')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_templates')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_urls')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('fidelitas_widget_cache')}`;");


$installer->run("
-- ----------------------------
--  Table structure for `fidelitas_account`
-- ----------------------------
DROP TABLE IF EXISTS `{$this->getTable('fidelitas_account')}`;
CREATE TABLE `{$this->getTable('fidelitas_account')}`(
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL DEFAULT '0',
  `company_name` varchar(255) DEFAULT NULL,
  `company_legal_name` varchar(255) DEFAULT NULL,
  `company_type` varchar(255) DEFAULT NULL,
  `business_activity_code` varchar(255) DEFAULT NULL,
  `date_registration` date DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `signup_date` date DEFAULT NULL,
  `credits` float(8,2) DEFAULT NULL,
  `cron` smallint(2) DEFAULT NULL,
  `notify_user` smallint(2) DEFAULT NULL,
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='E-Goi - Account Info';
");


$installer->run("
-- ----------------------------
--  Table structure for `fidelitas_extra`
-- ----------------------------
DROP TABLE IF EXISTS `{$this->getTable('fidelitas_extra')}`;
CREATE TABLE `{$this->getTable('fidelitas_extra')}` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `extra_code` varchar(50) DEFAULT NULL,
  `attribute_code` varchar(50) DEFAULT NULL,
  `system` smallint(11) DEFAULT NULL,
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Fidelitas - List Extra Fields';

");


$installer->run("
-- ----------------------------
--  Table structure for `fidelitas_lists`
-- ----------------------------
DROP TABLE IF EXISTS `{$this->getTable('fidelitas_lists')}`;
CREATE TABLE `{$this->getTable('fidelitas_lists')}`(
  `list_id` int(11) NOT NULL AUTO_INCREMENT,
  `listnum` int(12) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `internal_name` varchar(255) DEFAULT NULL,
  `subs_activos` int(11) DEFAULT NULL,
  `subs_total` int(11) DEFAULT NULL,
  `canal_email` enum('0','1') NOT NULL DEFAULT '1',
  `canal_sms` enum('0','1') NOT NULL DEFAULT '0',
  `is_active` enum('0','1') DEFAULT '1',
  PRIMARY KEY (`list_id`),
  UNIQUE KEY `unq_listnum` (`listnum`),
  KEY `listnum` (`listnum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='E-Goi - List of Lists';

");


$installer->run("
-- ----------------------------
--  Table structure for `fidelitas_subscribers`
-- ----------------------------
DROP TABLE IF EXISTS `{$this->getTable('fidelitas_subscribers')}`;
CREATE TABLE `{$this->getTable('fidelitas_subscribers')}`(
  `subscriber_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `language` varchar(255) DEFAULT NULL,
  `store_id` varchar(255) DEFAULT NULL,
  `uid` varchar(255) DEFAULT NULL,
  `add_date` date DEFAULT NULL,
  `subscription_method` varchar(255) DEFAULT NULL,
  `list` int(11) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `cellphone` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `fax` varchar(255) DEFAULT NULL,
  `tax_id` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `zip_code` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `id_card` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `birth_date` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `bounces` int(11) DEFAULT NULL,
  `email_sent` int(11) DEFAULT NULL,
  `email_views` int(11) DEFAULT NULL,
  `referrals` int(11) DEFAULT NULL,
  `referrals_converted` int(11) DEFAULT NULL,
  `clicks` int(11) DEFAULT NULL,
  `sms_sent` int(11) DEFAULT NULL,
  `sms_delivered` int(11) DEFAULT NULL,
  `remove_method` varchar(255) DEFAULT NULL,
  `remove_date` datetime DEFAULT NULL,
  PRIMARY KEY (`subscriber_id`),
  UNIQUE KEY `unq_uid_list` (`uid`,`list`),
  KEY `email_i` (`email`),
  KEY `list_i` (`list`),
  KEY `uid_i` (`uid`),
  KEY `customer_i` (`customer_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='E-Goi - List of subscribers';

");

$installer->endSetup();
