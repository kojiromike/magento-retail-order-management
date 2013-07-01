<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $coreHelper;
	public $coreConfigHelper;
	public $configModel;
	public $constantHelper;
	protected $_operation;

	public function __construct()
	{
		$this->coreHelper = $this->getCoreHelper();
		$this->coreConfigHelper = $this->getCoreConfigHelper(null);
		$this->configModel = $this->getConfigModel(null);
		$this->constantHelper = $this->getConstantHelper();
		$constantHelper = $this->getConstantHelper();
		$this->_operation = array(
			'check_quantity' => array(
				'pro' => $constantHelper::OPT_QTY,
				'dev' => $this->getConfigModel()->quantity_api_uri
			),
			'get_inventory_details' => array(
				'pro' => $constantHelper::OPT_INV_DETAILS,
				'dev' => $this->getConfigModel()->inventory_detail_uri
			),
			'allocate_inventory' => array(
				'pro' => $constantHelper::OPT_ALLOCATION,
				'dev' => $this->getConfigModel()->allocation_uri
			),
			'rollback_allocation' => array(
				'pro' => $constantHelper::OPT_ROLLBACK_ALLOCATION,
				'dev' => $this->getConfigModel()->rollback_allocation_uri
			)
		);
	}

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
	 * Get core helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Core_Helper_Data
	 */
	public function getCoreConfigHelper($store=null)
	{
		if (!$this->coreConfigHelper) {
			$this->coreConfigHelper = Mage::getModel('eb2ccore/config_registry');
			$this->coreConfigHelper->setStore($store)
				->addConfigModel(Mage::getModel('eb2ccore/config'));
		}
		return $this->coreConfigHelper;
	}

	/**
	 * Get inventory config instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Model_Config
	 */
	public function getConfigModel($store=null)
	{
		if (!$this->configModel) {
			$this->configModel = Mage::getModel('eb2ccore/config_registry');
			$this->configModel->setStore($store)
				->addConfigModel(Mage::getModel('eb2cinventory/config'));
		}
		return $this->configModel;
	}

	/**
	 * Get Constants helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Helper_Constants
	 */
	public function getConstantHelper()
	{
		if (!$this->constantHelper) {
			$this->constantHelper = Mage::helper('eb2cinventory/constants');
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
	 * Generate eb2c API operation Uri from configuration settings and constants
	 * @param string $optIndex, the operation index of the associative array
	 *
	 * @return string, the generated operation Uri
	 */
	public function getOperationUri($optIndex)
	{
		$operation = '';
		if (isset($this->_operation[$optIndex])) {
			$operation = $this->_operation[$optIndex];
		}
		$constantHelper = $this->getConstantHelper();
		$apiUri = $operation['dev'];
		if (!(bool) $this->getConfigModel()->developer_mode) {
			$apiUri = $this->getCoreHelper()->apiUri(
				$constantHelper::SERVICE,
				$operation['pro']
			);
		}
		return $apiUri;
	}

	/**
	 * Generate eb2c API Universally unique ID used to globally identify to request.
	 *
	 * @param int $entityId, the magento sales_flat_order primary key
	 *
	 * @return string, the request id
	 */
	public function getRequestId($entityId)
	{
		return implode('-', array(
			$this->getCoreConfigHelper()->client_id,
			$this->getCoreConfigHelper()->store_id,
			$entityId
		));
	}

	/**
	 * Generate eb2c API Universally unique ID to represent the reservation.
	 *
	 * @param int $entityId, the magento sales_flat_order primary key
	 *
	 * @return string, the reservation id
	 */
	public function getReservationId($entityId)
	{
		return implode('-', array(
			$this->getCoreConfigHelper()->client_id,
			$this->getCoreConfigHelper()->store_id,
			$entityId
		));
	}
}
