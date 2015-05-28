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

/** @var $installer EbayEnterprise_Order_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

// Field to hold OrderCreateRequest XML for Create Retries:
$installer->addAttribute('order', 'eb2c_order_create_request', [
	'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
	'visible' => true,
	'required' => false,
]);

/**
 * set the order entity to use a single pool of increment ids - increment_per_store
 * set to false (prevents duplicate increment ids when multiple stores use same
 * client order id prefix) - and a custom increment model to add the configured
 * client order id prefixes - increment_model set to alias of the desired model.
 */
$installer->updateEntityType(Mage_Sales_Model_Order::ENTITY, 'increment_per_store', false);
$installer->updateEntityType(Mage_Sales_Model_Order::ENTITY, 'increment_model', 'ebayenterprise_order/eav_entity_increment_order');

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
	$entityStore->setData([
		'entity_type_id' => $orderTypeId,
		'store_id' => $adminId,
	]);
}
$entityStore->setIncrementPrefix(null)->save();

/**
 * Installing 'unsubmitted' status associated with a state 'new' for the
 * purpose of flagging when an order fail to submit successfully to the ROM API.
 * The initial status and state of the order will be 'unsubmitted' and 'new', respectively.
 * When the ROM API return success the order status will be set to 'pending'
 * while it state remained unchanged as 'new'. However,
 * when the ROM API fail due to some transient failure, the status will be set
 * to 'unsubmitted'. When order retry run it will look for order with state
 * 'new' and status 'unsubmitted'.
 */
$status = 'unsubmitted';
$label = 'Unsubmitted';
$isDefault = 1;

$statusFields = ['status', 'label'];
$statusValues = [$status, $label];
$statusTbl = $installer->getTable('sales/order_status');
/** @var $conn Magento_Db_Adapter_Pdo_Mysql */
try {
	$conn->insertArray($statusTbl, $statusFields, [array_combine($statusFields, $statusValues)]);
}
catch (Exception $e) {
	// If the 'unsubmitted' status already exists, this will throw a PRIMARY KEY violation error.
	// We don't want to fail completely if that happens.
}

$stateFields = ['status', 'state', 'is_default'];
$stateValues = [$status, Mage_Sales_Model_Order::STATE_NEW, $isDefault];
$stateTbl = $installer->getTable('sales/order_status_state');
try {
	$conn->insertArray($stateTbl, $stateFields, [array_combine($stateFields, $stateValues)]);
}
catch (Exception $e) {
	// If this status/ state combination already exists, this will throw a PRIMARY KEY violation error.
	// We don't want to fail completely if that happens.
}

$installer->endSetup();
