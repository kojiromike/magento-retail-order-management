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

class EbayEnterprise_Eb2cTax_Overrides_Model_Observer extends Mage_Tax_Model_Observer
{
	protected $_tax;

	/**
	 * Put quote address tax information into order
	 *
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function salesEventConvertQuoteAddressToOrder(Varien_Event_Observer $observer)
	{
		$address = $observer->getEvent()->getAddress();
		$order = $observer->getEvent()->getOrder();

		$taxes = $address->getAppliedTaxes();
		if (is_array($taxes)) {
			if (is_array($order->getAppliedTaxes())) {
				$taxes = array_merge($order->getAppliedTaxes(), $taxes);
			}
			$order->setAppliedTaxes($taxes);
			$order->setConvertingFromQuote(true);
		}
		return $this;
	}

	/**
	 * Save order tax information
	 *
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function salesEventOrderAfterSave(Varien_Event_Observer $observer)
	{
		parent::salesEventOrderAfterSave($observer);
		$order = $observer->getEvent()->getOrder();
		if (!$order->hasQuote()) {
			return;
		}
		// save all of the response quote and response quote discount objects
		$response = Mage::helper('tax')->getCalculator()->getTaxResponse();
		if ($response) {
			foreach ($order->getQuote()->getAllAddresses() as $address) {
				foreach ($address->getAllVisibleItems() as $item) {
					$this->_saveResponseQuote($item, $address, $response->getResponseForItem($item, $address));
				}
			}
			$response->storeResponseData();
		}
		return $this;
	}
	/**
	 * saving reponse data into response_quote table
	 * @param Mage_Sales_Model_Quote_Item_Abstract $item
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @param EbayEnterprise_Eb2cTax_Model_Response_Orderitem $responseItem
	 * @return self
	 */
	protected function _saveResponseQuote(
		Mage_Sales_Model_Quote_Item_Abstract $item,
		Mage_Sales_Model_Quote_Address $address,
		EbayEnterprise_Eb2cTax_Model_Response_Orderitem $responseItem=null
	)
	{
		if ($responseItem) {
			foreach ($responseItem->getTaxQuotes() as $taxQuote) {
				if ($taxQuote->getId()) {
					continue;
				}
				$taxQuote->setQuoteItemId($item->getId())
					->setQuoteAddressId($address->getId())
					->save();
			}
			foreach ($responseItem->getTaxQuoteDiscounts() as $taxDiscount) {
				if ($taxDiscount->getId()) {
					continue;
				}
				$taxDiscount->setQuoteItemId($item->getId())
					->setQuoteAddressId($address->getId())
					->save();
			}
		}
		return $this;
	}
	/**
	 * Clean up all of the tax flags at the end of collectTotals.
	 * @return self
	 */
	public function cleanupTaxRequestFlags()
	{
		Mage::helper('eb2ctax')->cleanupSessionFlags();
		return $this;
	}
	/**
	 * Send a tax request for the address and update the calculator with the
	 * response and indicate taxes should be calculated using the response.
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function taxEventSubtotalCollectBefore(Varien_Event_Observer $observer)
	{
		$address = $observer->getEvent()->getAddress();
		if (Mage::helper('eb2ctax')->isRequestForAddressRequired($address)) {
			$this->_fetchTaxUpdate($address);
		}
		return $this;
	}
	/**
	 * attempt to fetch udpated tax information for an address.
	 * @param  Mage_Sales_Model_Quote_Address $address
	 * @return self
	 */
	protected function _fetchTaxUpdate(Mage_Sales_Model_Quote_Address $address=null)
	{
		$request = Mage::helper('tax')->getCalculator()
			->getTaxRequest($address);

		if ($request->isValid()) {
			Mage::log(
				'[' . __CLASS__ . '] Sending TaxDutyQuote request for quote address ' . $address->getId(),
				Zend_Log::DEBUG
			);
			$this->_doRequest($request);
		} else {
			Mage::log(
				'[' . __CLASS__ . '] Unable to send TaxDutyQuote request: The request was empty or invalid',
				Zend_Log::DEBUG
			);
			Mage::helper('eb2ctax')->failTaxRequest();
		}
		return $this;
	}
	/**
	 * send the request and process the response.
	 * @param  EbayEnterprise_Eb2cTax_Model_Request $request
	 * @return self
	 */
	protected function _doRequest(EbayEnterprise_Eb2cTax_Model_Request $request)
	{
		try {
			$response = Mage::helper('eb2ctax')->sendRequest($request);
			$this->_handleResponse($response);
		} catch (Mage_Core_Exception $e) {
			Mage::log(sprintf(
				'[%s] Exception encountered making the TaxDutyQuote request: %s',
				__CLASS__,
				$e->getMessage()),
				Zend_Log::WARN
			);
			Mage::helper('eb2ctax')->failTaxRequest();
		}
		return $this;
	}
	/**
	 * check the response and trigger the next steps if necessary.
	 * @param  EbayEnterprise_Eb2cTax_Model_Response $response
	 * @return self
	 */
	protected function _handleResponse(EbayEnterprise_Eb2cTax_Model_Response $response)
	{
		$calc = Mage::helper('tax')->getCalculator();
		if ($response->isValid()) {
			$calc->setTaxResponse($response);
		} else {
			Mage::log(
				'[' . __CLASS__ . '] Unsuccessful TaxDutyQuote request: valid request received an invalid response',
				Zend_Log::WARN
			);
			Mage::helper('eb2ctax')->failTaxRequest();
		}
		return $this;
	}
}
