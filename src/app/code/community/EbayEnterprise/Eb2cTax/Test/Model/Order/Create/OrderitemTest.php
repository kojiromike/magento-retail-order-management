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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem;

class EbayEnterprise_Eb2cTax_Test_Model_Order_Create_OrderitemTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	const ORDERITEM_PAYLOAD_CLASS =
		'\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem';

	protected $_helperStub;
	protected $_calculator;
	protected $_taxResponse;
	protected $_item;
	protected $_address;
	protected $_sdk;
	protected $_itemPayload;

	public function setUp()
	{
		parent::setUp();
		// replace the session for the logger context
		$this->_replaceSession('core/session');
		$this->_taxResponse = $this->getModelMock('eb2ctax/response', ['_construct']);
		$this->_calculator = Mage::getModel('tax/calculation');
		$this->_calculator->setTaxResponse($this->_taxResponse);
		$this->_sdk = Mage::helper('eb2ccore')->getSdkApi('orders', 'create');
		$config = $this->buildCoreConfigRegistry(['shippingTaxClass' => 'shippingtaxclass']);
		$this->_helperStub = $this->getHelperMock('eb2ctax/data', ['getConfigModel']);
		$this->_helperStub->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($config));
		$this->_itemPayload = $this->_sdk->getRequestBody()->getOrderItems()->getEmptyOrderItem();
		$product = Mage::getModel('catalog/product', ['tax_code' => 'merchtaxclass']);
		$this->_item = Mage::getModel('sales/order_item', ['sku' => 'sku', 'product' => $product]);
		$this->_address = Mage::getModel('sales/order_address', ['quote_address_id' => 1]);
	}

	/**
	 * IOrderItem::TAX_AND_DUTY_DISPLAY_NONE is returned when there was an error
	 */
	public function testGetTaxAndDutyDisplayWhenError()
	{
		$responseItem = Mage::getModel('eb2ctax/response_orderitem');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue(
			$responseItem,
			'_taxQuotes',
			[Mage::getModel('eb2ctax/response_quote', ['calculation_error' => true])]
		);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_taxResponse, '_responseItems', [1 => ['sku' => $responseItem]]);

		$handler = Mage::getModel('eb2ctax/order_create_orderitem', ['helper' => $this->_helperStub]);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($handler, '_calculator', $this->_calculator);
		$handler->addTaxesToPayload($this->_itemPayload, $this->_item, $this->_address);
		$this->assertSame(IOrderItem::TAX_AND_DUTY_DISPLAY_NONE, $this->_itemPayload->getTaxAndDutyDisplayType());
	}

	/**
	 * IOrderItem::TAX_AND_DUTY_DISPLAY_NONE is returned when there was an error
	 */
	public function testGetTaxAndDutyDisplayWhenNoTaxes()
	{
		$responseItem = Mage::getModel('eb2ctax/response_orderitem');
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($responseItem, [
			'_taxQuotes' => []
		]);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_taxResponse, '_responseItems', [1 => ['sku' => $responseItem]]);
		$handler = Mage::getModel('eb2ctax/order_create_orderitem', ['helper' => $this->_helperStub]);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($handler, '_calculator', $this->_calculator);
		$handler->addTaxesToPayload($this->_itemPayload, $this->_item, $this->_address);
		$this->assertSame(IOrderItem::TAX_AND_DUTY_DISPLAY_NONE, $this->_itemPayload->getTaxAndDutyDisplayType());
	}

	/**
	 * IOrderItem::TAX_AND_DUTY_DISPLAY_SINGLE_AMOUNT is returned when there are taxes
	 * to add and no errors
	 */
	public function testGetTaxAndDutyDisplay()
	{
		$responseItem = Mage::getModel('eb2ctax/response_orderitem');
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($responseItem, [
			'_taxQuotes' => [Mage::getModel('eb2ctax/response_quote', ['type' => EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE])]
		]);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_taxResponse, '_responseItems', [1 => ['sku' => $responseItem]]);

		$handler = Mage::getModel('eb2ctax/order_create_orderitem', ['helper' => $this->_helperStub]);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($handler, '_calculator', $this->_calculator);
		$handler->addTaxesToPayload($this->_itemPayload, $this->_item, $this->_address);
		$this->assertSame(IOrderItem::TAX_AND_DUTY_DISPLAY_SINGLE_AMOUNT, $this->_itemPayload->getTaxAndDutyDisplayType());
	}

	public function providePriceGroupGetters()
	{
		return [
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE, 'getMerchandisePricing', ['getDutyPricing', 'getShippingPricing', 'getGiftPricing']],
			// the merchandise price group is required and should never be null
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::DUTY, 'getDutyPricing', ['getShippingPricing', 'getGiftPricing']],
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING, 'getShippingPricing', ['getDutyPricing', 'getGiftPricing']],
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::GIFTING, 'getGiftPricing', ['getDutyPricing', 'getShippingPricing']],
		];
	}

	/**
	 * verify the tax payload is added to the correct price group
	 * @dataProvider providePriceGroupGetters
	 */
	public function testAddTaxesToPayload($taxType, $nonNullPriceGroupGetter, array $nullPriceGroupGetters)
	{
		$responseItem = Mage::getModel('eb2ctax/response_orderitem');
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($responseItem, [
			'_taxQuotes' => [Mage::getModel('eb2ctax/response_quote', ['type' => $taxType])]
		]);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_taxResponse, '_responseItems', [1 => ['sku' => $responseItem]]);

		$handler = Mage::getModel('eb2ctax/order_create_orderitem', ['helper' => $this->_helperStub]);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($handler, '_calculator', $this->_calculator);
		$handler->addTaxesToPayload($this->_itemPayload, $this->_item, $this->_address);
		$this->assertNotNull($this->_itemPayload->getMerchandisePricing());
		$pg = $this->_itemPayload->$nonNullPriceGroupGetter();
		$this->assertCount(1, $pg->getTaxes());
		foreach ($nullPriceGroupGetters as $method) {
			$this->assertNull($this->_itemPayload->$method());
		}
	}

	public function providePriceGroupGettersWithTaxClass()
	{
		return [
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE, 'getMerchandisePricing', 'merchtaxclass'],
			// the merchandise price group is required and should never be null
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::DUTY, 'getDutyPricing', null],
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING, 'getShippingPricing', 'shippingtaxclass'],
			[EbayEnterprise_Eb2cTax_Model_Response_Quote::GIFTING, 'getGiftPricing', null],
		];
	}

	/**
	 * verify the tax class is set properly for each price group
	 * @dataProvider providePriceGroupGettersWithTaxClass
	 */
	public function testAddTaxesToPayloadTaxClass($taxType, $priceGroupGetter, $taxClass)
	{
		$responseItem = Mage::getModel('eb2ctax/response_orderitem');
		EcomDev_Utils_Reflection::setRestrictedPropertyValues($responseItem, [
			'_taxQuotes' => [Mage::getModel('eb2ctax/response_quote', ['type' => $taxType])]
		]);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->_taxResponse, '_responseItems', [1 => ['sku' => $responseItem]]);

		$handler = Mage::getModel('eb2ctax/order_create_orderitem', ['helper' => $this->_helperStub]);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($handler, '_calculator', $this->_calculator);
		$handler->addTaxesToPayload($this->_itemPayload, $this->_item, $this->_address);
		$pg = $this->_itemPayload->$priceGroupGetter();
		$this->assertSame($taxClass, $pg->getTaxClass());
	}
}
