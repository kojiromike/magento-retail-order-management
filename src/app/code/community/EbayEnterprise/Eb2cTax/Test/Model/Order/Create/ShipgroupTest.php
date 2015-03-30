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

class EbayEnterprise_Eb2cTax_Test_Model_Order_Create_ShipgroupTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/** @var Mage_Sales_Model_Order_Address */
	protected $_address;
	/** @var \eBayEnterprise\RetailOrderManagement\Api\HttpApi */
	protected $_sdk;
	/** @var Mage_Sales_Model_Order */
	protected $_order;
	/** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IShipGroup */
	protected $_shipgroupPayload;
	/** @var Varien_Object */
	protected $_taxUtilStub;

	public function setUp()
	{
		parent::setUp();
		// replace the session for the logger context
		$this->_replaceSession('core/session');
		$this->_taxResponse = $this->getModelMock('eb2ctax/response', ['_construct']);
		$this->_calculator = Mage::getModel('tax/calculation');
		$this->_calculator->setTaxResponse($this->_taxResponse);
		$this->_sdk = Mage::helper('eb2ccore')->getSdkApi('orders', 'create');
		$this->_shipgroupPayload = $this->_sdk->getRequestBody()->getShipGroups()->getEmptyShipGroup();
		$this->_address = Mage::getModel('sales/order_address', ['quote_address_id' => 1]);
		$this->_order = Mage::getModel('sales/order');
		$this->_taxUtilStub = new Varien_Object();
	}

	/**
	 * verify shipgroup gifting taxes from all items attached to the given address
	 * are gathered.
	 */
	public function testLoadingTaxData()
	{
		$taxQuotes = [
			Mage::getModel('eb2ctax/response_quote', ['type' => EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPGROUP_GIFTING]),
			Mage::getModel('eb2ctax/response_quote', ['type' => EbayEnterprise_Eb2cTax_Model_Response_Quote::GIFTING])
		];
		$orderItem1 = Mage::getModel('eb2ctax/response_orderitem');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderItem1, '_taxQuotes', $taxQuotes);

		$taxQuotes = [Mage::getModel('eb2ctax/response_quote', ['type' => EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPGROUP_GIFTING])];
		$orderItem2 = Mage::getModel('eb2ctax/response_orderitem');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderItem2, '_taxQuotes', $taxQuotes);

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_taxResponse, '_responseItems', [1 => ['sku1' => $orderItem1, 'sku2' => $orderItem2]]);

		$handler = Mage::getModel('eb2ctax/order_create_shipgroup', ['calculator' => $this->_calculator]);
		EcomDev_Utils_Reflection::invokeRestrictedMethod($handler, '_loadTaxes', [$this->_address]);
		$taxQuotes = EcomDev_Utils_Reflection::getRestrictedPropertyValue($handler, '_taxQuotes');
		$this->assertCount(2, $taxQuotes);
	}

	/**
	 * no taxes are loaded if there are errors.
	 * a flag will be set on the calculator.
	 */
	public function testLoadingTaxDataWithError()
	{
		$taxQuotes = [
			Mage::getModel('eb2ctax/response_quote', ['code' => 'CalculationError']),
		];
		$orderItem1 = Mage::getModel('eb2ctax/response_orderitem');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderItem1, '_taxQuotes', $taxQuotes);
		// add the response item to the response
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_taxResponse, '_responseItems', [1 => ['sku1' => $orderItem1]]);

		$handler = Mage::getModel('eb2ctax/order_create_shipgroup', ['calculator' => $this->_calculator]);
		EcomDev_Utils_Reflection::invokeRestrictedMethod($handler, '_loadTaxes', [$this->_address]);
		$taxQuotes = EcomDev_Utils_Reflection::getRestrictedPropertyValue($handler, '_taxQuotes');
		$hasError = EcomDev_Utils_Reflection::getRestrictedPropertyValue($handler, '_hasErrors');
		$this->assertCount(0, $taxQuotes, 'tax_quotes were found');
		$this->assertNotNull($hasError, '_eb2ctax_hasErrors not set');
		$this->assertTrue($hasError, '_eb2ctax_hasErrors is incorrect');
	}

	/**
	 * shipgroup tax payloads are added to the shipgroup
	 */
	public function testAddGiftingTaxesToPayload()
	{
		$taxQuotes = [Mage::getModel('eb2ctax/response_quote', ['type' => EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPGROUP_GIFTING])];
		$orderItem1 = Mage::getModel('eb2ctax/response_orderitem');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderItem1, '_taxQuotes', $taxQuotes);
		// add the response item to the response
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_taxResponse, '_responseItems', [1 => ['sku1' => $orderItem1]]);

		$handler = Mage::getModel('eb2ctax/order_create_shipgroup', ['calculator' => $this->_calculator]);
		$handler->addGiftTaxesToPayload($this->_shipgroupPayload, $this->_address, $this->_order);
		$pg = $this->_shipgroupPayload->getGiftPricing();
		$this->assertNotNull($pg);
		$this->assertCount(1, $pg->getTaxes());
	}

	/**
	 * provide non ship group tax quote types
	 * @return array
	 */
	public function provideNonShipGroupTaxes()
	{
		return [
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE],
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE_PROMOTION],
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING],
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING_PROMOTION],
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::DUTY],
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::DUTY_PROMOTION],
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::GIFTING],
		];
	}

	/**
	 * for non shipgroup types, no tax payloads will be added
	 * @param int $taxType
	 * @dataProvider provideNonShipGroupTaxes
	 */
	public function testAddGiftingTaxesToPayloadNonShipGroupTaxes($taxType)
	{
		$taxQuotes = [Mage::getModel('eb2ctax/response_quote', ['type' => $taxType])];
		$orderItem1 = Mage::getModel('eb2ctax/response_orderitem');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderItem1, '_taxQuotes', $taxQuotes);
		// add the response item to the response
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_taxResponse, '_responseItems', [1 => ['sku1' => $orderItem1]]);

		$handler = Mage::getModel('eb2ctax/order_create_shipgroup', ['calculator' => $this->_calculator]);
		$handler->addGiftTaxesToPayload($this->_shipgroupPayload, $this->_address, $this->_order);
		$pg = $this->_shipgroupPayload->getGiftPricing();
		$this->assertNull($pg);
	}
}
