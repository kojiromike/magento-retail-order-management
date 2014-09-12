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

//** @var $installer Mage_Catalog_Model_Resource_Setup */
$installer = $this;

$attributeName = 'gift_card_tender_code';
$entityType = Mage_Catalog_Model_Product::ENTITY;

// Update the `gift_card_tender_code` attribute to be required.
$this->updateAttribute($entityType, $attributeName, 'is_required', 1);
// Set maximum character length validation for attribute `gift_card_tender_code` to 3.
$this->updateAttribute($entityType, $attributeName, 'frontend_class', 'validate-length maximum-length-3');
