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

class EbayEnterprise_Eb2cCore_Model_Observer
{
	/** @var  EbayEnterprise_Eb2cCore_Model_Session */
	protected $_session;

	/**
	 * @return EbayEnterprise_Eb2cCore_Model_Session|Mage_Core_Model_Abstract
	 */
	protected function _getCoreSession()
	{
		if (!$this->_session) {
			$this->_session = Mage::getSingleton('eb2ccore/session');
		}
		return $this->_session;
	}
	/**
	 * Update the eb2ccore session with the new quote.
	 * @param  Varien_Event_Observer $observer Event observer object containing a quote object
	 * @return self $this object
	 */
	public function checkQuoteForChanges($observer)
	{
		$this->_getCoreSession()->updateWithQuote($observer->getEvent()->getQuote());
		return $this;
	}

	/**
	 * Perform all processing necessary for the order to be placed with the
	 * Exchange Platform - allocate inventory, redeem SVC. If any of the observers
	 * need to indicate that an order should not be created, the observer method
	 * should throw an exception.
	 * Observers the 'sales_order_place_before' event.
	 *
	 * @see Mage_Sales_Model_Order::place
	 * @param Varien_Event_Observer $observer Contains the order being placed which will have a reference to the quote the order was created for
	 * @throws EbayEnterprise_Eb2cInventory_Model_Allocation_Exception
	 * @throws Exception
	 * @return self
	 */
	public function processExchangePlatformOrder(Varien_Event_Observer $observer)
	{
		$order = $observer->getEvent()->getOrder();
		$quote = $order->getQuote();
		try {
			Mage::dispatchEvent('eb2c_allocate_inventory', array('quote' => $quote, 'order' => $order));
		} catch (EbayEnterprise_Eb2cInventory_Model_Allocation_Exception $e) {
			// When just an allocation fails, keep whatever could be allocated
			// for if/when the order is resubmitted.
			Mage::getSingleton('checkout/session')->setRetainAllocation(true);
			throw $e;
		}
		Mage::dispatchEvent('ebayenterprise_giftcard_redeem', array('quote' => $quote, 'order' => $order));
		return $this;
	}
	/**
	 * Roll back any Exchange Platform actions made for the order - rollback
	 * allocation, void SVC redemptions, void payment auths.
	 * Observes the 'sales_model_service_quote_submit_failure' event.
	 * @see Mage_Sales_Model_Service_Quote::submitOrder
	 * @param  Varien_Event_Observer $observer Contains the failed order as well as the quote the order was created for
	 * @return self
	 */
	public function rollbackExchangePlatformOrder(Varien_Event_Observer $observer)
	{
		Mage::dispatchEvent('eb2c_order_creation_failure', array(
			'quote' => $observer->getEvent()->getQuote(),
			'order' => $observer->getEvent()->getOrder()
		));
		return $this;
	}
	/**
	 * Listen to the 'checkout_onepage_controller_success_action' event
	 * Clear the session
	 *
	 * @param Varien_Event_Observer $observer
	 * @return self
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function clearSession(Varien_Event_Observer $observer)
	{
		$this->_getCoreSession()->clear();
		return $this;
	}
}
