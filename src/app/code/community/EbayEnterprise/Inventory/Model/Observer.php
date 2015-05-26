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

class EbayEnterprise_Inventory_Model_Observer
{
	/** @var EbayEnterprise_Inventory_Model_Quantity_Service */
	protected $_quantityService;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_logContext;

	/**
	 * @param array $args May contain:
	 *                    - quantity_service => EbayEnterprise_Inventory_Model_Quantity_Service
	 *                    - logger => EbayEnterprise_MageLog_Helper_Data
	 *                    - log_context => EbayEnterprise_MageLog_Helper_Context
	 */
	public function __construct(array $args=[])
	{
		list(
			$this->_quantityService,
			$this->_logger,
			$this->_logContext
		) = $this->_checkTypes(
			$this->_nullCoalesce($args, 'quantity_service', Mage::getModel('ebayenterprise_inventory/quantity_service')),
			$this->_nullCoalesce($args, 'logger', Mage::helper('ebayenterprise_magelog')),
			$this->_nullCoalesce($args, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
		);
	}

	/**
	 * Enforce type checks on constructor init params.
	 *
	 * @param EbayEnterprise_Inventory_Model_Quantity_Service
	 * @param EbayEnterprise_MageLog_Helper_Data
	 * @param EbayEnterprise_MageLog_Helper_Context
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_Inventory_Model_Quantity_Service $quantityService,
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_MageLog_Helper_Context $logContext
	) {
		return func_get_args();
	}

	/**
	 * Fill in default values.
	 *
	 * @param string
	 * @param array
	 * @param mixed
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $key, $default)
	{
		return isset($arr[$key]) ? $arr[$key] : $default;
	}

	/**
	 * Before collecting item totals, check that all items
	 * in the quote are available to be fulfilled.
	 *
	 * @param Varien_Event_Observer
	 * @return self
	 */
	public function handleBeforeCollectTotals(Varien_Event_Observer $observer)
	{
		try {
			$this->_quantityService
				->checkQuoteInventory($observer->getEvent()->getQuote());
		} catch (EbayEnterprise_Inventory_Exception_Quantity_Collector_Exception $e) {
			$this->_logger->warning($e->getMessage(), $this->_logContext->getMetaData(__CLASS__, [], $e));
		}
		return $this;
	}
}
