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


$installer = new Mage_Sales_Model_Resource_Setup('core_setup');
$installer->startSetup();
try{
	$entities = array(
		'order_item',
		'quote_address_item',
		'quote_item',
	);

	$inventoryAttributes = array(
		array(
			'name' => 'reservation_id',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'reservation_expires',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'qty_reserved',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'creation_time',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'display',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'delivery_window_from',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'delivery_window_to',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'shipping_window_from',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'shipping_window_to',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_line1',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_line2',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_line3',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_line4',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_city',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_main_division',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_country_code',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
		array(
			'name' => 'ship_from_address_postal_code',
			'options' => array (
				'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
				'visible' => true,
				'required' => false,
			)
		),
	);

	$pfx = 'eb2c_';

	foreach ($entities as $entity) {
		foreach ($inventoryAttributes as $a) {
			$installer->addAttribute(
				$entity,
				$pfx . $a['name'],
				$a['options']
			);
		}
	}
} catch (Exception $e) {
	Mage::logException($e);
}
$installer->endSetup();
