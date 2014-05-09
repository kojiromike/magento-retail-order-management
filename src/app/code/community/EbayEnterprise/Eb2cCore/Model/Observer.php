<?php
class EbayEnterprise_Eb2cCore_Model_Observer
{
	/**
	 * Update the eb2ccore session with the new quote.
	 * @param  Varien_Event_Observer $observer Event observer object containing a quote object
	 * @return self $this object
	 */
	public function checkQuoteForChanges($observer)
	{
		Mage::getSingleton('eb2ccore/session')->updateWithQuote($observer->getEvent()->getQuote());
		return $this;
	}
	/**
	 * Perform all processing necessary for the order to be placed with the
	 * Exchange Platform - allocate inventory, redeem SVC. If any of the observers
	 * need to indicate that an order should not be created, the observer method
	 * should throw an exception.
	 * Observers the 'sales_order_place_before' event.
	 * @see Mage_Sales_Model_Order::place
	 * @param  Varien_Event_Observer $observer Contains the order being placed which will have a reference to the quote the order was created for
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
		Mage::dispatchEvent('eb2c_redeem_giftcard', array('quote' => $quote, 'order' => $order));
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
		// Add a flag to the session indicating the failure to be used in
		// controllers, events, whatever, to detect the failure.
		Mage::getSingleton('checkout/session')->setExchangePlatformOrderCreateFailed(true);
		return $this;
	}
	/**
	 * Replace the response body with a redirect directive for the OPC JavaScript
	 * when the Exchange Platform order create fails.
	 * Observes the 'controller_action_postdispatch_checkout_onepage_saveOrder' event
	 * @see Mage_Core_Controller_Varien_Action::postDispatch
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function addOnepageCheckoutRedirectResponse(Varien_Event_Observer $observer)
	{
		$session = Mage::getSingleton('checkout/session');
		// get the flag from the session - pass true to the getter to also clear the flag
		if ($session->getExchangePlatformOrderCreateFailed(true)) {
			$helper = Mage::helper('core');
			$response = $observer->getEvent()->getControllerAction()->getResponse();
			$result = $helper->jsonDecode($response->getBody('default'));
			// Transfer any error messages in the JSON response to the session so they
			// are still displayed to the user.
			if (isset($result['error_messages'])) {
				// As far as I can tell, "error_messages" will always be a single
				// message here, despite the use of the plural "messages."
				$session->addError($result['error_messages']);
			}
			$response->setBody(
				Mage::helper('core')->jsonEncode(
					array('redirect' => Mage::helper('checkout/cart')->getCartUrl())
				)
			);
		}
		return $this;
	}
}
