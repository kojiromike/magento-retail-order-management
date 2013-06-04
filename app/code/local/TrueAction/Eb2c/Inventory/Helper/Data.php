<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Helper_Data extends Mage_Core_Helper_Abstract
{
	const EB2C_INVENTORY_XMLNS = 'http://api.gsicommerce.com/schema/checkout/1.0';
	const EB2C_INVENTORY_ENV = 'developer';
	const EB2C_INVENTORY_REGION = 'na';
	const EB2C_INVENTORY_VERSION = 'v1.10';
	const EB2C_INVENTORY_SERVICE = 'inventory';
	const EB2C_INVENTORY_OPT_QTY = 'quantity';
	const EB2C_INVENTORY_OPT_INV_DETAILS = 'inventoryDetails';
	const EB2C_URI_FROMAT = 'https://%s.%s.gsipartners.com/%s/stores/%s/%s/%s.%s';
	const EB2C_INVENTORY_RETURN_FORMAT = 'xml';

	/**
	 * Get Dom instantiated object.
	 *
	 * @return TrueAction_Dom_Document
	 */
	public function getDomDocument()
	{
		return new TrueAction_Dom_Document('1.0', 'UTF-8');
	}

	/**
	 * getXmlNs method
	 */
	public function getXmlNs()
	{
		return self::EB2C_INVENTORY_XMLNS;
	}

	/**
	 * getQuantityUri method
	 */
	public function getQuantityUri()
	{
		return sprintf(
			self::EB2C_URI_FROMAT,
			self::EB2C_INVENTORY_ENV,
			self::EB2C_INVENTORY_REGION,
			self::EB2C_INVENTORY_VERSION,
			Mage::app()->getStore()->getStoreId(),
			self::EB2C_INVENTORY_SERVICE,
			self::EB2C_INVENTORY_OPT_QTY,
			self::EB2C_INVENTORY_RETURN_FORMAT
		);
	}

	/**
	 * getInventoryDetailsUri method
	 */
	public function getInventoryDetailsUri()
	{
		return sprintf(
			self::EB2C_URI_FROMAT,
			self::EB2C_INVENTORY_ENV,
			self::EB2C_INVENTORY_REGION,
			self::EB2C_INVENTORY_VERSION,
			Mage::app()->getStore()->getStoreId(),
			self::EB2C_INVENTORY_SERVICE,
			self::EB2C_INVENTORY_OPT_INV_DETAILS,
			self::EB2C_INVENTORY_RETURN_FORMAT
		);
	}
}
