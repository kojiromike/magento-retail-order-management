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

// Remove the size attribute if it is already exist.
$installer->removeAttribute(Mage_Catalog_Model_Product::ENTITY, 'size');

// add the size attribute as a select input box
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'size', [
    'type' => 'int',
    'group' => 'General',
    'label' => 'Size',
    'input' => 'select',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,bundle',
    'visible_on_front' => false,
    'used_in_product_listing' => false
]);
