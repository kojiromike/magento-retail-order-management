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

// Update all ROM product import attributes with the proper data type
$attributes = array(
	'size' => 'varchar',
	'style_id' => 'varchar',
	'is_clean' => 'int',
	'unresolved_product_links' => 'text',
	'hts_codes' => 'text',
	'tax_code' => 'varchar'
);
foreach ($attributes as $code => $value) {
	/** @see Mage_Eav_Model_Entity_Setup::updateAttribute **/
	$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, $code, 'type', $value);
}
