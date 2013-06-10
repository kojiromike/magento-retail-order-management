<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $coreHelper;
	public $constantHelper;

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
	 * Get Constants helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Helper_Constants
	 */
	public function getConstantHelper()
	{
		if (!$this->constantHelper) {
			$this->constantHelper = Mage::helper('eb2c_inventory/constants');
		}
		return $this->constantHelper;
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
	 * Getting the NS constant value
	 *
	 * @return string, the ns value
	 */
	public function getXmlNs()
	{
		$constantHelper = $this->getConstantHelper();
		return $constantHelper::XMLNS;
	}

	/**
	 * Generate eb2c api quantity api uri from config settings and constansts
	 *
	 * @return string, the quantity uri
	 */
	public function getQuantityUri()
	{
		$coreHelper = $this->getCoreHelper();
		$constantHelper = $this->getConstantHelper();
		$apiUri = Mage::getStoreConfig($constantHelper::DEV_MODE_QTY_API_URI, null);
		if (!Mage::getStoreConfigFlag($constantHelper::DEV_MODE, null)) {
			$apiUri = sprintf(
				$constantHelper::URI_FROMAT,
				$constantHelper::ENV,
				$constantHelper::REGION,
				$constantHelper::VERSION,
				Mage::getStoreConfig($coreHelper::STORE_ID, null),
				$constantHelper::SERVICE,
				$constantHelper::OPT_QTY,
				$constantHelper::RETURN_FORMAT
			);
		}
		return $apiUri;
	}

	/**
	 * Generate eb2c api inventory detail api uri from config settings and constansts
	 *
	 * @return string, the inventory detail uri
	 */
	public function getInventoryDetailsUri()
	{
		$coreHelper = $this->getCoreHelper();
		$constantHelper = $this->getConstantHelper();
		$apiUri = Mage::getStoreConfig($constantHelper::DEV_MODE_DETAIL_API_URI, null);
		if (!Mage::getStoreConfigFlag($constantHelper::DEV_MODE, null)) {
			$apiUri = sprintf(
				$constantHelper::URI_FROMAT,
				$constantHelper::ENV,
				$constantHelper::REGION,
				$constantHelper::VERSION,
				Mage::getStoreConfig($coreHelper::STORE_ID, null),
				$constantHelper::SERVICE,
				$constantHelper::OPT_INV_DETAILS,
				$constantHelper::RETURN_FORMAT
			);
		}
		return $apiUri;
	}

	/**
	 * Generate eb2c api allocation api uri from config settings and constansts
	 *
	 * @return string, the allocation uri
	 */
	public function getAllocationUri()
	{
		$coreHelper = $this->getCoreHelper();
		$constantHelper = $this->getConstantHelper();
		$apiUri = Mage::getStoreConfig($constantHelper::DEV_MODE_ALLOCATION_API_URI, null);
		if (!Mage::getStoreConfigFlag($constantHelper::DEV_MODE, null)) {
			$apiUri = sprintf(
				$constantHelper::URI_FROMAT,
				$constantHelper::ENV,
				$constantHelper::REGION,
				$constantHelper::VERSION,
				Mage::getStoreConfig($coreHelper::STORE_ID, null),
				$constantHelper::SERVICE,
				$constantHelper::OPT_ALLOCATION,
				$constantHelper::RETURN_FORMAT
			);
		}
		return $apiUri;
	}

	/**
	 * Generate eb2c api Universally unique ID used to globally identify to request.
	 *
	 * @param int $entityId, the magento sales_flat_order primary key
	 * @param string $incrementId, the magento unique incrimental value
	 *
	 * @return string, the request id
	 */
	public function getRequestId($entityId, $incrementId)
	{
		$coreHelper = $this->getCoreHelper();

		return implode('-', array(
			Mage::getStoreConfig($coreHelper::CLIENT_ID, null),
			Mage::getStoreConfig($coreHelper::STORE_ID, null),
			$entityId,
			$incrementId
		));
	}

	/**
	 * Generate eb2c api Universally unique ID to represent the reservation.
	 *
	 * @param int $entityId, the magento sales_flat_order primary key
	 * @param string $incrementId, the magento unique incrimental value
	 *
	 * @return string, the reservation id
	 */
	public function getReservationId($entityId, $incrementId)
	{
		$coreHelper = $this->getCoreHelper();

		return implode('-', array(
			Mage::getStoreConfig($coreHelper::CLIENT_ID, null),
			Mage::getStoreConfig($coreHelper::STORE_ID, null),
			$entityId,
			$incrementId
		));
	}
}
