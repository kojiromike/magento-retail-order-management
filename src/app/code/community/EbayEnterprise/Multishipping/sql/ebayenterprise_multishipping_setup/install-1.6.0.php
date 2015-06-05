<?php
/**
 * Copyright (c) 2013-2015 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2015 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/** @var Mage_Sales_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

// Attribute options definitions for common attribute types.
$decimalOptions = [
    'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
    'length' => '12,4',
];
$foreignKeyOptions = [
    'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'unsigned' => true,
];
$integerOptions = [
    'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
];
$varcharOptions = [
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length' => 255,
];

// Update sales_flat_order_address table to contain data from the
// sales_flat_quote_address the order address was created from as well
// as shipping level totals for sales_flat_order totals.
$orderAddressAttributes = [
    'base_grand_total' => $decimalOptions,
    'base_shipping_amount'=> $decimalOptions,
    'base_shipping_discount_amount'=> $decimalOptions,
    'base_shipping_hidden_tax_amnt'=> $decimalOptions,
    'base_shipping_incl_tax' => $decimalOptions,
    'base_shipping_tax_amount' => $decimalOptions,
    'gift_message_id' => $foreignKeyOptions,
    'grand_total' => $decimalOptions,
    'gw_add_card' => $integerOptions,
    'gw_allow_gift_receipt' => $integerOptions,
    'gw_base_price' => $decimalOptions,
    'gw_base_tax_amount' => $decimalOptions,
    'gw_card_base_price' => $decimalOptions,
    'gw_card_base_tax_amount' => $decimalOptions,
    'gw_card_price' => $decimalOptions,
    'gw_card_tax_amount' => $decimalOptions,
    'gw_id' => $foreignKeyOptions,
    'gw_items_base_price' => $decimalOptions,
    'gw_items_base_tax_amount' => $decimalOptions,
    'gw_items_price' => $decimalOptions,
    'gw_items_tax_amount' => $decimalOptions,
    'gw_price' => $decimalOptions,
    'gw_tax_amount' => $decimalOptions,
    'shipping_amount' => $decimalOptions,
    'shipping_description' => $varcharOptions,
    'shipping_discount_amount' => $decimalOptions,
    'shipping_hidden_tax_amount' => $decimalOptions,
    'shipping_incl_tax' => $decimalOptions,
    'shipping_method' => $varcharOptions,
    'shipping_tax_amount' => $decimalOptions,
];

foreach ($orderAddressAttributes as $code => $options) {
    $installer->addAttribute('order_address', $code, $options);
}

$connection = $installer->getConnection();
$salesOrderAddressTable = $installer->getTable('sales/order_address');

// Add FKs from the sales_flat_order_address table.
$connection->addForeignKey(
    $installer->getFkName(
        'sales/order_address',
        'gift_message_id',
        'giftmessage/message',
        'gift_message_id'
    ),
    $salesOrderAddressTable,
    'gift_message_id',
    $installer->getTable('giftmessage/message'),
    'gift_message_id'
);

// Enterprise gift wrapping - in case it isn't installed, only add the FK if
// the table the FK would be to exists.
if ($connection->isTableExists('enterprise_giftwrapping/wrapping')) {
    $connection->addForeignKey(
        $installer->getFkName(
            'sales/order_address',
            'gw_id',
            'enterprise_giftwrapping/wrapping',
            'wrapping_id'
        ),
        $salesOrderAddressTable,
        'gw_id',
        $installer->getTable('enterprise_giftwrapping/wrapping'),
        'wrapping_id'
    );
}

// Update sales_flat_order_item table to include FK to sales_flat_order_address
$installer->addAttribute('order_item', 'order_address_id', $foreignKeyOptions);

$connection->addForeignKey(
    $installer->getFkName(
        'sales/order_item',
        'order_address_id',
        'sales/order_address',
        'entity_id'
    ),
    $installer->getTable('sales/order_item'),
    'order_address_id',
    $salesOrderAddressTable,
    'entity_id'
);

$installer->endSetup();
