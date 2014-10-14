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

class EbayEnterprise_Eb2cInventory_Model_Observer
{
	const CANNOT_ADD_TO_CART_MESSAGE = 'EbayEnterprise_Eb2cInventory_Cannot_Add_To_Cart_Message';
	const ALLOCATION_ERROR_MESSAGE = 'EbayEnterprise_Eb2cInventory_Allocation_Error_Message';
	/**
	 * Validate the quote against inventory service calls. When items or item quantities in the quote
	 * have changed, this method will trigger a new inventory quantity service call and have the quote
	 * updated with the results. When shipping or item/item quantity changes are detected, a new
	 * inventory details request should be made only if the the quote shipping address has the required data.
	 *
	 * Failures in related methods, those actually making the inventory service calls
	 * and updating the quote, can signal for the process to be interrupted which will
	 * cause this method to throw a Mage_Core_Exception.
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 * @throws EbayEnterprise_Eb2cInventory_Exception_Cart_Interrupt If any of the service calls fail with a blocking
	 *         status
	 * @throws EbayEnterprise_Eb2cInventory_Exception_Cart If any of the service calls fail with a non-blocking status
	 */
	public function checkInventory(Varien_Event_Observer $observer)
	{
		$coreSession = Mage::getSingleton('eb2ccore/session');
		$quote = $observer->getEvent()->getQuote();
		$qtyRequired = $coreSession->isQuantityUpdateRequired();
		$dtsRequired = $coreSession->isDetailsUpdateRequired();

		if ($qtyRequired || $dtsRequired) {
			Mage::helper('eb2cinventory/quote')->rollbackAllocation($quote, null);
			if ($qtyRequired) {
				$this->_updateQuantity($quote);
			}
			if ($dtsRequired && Mage::helper('eb2cinventory')->hasRequiredShippingDetail($quote->getShippingAddress())) {
				$this->_updateDetails($quote);
			}
		}
		return $this;
	}
	/**
	 * Make an inventory quantity request and update the quote as needed.
	 * @param  Mage_Sales_Model_Quote $quote     Quote object to make the request for and updated
	 * @return self
	 */
	protected function _updateQuantity(Mage_Sales_Model_Quote $quote)
	{
		if ($this->_makeRequestAndUpdate(Mage::getModel('eb2cinventory/quantity'), $quote)) {
			Mage::getSingleton('eb2ccore/session')->updateQuoteInventory($quote)->resetQuantityUpdateRequired();
		}
		return $this;
	}
	/**
	 * Make an inventory details request and update the quote as needed.
	 * If the details request was successful (no unavailable items), also flag the
	 * inventory as being updated. If any items came back as unavailable, mark the session data as
	 * invalid as quantity will need to be run again at the next available opportunity.
	 * @param  Mage_Sales_Model_Quote $quote     Quote object the request is for
	 * @return self
	 */
	protected function _updateDetails(Mage_Sales_Model_Quote $quote)
	{
		if ($this->_makeRequestAndUpdate(Mage::getModel('eb2cinventory/details'), $quote)) {
			Mage::getSingleton('eb2ccore/session')->updateQuoteInventory($quote)->resetDetailsUpdateRequired();
		}
		return $this;
	}
	/**
	 * Use the given request model to make a request and update the quote accordingly.
	 * @param  EbayEnterprise_Eb2cInventory_Model_Request_Abstract $requestModel Request object used to
	 *                                                                       make the request
	 * @param  Mage_Sales_Model_Quote $quote Quote object the request is for
	 * @return string the response text
	 */
	protected function _makeRequestAndUpdate(
		EbayEnterprise_Eb2cInventory_Model_Request_Abstract $requestModel,
		Mage_Sales_Model_Quote $quote
	)
	{
		$response = $requestModel->makeRequestForQuote($quote);
		$requestModel->updateQuoteWithResponse($quote, $response);
		return $response;
	}
	/**
	 * Processing e2bc allocation, triggering eb2c_allocation_onepage_save_order_action_before event will run this method.
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 * @throws EbayEnterprise_Eb2cInventory_Model_Allocation_Exception if quote not fully allocated
	 */
	public function processAllocation(Varien_Event_Observer $observer)
	{
		/** @var Varien_Event $event */
		$event = $observer->getEvent();
		// get the quote from the event observer
		$quote = $event->getQuote();
		/** @var EbayEnterprise_Eb2cInventory_Model_Allocation $allocation */
		$allocation = Mage::getModel('eb2cinventory/allocation');
		// only allow allocation only when, there's no previous allocation or the previous allocation expired
		if ($allocation->requiresAllocation($quote)) {
			// flag for failure or success allocation
			$isAllocated = true;

			// generate request and send request to eb2c allocation
			$allocationResponseMessage = $allocation->allocateQuoteItems($quote);
			if ($allocationResponseMessage) {
				// parse allocation response
				$allocationData = $allocation->parseResponse($allocationResponseMessage);

				// got a valid response from eb2c, then go ahead and update the quote with the eb2c information
				$allocatedErr = $allocation->processAllocation($quote, $event->getOrder(), $allocationData);

				// Got an allocation failure
				if (!empty($allocatedErr)) {
					$isAllocated = false;
				}
			}

			if (!$isAllocated) {
				$message = Mage::helper('eb2cinventory')->__(self::ALLOCATION_ERROR_MESSAGE, implode(' ', $allocatedErr));
				throw Mage::exception('EbayEnterprise_Eb2cInventory_Model_Allocation', $message);
			}
		}
		return $this;
	}
	/**
	 * Rollback allocations for the quote/order when the order could not be created.
	 * @param  Varien_Event_Observer $observer Contains the quote and order
	 * @return self
	 */
	public function rollbackAllocation(Varien_Event_Observer $observer)
	{
		// Don't rollback if the session has been flagged to retain the allocation.
		// Pass true to clear the flag after getting it.
		if (!Mage::getSingleton('checkout/session')->getRetainAllocation(true)) {
			$event = $observer->getEvent();
			Mage::helper('eb2cinventory/quote')->rollbackAllocation($event->getQuote(), $event->getOrder());
		}
		return $this;
	}
	/**
	 * Listen to the 'ebayenterprise_feed_dom_loaded' event
	 * @see EbayEnterprise_Eb2cCore_Model_Feed_Abstract::processFile
	 * process a dom document
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function processDom(Varien_Event_Observer $observer)
	{
		Varien_Profiler::start(__METHOD__);
		$event = $observer->getEvent();
		$fileDetail = $event->getFileDetail();
		$feedConfig = $fileDetail['core_feed']->getFeedConfig();
		// only process the import if the event type is an inventory type
		if ($feedConfig['event_type'] === Mage::helper('eb2cinventory')->getConfigModel()->feedEventType) {
			Mage::log(sprintf('[%s] processing %s', __CLASS__, $fileDetail['local_file']), Zend_Log::DEBUG);
			$fileDetail['doc'] = $event->getDoc();
			Mage::getModel('eb2cinventory/feed_item_inventories')->process($fileDetail['doc']);
		}
		Varien_Profiler::stop(__METHOD__);
		return $this;
	}
}
