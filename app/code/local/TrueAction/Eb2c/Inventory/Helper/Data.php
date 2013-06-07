<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Helper_Data extends Mage_Core_Helper_Abstract
{
	const XMLNS = 'http://api.gsicommerce.com/schema/checkout/1.0';
	const ENV = 'developer';
	const REGION = 'na';
	const VERSION = 'v1.10';
	const SERVICE = 'inventory';
	const OPT_QTY = 'quantity';
	const OPT_INV_DETAILS = 'inventoryDetails';
	const URI_FROMAT = 'https://%s.%s.gsipartners.com/%s/stores/%s/%s/%s.%s';
	const RETURN_FORMAT = 'xml';

	const DEV_MODE = 'eb2c/inventory/developerMode';
	const DEV_MODE_QTY_API_URI = 'eb2c/inventory/quantityApiUri';
	const DEV_MODE_DETAIL_API_URI = 'eb2c/inventory/inventoryDetailUri';

	public $coreHelper;

	/**
	 * Get core helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Core_Helper_Data
	 */
	public function getCoreHelper()
	{
		if (!$this->coreHelper) {
			$this->coreHelper = Mage::helper('eb2ccore');
		}
		return $this->coreHelper;
	}

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
		return self::XMLNS;
	}

	/**
	 * getQuantityUri method
	 */
	public function getQuantityUri()
	{
		$apiUri = Mage::getStoreConfig(self::DEV_MODE_QTY_API_URI, null);
		if (!Mage::getStoreConfigFlag(self::DEV_MODE, null)) {
			$apiUri = sprintf(
				self::URI_FROMAT,
				self::ENV,
				self::REGION,
				self::VERSION,
				Mage::app()->getStore()->getStoreId(),
				self::SERVICE,
				self::OPT_QTY,
				self::RETURN_FORMAT
			);
		}
		return $apiUri;
	}

	/**
	 * getInventoryDetailsUri method
	 */
	public function getInventoryDetailsUri()
	{
		$apiUri = Mage::getStoreConfig(self::DEV_MODE_DETAIL_API_URI, null);
		if (!Mage::getStoreConfigFlag(self::DEV_MODE, null)) {
			$apiUri = sprintf(
				self::URI_FROMAT,
				self::ENV,
				self::REGION,
				self::VERSION,
				Mage::app()->getStore()->getStoreId(),
				self::SERVICE,
				self::OPT_INV_DETAILS,
				self::RETURN_FORMAT
			);
		}
		return $apiUri;
	}
}
