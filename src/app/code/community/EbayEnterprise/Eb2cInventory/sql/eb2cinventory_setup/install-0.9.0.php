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
 * Uses a Mage_Sales_Model_Resource_Setup because that's the class responsible for setting up
 * and managing each of the entities we are adding to, including doing the right things if
 * there's a "flat_" version of that entity.
 *
 * @var $installer Mage_Sales_Model_Resource_Setup
 */
$installer = $this;
$installer->startSetup();
$entities = array('order_item', 'quote_address_item', 'quote_item');

$inventoryAttributes = array(
    'reservation_id'                  => Varien_Db_Ddl_Table::TYPE_TEXT,
    'reservation_expires'             => Varien_Db_Ddl_Table::TYPE_DATETIME,
    'qty_reserved'                    => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'creation_time'                   => Varien_Db_Ddl_Table::TYPE_DATETIME,
    'display'                         => Varien_Db_Ddl_Table::TYPE_TEXT,
    'delivery_window_from'            => Varien_Db_Ddl_Table::TYPE_DATETIME,
    'delivery_window_to'              => Varien_Db_Ddl_Table::TYPE_DATETIME,
    'shipping_window_from'            => Varien_Db_Ddl_Table::TYPE_DATETIME,
    'shipping_window_to'              => Varien_Db_Ddl_Table::TYPE_DATETIME,
    'ship_from_address_line1'         => Varien_Db_Ddl_Table::TYPE_TEXT,
    'ship_from_address_line2'         => Varien_Db_Ddl_Table::TYPE_TEXT,
    'ship_from_address_line3'         => Varien_Db_Ddl_Table::TYPE_TEXT,
    'ship_from_address_line4'         => Varien_Db_Ddl_Table::TYPE_TEXT,
    'ship_from_address_city'          => Varien_Db_Ddl_Table::TYPE_TEXT,
    'ship_from_address_main_division' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'ship_from_address_country_code'  => Varien_Db_Ddl_Table::TYPE_TEXT,
    'ship_from_address_postal_code'   => Varien_Db_Ddl_Table::TYPE_TEXT,
);

$attributePrefix = 'eb2c_';
foreach ($entities as $entity) {
    foreach ($inventoryAttributes as $name => $type) {
        $options = array('type' => $type, 'visible'  => true, 'required' => false);
        $installer->addAttribute($entity, $attributePrefix . $name, $options);
    }
}
$installer->endSetup();
