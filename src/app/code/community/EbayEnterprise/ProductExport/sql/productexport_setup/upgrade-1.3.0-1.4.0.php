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

// @var $this Mage_Catalog_Model_Resource_Setup
$this->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'sales_class', array(
	'apply_to'                => 'simple,configurable,virtual,bundle,downloadable,giftcard',
	'global'                  => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
	'group'                   => 'Retail Order Management',
	'input'                   => 'select',
	'label'                   => 'Sales Class',
	'required'                => true,
	'source'                  => 'eav/entity_attribute_source_table',
	'type'                    => 'int',
	'used_in_product_listing' => false,
	'user_defined'            => false,
	'visible'                 => true,
	'visible_on_front'        => false,
	/**
	 * This is a select dropdown.
	 * 'option' is the reserved Magento field name that means 'the set of options for this attribute'
	 * 'value' is the reserved Magento array key that holds the array of possible option-values for this attribute
	 * 'optionId_x': the array keys of 'value'. These are cast to int by Magento to determine if these are new (0)
	 * 		or existing (non-zero) options. Since we're an install script, we want to these to always evaluate to 0.
	 * Finally, the array that each 'optionId_x' holds is a set of 'storeId' => 'option_value' pairs.
	 */
	'option' => array(
		'value' => array(
			'optionId_0' => array(0 => 'stock' /*, 1 => 'Option for Store 1', 2 => 'Option for Store 2' */),
			'optionId_1' => array(0 => 'advanceOrderOpen'),
			'optionId_2' => array(0 => 'advanceOrderLimited'),
			'optionId_3' => array(0 => 'backOrderLimited'),
		)
	),
));

/**
 * In order to set a default option for input='select' attribute, we need to use the id of that option.
 * However, we don't know that until _after_ we've created and saved it. So we have to get the option
 * to determine its option id, and save that option id back to the attribute's default_value field.
 */
$model = Mage::getModel('eav/entity_attribute')
	->loadByCode(Mage_Catalog_Model_Product::ENTITY, 'sales_class');
$model->setDefaultValue($model->getSource()->getOptionId('stock'))
	->save();

