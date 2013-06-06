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

	const EB2C_INVENTORY_DEVELOPER_MODE = 'eb2c/inventory/developerMode';
	const EB2C_INVENTORY_DEVELOPER_MODE_QTY_API_URI = 'eb2c/inventory/quantityApiUri';
	const EB2C_INVENTORY_DEVELOPER_MODE_DETAIL_API_URI = 'eb2c/inventory/inventoryDetailUri';

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
	 * isDeveloperMode method
	 */
	public function isDeveloperMode($store=null)
	{
		return Mage::getStoreConfigFlag(
			self::EB2C_INVENTORY_DEVELOPER_MODE,
			$store
		);
	}

	/**
	 * getDeveloperModeQtyApiUri method
	 */
	public function getDeveloperModeQtyApiUri($store=null)
	{
		return Mage::getStoreConfig(
			self::EB2C_INVENTORY_DEVELOPER_MODE_QTY_API_URI,
			$store
		);
	}

	/**
	 * getDeveloperModeDetailApiUri method
	 */
	public function getDeveloperModeDetailApiUri($store=null)
	{
		return Mage::getStoreConfig(
			self::EB2C_INVENTORY_DEVELOPER_MODE_DETAIL_API_URI,
			$store
		);
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
		$apiUri = $this->getDeveloperModeQtyApiUri();
		if (!$this->isDeveloperMode()){
			$apiUri = sprintf(
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
		return $apiUri;
	}

	/**
	 * getInventoryDetailsUri method
	 */
	public function getInventoryDetailsUri()
	{
		$apiUri = $this->getDeveloperModeDetailApiUri();
		if (!$this->isDeveloperMode()){
			$apiUri = sprintf(
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
		return $apiUri;
	}
}
