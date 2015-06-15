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

/** Update formerly added Attributes to not be used in the product listing */
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'isp_eligible', 'used_in_product_listing', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'isp_reserve_eligible', 'used_in_product_listing', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'inventory_check_eligible', 'used_in_product_listing', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'sfs_eligible', 'used_in_product_listing', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'sts_eligible', 'used_in_product_listing', false);

/** Update formerly added Attributes to not be visible on the front end */
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'isp_eligible', 'is_visible_on_front', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'isp_reserve_eligible', 'is_visible_on_front', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'inventory_check_eligible', 'is_visible_on_front', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'sfs_eligible', 'is_visible_on_front', false);
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'sts_eligible', 'is_visible_on_front', false);
