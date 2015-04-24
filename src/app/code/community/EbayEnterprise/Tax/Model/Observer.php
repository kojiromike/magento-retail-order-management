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

class EbayEnterprise_Tax_Model_Observer
{
	/** @var EbayEnterprise_Tax_Model_Collector */
	protected $_taxCollector;
	/** @var EbayEnterprise_Eb2cCore_Model_Session */
	protected $_coreSession;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_logContext;

	/**
	 * @param array
	 */
	public function __construct(array $args=[])
	{
		list(
			$this->_taxCollector,
			$this->_coreSession,
			$this->_logger,
			$this->_logContext
		) = $this->_checkTypes(
			$this->_nullCoalesce($args, 'tax_collector', Mage::getModel('ebayenterprise_tax/collector')),
			$this->_nullCoalesce($args, 'core_session', null),
			$this->_nullCoalesce($args, 'logger', Mage::helper('ebayenterprise_magelog')),
			$this->_nullCoalesce($args, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
		);
	}

	/**
	 * Enforce type checks on construct args array.
	 *
	 * @param EbayEnterprise_Tax_Model_Collector
	 * @param EbayEnterprise_Tax_Model_Session
	 * @param EbayEnterprise_MageLog_Helper_Data
	 * @param EbayEnterprise_MageLog_Helper_Context
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_Tax_Model_Collector $taxCollector,
		EbayEnterprise_Eb2cCore_Model_Session $coreSession=null,
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_MageLog_Helper_Context $logContext
	) {
		return [$taxCollector, $coreSession, $logger, $logContext];
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
	 * Get the session instance containing collected tax data for the quote.
	 * Populates the class property if not set when requested. The property
	 * will not be set during construction to minimize the risk of initializing
	 * the session instance before the user session has been started.
	 *
	 * @return EbayEnterprise_Eb2cCore_Model_Session
	 */
	protected function _getCoreSession()
	{
		if (!$this->_coreSession) {
			$this->_coreSession = Mage::getSingleton('eb2ccore/session');
		}
		return $this->_coreSession;
	}

	/**
	 * Collect new tax totals if necessary after collecting quote totals.
	 * Tax totals collected after all other quote totals so tax totals for the
	 * entire quote may be collected at one - all other totals for all other
	 * addresses must have already been collected.
	 *
	 * If new taxes are collected, all quote totals must be recollected.
	 *
	 * @param Varien_Event_Observer
	 * @return self
	 */
	public function handleSalesQuoteCollectTotalsAfter(Varien_Event_Observer $observer)
	{
		$coreSession = $this->_getCoreSession();
		if ($coreSession->isTaxUpdateRequired()) {
			/** @var Mage_Sales_Model_Quote */
			$quote = $observer->getEvent()->getQuote();
			try {
				$this->_taxCollector->collectTaxes($quote);
			} catch (EbayEnterprise_Tax_Exception_Collector_InvalidQuote_Exception $e) {
				// Exception for when a quote is not yet ready for making
				// a tax request. Not an entirely uncommon situation and
				// does not necessarily indicate anything is actually wrong
				// unless the quote is expected to be valid but isn't.
				$this->_logger->debug('Quote not valid for tax request.', $this->_logContext->getMetaData(__CLASS__));
				return $this;
			} catch (EbayEnterprise_Tax_Exception_Collector_Exception $e) {
				// Want TDF to be non-blocking so exceptions from making the
				// request should be caught. Still need to exit here when there
				// is an exception, however, to allow the TDF to be retried
				// (don't reset update required flag) and prevent totals from being
				// recollected (nothing to update and, more imporantly, would
				// continue to loop until PHP crashes or a TDF request succeeds).
				$this->_logger->warning('Tax request failed.', $this->_logContext->getMetaData(__CLASS__, [], $e));
				return $this;
			}
			// After retrieving new tax records, update the session with data
			// from the quote used to make the request and reset the tax
			// update required flag as another update should not be required
			// until some other change has been detected.
			$coreSession->updateWithQuote($quote)->resetTaxUpdateRequired();
			// Need to trigger a re-collection of quote totals now that taxes
			// for the quote have been retrieved. On the second pass, tax totals
			// just collected should be applied to the quote and any totals
			// dependent upon tax totals - like grand total - should update
			// to include the tax totals.
			$quote->collectTotals();
		}
		return $this;
	}

	/**
	 * set the tax header error flag on the order create request.
	 * @param  Varien_Event_Observer
	 * @return self
	 */
	public function handleOrderCreateBeforeAttachEvent(Varien_Event_Observer $observer)
	{
		$event = $observer->getEvent();
		Mage::getModel(
			'ebayenterprise_tax/order_create_order',
			['order_create_request' => $event->getPayload()]
		)->addTaxHeaderErrorFlag();
		return $this;
	}

	/**
	 * set gifting tax data on the shipgroup payload for the order create request
	 * @param  Varien_Event_Observer
	 * @return self
	 */
	public function handleOrderCreateShipGroupEvent(Varien_Event_Observer $observer)
	{
		$event = $observer->getEvent();
		Mage::getModel(
			'ebayenterprise_tax/order_create_shipgroup',
			[
				'address' => $event->getAddress(),
				'ship_group' => $event->getShipGroupPayload(),
			]
		)->addGiftTaxesToPayload();
		return $this;
	}

	/**
	 * set tax data on the orderitem payload for the order create request
	 * @param  Varien_Event_Observer
	 * @return self
	 */
	public function handleOrderCreateItemEvent(Varien_Event_Observer $observer)
	{
		$event = $observer->getEvent();
		$item = $event->getItem();
		$quoteItemId = $item->getQuoteItemId();
		Mage::getModel(
			'ebayenterprise_tax/order_create_orderitem',
			[
				'item' => $event->getItem(),
				'tax_records' => $this->_taxCollector->getTaxRecordsByItemId($quoteItemId),
				'duty' => $this->_taxCollector->getTaxDutyByItemId($quoteItemId),
				'fees' => $this->_taxCollector->getTaxFeesByItemId($quoteItemId),
				'order_item_payload' => $event->getItemPayload(),
			]
		)->addTaxesToPayload();
		return $this;
	}
}
