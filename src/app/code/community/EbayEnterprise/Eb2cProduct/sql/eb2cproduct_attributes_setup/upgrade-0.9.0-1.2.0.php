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

// need to use a different setup model (should be configured this way but isn't)
$installer = Mage::getResourceModel('eb2cproduct/eav_entity_setup', 'eb2cproduct_attributes_setup');

$installer->startSetup();

$productEntityId = $installer->getEntityTypeId(Mage_Catalog_Model_Product::ENTITY);
// only add the attribute if it doesn't already exist
if ($installer->getAttributeId($productEntityId, 'gift_card_tender_code') === false) {
	// get all of the processed attribute creation configuration - this is currently
	// the only way of getting the model to process the config to include default values
	$allAttributes = Mage::getModel('eb2cproduct/attributes')->getAttributesData();
	// get the gift_card_tender_tcodeattribute configuration and create the attribute
	$tenderTypeAttributeConfig = $allAttributes['gift_card_tender_code'];
	$installer->addAttribute($productEntityId, 'gift_card_tender_code', $tenderTypeAttributeConfig);
}

$installer->endSetup();
