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

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'item_type', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Item Type',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => true,
    'user_defined' => false,
    'default' => 'Merch',
    'apply_to' => 'simple,configurable,virtual,bundle,downloadable,giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'drop_shipped', array(
    'type' => 'int',
    'group' => 'Retail Order Management',
    'label' => 'Drop Shipped',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => true,
    'user_defined' => false,
    'default' => '0',
    'apply_to' => 'simple,configurable,bundle',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'drop_ship_supplier_name', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Drop Ship Supplier Name',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,bundle',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'drop_ship_supplier_number', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Drop Ship Supplier Number',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,bundle',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'drop_ship_supplier_part_number', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Drop Ship Supplier Part Number',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,bundle',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'hierarchy_dept_number', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Hierarchy Department Number',
    'input' => 'text',
    'frontend_class' => 'validate-digits',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => true,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,virtual,bundle,downloadable,giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'hierarchy_dept_description', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Hierarchy Department Description',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,virtual,bundle,downloadable,giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'hierarchy_subdept_number', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Hierarchy Subdepartment Number',
    'input' => 'text',
    'frontend_class' => 'validate-digits',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => true,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,virtual,bundle,downloadable,giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'hierarchy_subdept_description', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Hierarchy Subdepartment Description',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,virtual,bundle,downloadable,giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'hierarchy_class_number', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Hierarchy Class Number',
    'input' => 'text',
    'frontend_class' => 'validate-digits',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => true,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,virtual,bundle,downloadable,giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'hierarchy_class_description', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Hierarchy Class Description',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,virtual,bundle,downloadable,giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'hierarchy_subclass_number', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Hierarchy Subclass Number',
    'input' => 'text',
    'frontend_class' => 'validate-digits',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => true,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,virtual,bundle,downloadable,giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'hierarchy_subclass_description', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Hierarchy Subclass Description',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,virtual,bundle,downloadable,giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'gift_card_tender_code', array(
    'type' => 'varchar',
    'group' => 'Retail Order Management',
    'label' => 'Gift Card Tender Code',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'giftcard',
    'visible_on_front' => false,
    'used_in_product_listing' => true,
    'searchable' => 0,
    'visible_in_advanced_search' => 0,
    'comparable' => 0
));
