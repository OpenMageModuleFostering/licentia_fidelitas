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

$installer->run("ALTER TABLE `{$this->getTable('fidelitas_autoresponders_events')}` ADD COLUMN `data_object_id` int");
$installer->run("ALTER TABLE `{$this->getTable('fidelitas_autoresponders_events')}` ADD COLUMN `message` VARCHAR (100)");

$installer->endSetup();
