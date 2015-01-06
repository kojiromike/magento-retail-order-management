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

$attributes = array(
	'isp_eligible' => 'In-store Pickup Eligible',
	'isp_reserve_eligible' => 'In-store Pickup Reservation Eligible',
	'inventory_check_eligible' => 'Inventory Lookup Eligible',
	'sfs_eligible' => 'Ship-from-store Eligible',
	'sts_eligible' => 'Ship-to-store Eligible',
);
$attributeConfig = array(
	'type' => 'int',
	'group' => 'Retail Order Management',
	'input' => 'select',
	'source' => 'eav/entity_attribute_source_boolean',
	'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
	'visible' => true,
	'required' => false,
	'user_defined' => false,
	'default' => '0',
	'apply_to' => 'simple,configurable,bundle,giftcard',
	'visible_on_front' => true,
	'used_in_product_listing' => true
);
foreach ($attributes as $attribute => $label) {
	$attributeConfig['label'] = $label;
	$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute, $attributeConfig);
}
