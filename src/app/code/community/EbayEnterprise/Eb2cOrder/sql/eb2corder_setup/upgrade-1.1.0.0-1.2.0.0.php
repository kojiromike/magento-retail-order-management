<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Upgrade for Eb2cOrder
 * Disables `increment_per_store` setting for the
 * sales/order "entity"
 */

Mage::log(sprintf('[%s] Upgrading Eb2cOrder 1.1.0.0 -> 1.2.0.0', __CLASS__), Zend_Log::INFO);
// use the sales setup model to make it much easier to manage the EAV entity
// type table
$installer = Mage::getResourceModel('sales/setup', 'sales_setup');
$installer->startSetup();
$installer->updateEntityType(Mage_Sales_Model_Order::ENTITY, 'increment_per_store', false);
$installer->endSetup();
