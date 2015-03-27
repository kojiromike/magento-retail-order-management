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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IShipGroup;
use \eBayEnterprise\RetailOrderManagement\Payload\Order\ITax;

/**
 * Adds Tax information to an OrderItem payload and its sub payloads.
 */
class EbayEnterprise_Eb2cTax_Model_Order_Create_Shipgroup
{
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_Eb2cTax_Overrides_Model_Calculation */
	protected $_calculator;
	/** @var EbayEnterprise_Eb2cTax_Helper_Sdk */
	protected $_sdkHelper;
	/** @var array */
	protected $_taxQuotes = [];
	/** @var boolean */
	protected $_hasErrors = false;

	public function __construct(array $args=array())
	{
		list($this->_logger, $this->_sdkHelper, $this->_calculator) =
			$this->_checkTypes(
				$this->_nullCoalesce('logger', $args, Mage::helper('ebayenterprise_magelog')),
				$this->_nullCoalesce('sdk_helper', $args, Mage::helper('eb2ctax/sdk')),
				$this->_nullCoalesce('calculator', $args, Mage::getModel('tax/calculation'))
			);
	}

	protected function _checkTypes(
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_Eb2cTax_Helper_Sdk $sdkHelper,
		EbayEnterprise_Eb2cTax_Overrides_Model_Calculation $calculator
	) {
		return [$logger, $sdkHelper, $calculator];
	}

	protected function _nullCoalesce($key, array $ar, $default)
	{
		return isset($ar[$key]) ? $ar[$key] : $default;
	}

	/**
	 * add tax data to the given IShipGroup gifting price group.
	 * PriceGroups are created as necessary.
	 * An exception may be thrown if an expected discount payload is not found.
	 * @param IShipGroup                     $shipGroup
	 * @param Mage_Sales_Model_Order_Address $address
	 */
	public function addGiftTaxesToPayload(
		IShipGroup $shipGroup,
		Mage_Sales_Model_Order_Address $address
	) {
		$this->_loadTaxes($address);
		// terminate early if there are no taxes to work with or there was
		// an error on the remote side.
		if (!$this->_hasErrors) {
			foreach ($this->_taxQuotes as $taxQuote) {
				$iterable = $this->_fetchIterablePayload($taxQuote, $shipGroup);
				$taxPayload = $this->_sdkHelper->getAsOrderTaxPayload($taxQuote, $iterable->getEmptyTax());
				$iterable[$taxPayload] = $taxPayload;
			}
		}
		return $this;
	}

	/**
	 * get the iterable payload appropriate for inserting $taxQuote's information.
	 * @param  IShipGroup                                  $shipgroup
	 * @return ITaxIterable
	 */
	protected function _fetchIterablePayload(IShipGroup $shipgroup)
	{
		$pg = $shipgroup->getGiftPricing();
		if (is_null($pg)) {
			$pg = $shipgroup->getEmptyGiftingPriceGroup();
			$shipgroup->setGiftPricing($pg);
		}
		return $pg->getTaxes();
	}

	/**
	 * get a tax payload for the given tax quote
	 * @param  EbayEnterprise_Eb2cTax_Model_Response_Quote $taxQuote
	 * @param  ITax                                        $taxPayload
	 * @return EbayEnterprise_Eb2cTax_Model_Response_Quote
	 */
	protected function _getTaxQuoteAsPayload(EbayEnterprise_Eb2cTax_Model_Response_Quote $taxQuote, ITax $taxPayload)
	{
		$taxPayload
			->setSitus($taxQuote->getSitus())
			->setEffectiveRate($this->_calculator->round($taxQuote->getEffectiveRate()))
			->setCalculatedTax($this->_calculator->round($taxQuote->getCalculatedTax()))
			->setType($taxQuote->getTaxType())
			->setTaxability($taxQuote->getTaxability())
			->setJurisdiction($taxQuote->getJurisdiction())
			->setJurisdictionLevel($taxQuote->getJurisdictionLevel())
			->setJurisdictionId($taxQuote->getJurisdictionId())
			->setImposition($taxQuote->getImposition())
			->setImpositionType($taxQuote->getImpositionType())
			->setTaxableAmount($this->_calculator->round($taxQuote->getTaxableAmount()))
			->setSellerRegistrationId($taxQuote->getSellerRegistrationId());
		return $taxPayload;
	}

	/**
	 * load the taxquotes for the ship group
	 * @param  Mage_Sales_Model_Order_Address $address
	 */
	protected function _loadTaxes(Mage_Sales_Model_Order_Address $address)
	{
		$responseItems = $this->_calculator->getTaxResponse()->getResponseItems();
		$responseItems = isset($responseItems[$address->getQuoteAddressId()]) ?
			$responseItems[$address->getQuoteAddressId()] : [];
		foreach ($responseItems as $responseItem) {
			$this->_getRelevantTaxes($responseItem);
		}
	}

	/**
	 * scan through the an item's taxes and get only those that
	 * go to the shipgroup.
	 * if errors are detected, set a flag.
	 * @param  EbayEnterprise_Eb2cTax_Model_Response_Orderitem $responseItem
	 */
	protected function _getRelevantTaxes(EbayEnterprise_Eb2cTax_Model_Response_Orderitem $responseItem)
	{
		foreach ($responseItem->getTaxQuotes() as $taxQuote) {
			if ($taxQuote->getCode() === 'CalculationError') {
				$this->_hasErrors = true;
				$this->_taxQuotes = [];
				return;
			}
			if ($taxQuote->getType() === EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPGROUP_GIFTING) {
				$this->_taxQuotes[] = $taxQuote;
			}
		}
	}
}
