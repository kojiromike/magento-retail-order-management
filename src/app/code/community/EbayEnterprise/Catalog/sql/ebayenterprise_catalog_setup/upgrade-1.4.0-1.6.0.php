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

/** @var Mage_Catalog_Model_Resource_Setup $installer */
$installer = $this;

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'catalog_class', [
    'type' => 'int',
    'group' => 'Retail Order Management',
    'label' => 'Catalog Class',
    'input' => 'select',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'backend'    => 'eav/entity_attribute_backend_array',
    'option'     => [
        'values' => [
            'always',
            'regular',
            'nosale',
        ],
    ],
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => 'regular',
    'apply_to' => 'simple,virtual,bundle,downloadable,giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => false,
]);

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'item_status', [
    'type' => 'int',
    'group' => 'Retail Order Management',
    'label' => 'Item Status',
    'input' => 'select',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'backend'    => 'eav/entity_attribute_backend_array',
    'option'     => [
        'values' => [
            'Active',
            'Discontinued',
            'Inactive',
        ],
    ],
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => 'Active',
    'apply_to' => 'simple,virtual,bundle,downloadable,giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => false,
]);

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'street_date', [
    'type' => 'datetime',
    'group' => 'Retail Order Management',
    'label' => 'Street Date',
    'input' => 'date',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,virtual,bundle,downloadable,giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => false,
]);

/** Update formerly added Attributes to not be used in the product listing */
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'size', 'used_in_product_listing', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'style_id', 'used_in_product_listing', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'is_clean', 'used_in_product_listing', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'unresolved_product_links', 'used_in_product_listing', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'hts_codes', 'used_in_product_listing', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'tax_code', 'used_in_product_listing', false);
