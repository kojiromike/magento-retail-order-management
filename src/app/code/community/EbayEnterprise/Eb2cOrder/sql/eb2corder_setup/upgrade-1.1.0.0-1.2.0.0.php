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
 * Update the order entity to share increment ids across all stores and use
 * a custom increment model for generating entity increment ids
 */

// use the sales setup model to make it easier to manage the EAV entity
// type table
$installer = Mage::getResourceModel('sales/setup', 'sales_setup');
$installer->startSetup();
// set the order entity to use a single pool of increment ids - increment_per_store
// set to false (prevents duplicate increment ids when multiple stores use same
// client order id prefix) - and a custom increment model to add the configured
// client order id prefixes - increment_model set to alias of the desired model.
$installer->updateEntityType(Mage_Sales_Model_Order::ENTITY, 'increment_per_store', false);
$installer->updateEntityType(Mage_Sales_Model_Order::ENTITY, 'increment_model', 'eb2corder/eav_entity_increment_order');

/*
 * update the eav_entity_store table to not include a prefix for the
 * admin store as it is not used for anything in the custom increment model
 */
$orderTypeId = Mage::getSingleton('eav/config')->getEntityType(Mage_Sales_Model_Order::ENTITY)->getId();
// when not being incremented by store, entity store table uses the admin store
$adminId = Mage_Core_Model_App::ADMIN_STORE_ID;
$entityStore = Mage::getModel('eav/entity_store')->loadByEntityStore($orderTypeId, $adminId);
// if the record doesn't exist yet, fill in necessary data for the record to be saved
// and associated with the admin store and order entity type
if (!$entityStore->getId()) {
	$entityStore->setData(array(
		'entity_type_id' => $orderTypeId,
		'store_id' => $adminId,
	));
}
// null out the prefix and save the record
$entityStore->setIncrementPrefix(null)->save();

$installer->endSetup();
