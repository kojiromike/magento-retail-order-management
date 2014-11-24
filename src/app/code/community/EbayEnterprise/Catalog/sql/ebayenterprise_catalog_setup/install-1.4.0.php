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

$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'size', array(
	'type' => 'varchar',
	'group' => 'General',
	'label' => 'Size',
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
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'style_id', array(
	'type' => 'varchar',
	'group' => 'General',
	'label' => 'Style Id',
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
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'is_clean', array(
	'type' => 'int',
	'group' => 'Retail Order Management',
	'label' => 'Is Clean',
	'input' => 'select',
	'source' => 'eav/entity_attribute_source_boolean',
	'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
	'visible' => true,
	'required' => false,
	'user_defined' => false,
	'default' => '',
	'apply_to' => 'simple,configurable,virtual,bundle,downloadable,giftcard',
	'visible_on_front' => false,
	'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'unresolved_product_links', array(
	'type' => 'text',
	'group' => 'Retail Order Management',
	'label' => 'Unresolved Product Links',
	'input' => 'textarea',
	'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
	'visible' => true,
	'required' => false,
	'user_defined' => false,
	'default' => '',
	'apply_to' => 'simple,configurable,virtual,bundle,downloadable,giftcard',
	'visible_on_front' => false,
	'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'hts_codes', array(
	'type' => 'text',
	'group' => 'Retail Order Management',
	'label' => 'HTS Codes',
	'input' => 'textarea',
	'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
	'visible' => true,
	'required' => false,
	'user_defined' => false,
	'default' => '',
	'apply_to' => 'simple,configurable,virtual,bundle,downloadable,giftcard',
	'visible_on_front' => false,
	'used_in_product_listing' => true
));
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'tax_code', array(
	'type' => 'varchar',
	'group' => 'Prices',
	'label' => 'Tax Code',
	'input' => 'text',
	'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
	'visible' => true,
	'required' => true,
	'user_defined' => false,
	'default' => '',
	'apply_to' => 'simple,virtual,bundle,downloadable,giftcard',
	'visible_on_front' => false,
	'used_in_product_listing' => true
));

/**
 * Renamed EbayEnterprise_Eb2cProduct module to EbayEnterprise_Catalog.
 */
/** @see Mage_Core_Model_Resource_Setup::deleteTableRow() **/
$installer->deleteTableRow('core/resource', 'code', 'eb2cproduct_setup');
