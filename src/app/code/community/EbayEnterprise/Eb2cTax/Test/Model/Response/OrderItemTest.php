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

/**
 * tests the tax response orderItem class.
 */
class EbayEnterprise_Eb2cTax_Test_Model_Response_OrderItemTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Testing _validate method with the following expectations
	 * Expectation 1: when the method EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_validate get invoke by this test
	 *                the class property EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_isValid which set to true in the
	 *                instantiation of the EbayEnterprise_Eb2cTax_Model_Response_Orderitem class which is confirm by the first assertion
	 * Expectation 2: by uns the sku and lineNumber from the EbayEnterprise_Eb2cTax_Model_Response_Orderitem object we are then guaranteed
	 *                that when the _validate method get invoked the property _isValid will be set to false, which is confirm by the
	 *                last assert false statement in this test
	 * Expectation 3: the method EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_validate will return null once invoke
	 */
	public function testValidate()
	{
		$orderItem = Mage::getModel('eb2ctax/response_orderitem');
		$this->assertTrue(EcomDev_Utils_Reflection::getRestrictedPropertyValue($orderItem, '_isValid'));
		$orderItem->unsSku()->unsLineNumber();
		$this->assertNull(EcomDev_Utils_Reflection::invokeRestrictedMethod($orderItem, '_validate'));
		$this->assertFalse(EcomDev_Utils_Reflection::getRestrictedPropertyValue($orderItem, '_isValid'));
	}

	/**
	 * Get item node
	 *
	 * @param DOMDocument $doc
	 * @param DOMXPath $xpath
	 * @throws Exception
	 * @return DomElement
	 */
	protected function _getItemNode(DOMDocument $doc, DOMXPath $xpath)
	{
		$shipGroups = $xpath->query('a:Shipping/a:ShipGroups/a:ShipGroup', $doc->documentElement);
		foreach ($shipGroups as $shipGroup) {
			$items = $xpath->query('./a:Items/a:OrderItem', $shipGroup);
			foreach ($items as $item) {
				return $item;
			}
		}
		throw new Exception('Item node not found.');
	}

	/**
	 * Testing _extractData method with the following expectations
	 * Expectation 1: this test is designed to show how EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_extractData
	 *                extract data from the TaxDutyQuoteResponse. Up on the instanciation of EbayEnterprise_Eb2cTax_Model_Response_Orderitem class object
	 *                an array of key (node) can be passed magically to initializ the property _xpath in the constructor method of this, so because
	 *                this test disabled the constructor when mocking this class, the _xpath property need to be set.
	 * Expectation 2: the property EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_isValid is set in this test to a know value because we mock the
	 *                EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_validate method will set the property to false if the magic method (getSku) is nll or empty string
	 * Expectation 3: the magic method EbayEnterprise_Eb2cTax_Model_Response_Orderitem::addData which is inherited from the extended class Varien_Object is mocked to
	 *                show the extracted data from the response DomDocument element is then pass as an array of key values and the method return it self
	 * Expectation 4: because the property class _isValid is set to know state of true, we know the mocked methods _processTaxData, and _processDiscountTaxData
	 *                will run once and return null
	 * Expectation 5: the EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_extractByType is mocked to be called exactly 2 time with parameter value map that show
	 *                exactly what will be return when the expected parameters are pass to this mocked method
	 * @mock EbayEnterprise_Eb2cTax_Model_Response_Orderitem::getNode - this is a magic Varien_Object method
	 * @mock EbayEnterprise_Eb2cTax_Model_Response_Orderitem::addData - this is a magic Varien_Object method
	 * @mock EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_extractByType
	 * @mock EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_validate
	 * @mock EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_processTaxData
	 * @mock EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_processDiscountTaxData
	 * @param string $response the xml string content to be loaded into the DOMDocument object
	 * @dataProvider dataProvider
	 */
	public function testExtractData($response)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($response);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('a', $doc->documentElement->namespaceURI);

		$itemNode = $this->_getItemNode($doc, $xpath);

		$orderitemModelMock = $this->getModelMockBuilder('eb2ctax/response_orderitem')
			->disableOriginalConstructor()
			->setMethods(array('getNode', 'addData', '_validate', '_processTaxData', '_extractByType'))
			->getMock();
		$orderitemModelMock->expects($this->once())
			->method('getNode')
			->will($this->returnValue($itemNode));
		$orderitemModelMock->expects($this->once())
			->method('addData')
			->with($this->equalTo(array(
				'sku' => 'gc_virtual1',
				'line_number' => '1',
				'item_desc' => 'Test Item 1',
				'hts_code' => 'duty code',
				'merchandise_amount' => 200.00,
				'unit_price' => 100.00,
				'shipping_amount' => 10.00,
				'duty_amount' => 2.00,
				'error_duty'        => '',
				'error_merchandise' => '',
				'error_shipping'    => '',
			)))
			->will($this->returnSelf());
		$orderitemModelMock->expects($this->exactly(2))
			->method('_extractByType')
			->will($this->returnValueMap(array(
				array(
					$itemNode,
					$xpath,
					array(
						'error_duty'        => 'string(a:Pricing/a:Duty/a:CalculationError)',
						'error_merchandise' => 'string(a:Pricing/a:Merchandise/a:CalculationError)',
						'error_shipping'    => 'string(a:Pricing/a:Shipping/a:CalculationError)',
						'hts_code'          => 'string(./a:HTSCode)',
						'item_desc'         => 'string(./a:ItemDesc)',
						'line_number'       => 'string(./@lineNumber)',
						'sku'               => 'string(./a:ItemId)',
					),
					'string',
					array(

						'error_duty'        => '',
						'error_merchandise' => '',
						'error_shipping'    => '',
						'hts_code'          => 'duty code',
						'item_desc'         => 'Test Item 1',
						'line_number'       => '1',
						'sku'               => 'gc_virtual1',
					)
				),
				array(
					$itemNode,
					$xpath,
					array(
						'merchandise_amount' => 'number(a:Pricing/a:Merchandise/a:Amount)',
						'unit_price' => 'number(a:Pricing/a:Merchandise/a:UnitPrice)',
						'shipping_amount' => 'number(a:Pricing/a:Shipping/a:Amount)',
						'duty_amount' => 'number(a:Pricing/a:Duty/a:Amount)'
					),
					'float',
					array(
						'merchandise_amount' => 200.00,
						'unit_price' => 100.00,
						'shipping_amount' => 10.00,
						'duty_amount' => 2.00
					)
				)
			)));
		$orderitemModelMock->expects($this->once())
			->method('_validate')
			->will($this->returnValue(true));
		$orderitemModelMock->expects($this->exactly(2))
			->method('_processTaxData')
			->will($this->returnValueMap(array(
				array($xpath, $itemNode, false, array()),
				array($xpath, $itemNode, true, array())
			)));
		// setting class property _xpath to a known state
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderitemModelMock, '_xpath', $xpath);

		// setting class property _isValid to a known state
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderitemModelMock, '_isValid', true);

		$this->assertNull(EcomDev_Utils_Reflection::invokeRestrictedMethod($orderitemModelMock, '_extractData'));
	}

	/**
	 * @see the expectation for testExtractData, except this test will test with the expectation
	 *      if the node in the response doesn't exists it will assigned those key values array null values
	 * @mock EbayEnterprise_Eb2cTax_Model_Response_Orderitem::getNode - this is a magic Varien_Object method
	 * @mock EbayEnterprise_Eb2cTax_Model_Response_Orderitem::addData - this is a magic Varien_Object method
	 * @mock EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_extractByType
	 * @mock EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_validate
	 * @mock EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_processTaxData
	 * @mock EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_processDiscountTaxData
	 * @param string $response the xml string content to be loaded into the DOMDocument object
	 * @dataProvider dataProvider
	 */
	public function testExtractDataSetNullValueFormNoneExistedNodeInResponseXml($response)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($response);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('a', $doc->documentElement->namespaceURI);

		$itemNode = $this->_getItemNode($doc, $xpath);

		$orderitemModelMock = $this->getModelMockBuilder('eb2ctax/response_orderitem')
			->disableOriginalConstructor()
			->setMethods(array('getNode', 'addData', '_validate', '_processTaxData', '_extractByType'))
			->getMock();
		$orderitemModelMock->expects($this->once())
			->method('getNode')
			->will($this->returnValue($itemNode));
		$orderitemModelMock->expects($this->once())
			->method('addData')
			->with($this->equalTo(array(
				'sku' => 'gc_virtual1',
				'line_number' => '1',
				'item_desc' => 'Test Item 1',
				'hts_code' => 'duty code',
				'merchandise_amount' => null,
				'unit_price' => null,
				'shipping_amount' => null,
				'duty_amount' => null,
				'error_duty'        => '',
				'error_merchandise' => '',
				'error_shipping'    => '',
			)))
			->will($this->returnSelf());
		$orderitemModelMock->expects($this->exactly(2))
			->method('_extractByType')
			->will($this->returnValueMap(array(
				array(
					$itemNode,
					$xpath,
					array(
						'error_duty'        => 'string(a:Pricing/a:Duty/a:CalculationError)',
						'error_merchandise' => 'string(a:Pricing/a:Merchandise/a:CalculationError)',
						'error_shipping'    => 'string(a:Pricing/a:Shipping/a:CalculationError)',
						'hts_code'          => 'string(./a:HTSCode)',
						'item_desc'         => 'string(./a:ItemDesc)',
						'line_number'       => 'string(./@lineNumber)',
						'sku'               => 'string(./a:ItemId)',
					),
					'string',
					array(
						'error_duty'        => '',
						'error_merchandise' => '',
						'error_shipping'    => '',
						'hts_code'          => 'duty code',
						'item_desc'         => 'Test Item 1',
						'line_number'       => '1',
						'sku'               => 'gc_virtual1',
					)
				),
				array(
					$itemNode,
					$xpath,
					array(
						'merchandise_amount' => 'number(a:Pricing/a:Merchandise/a:Amount)',
						'unit_price' => 'number(a:Pricing/a:Merchandise/a:UnitPrice)',
						'shipping_amount' => 'number(a:Pricing/a:Shipping/a:Amount)',
						'duty_amount' => 'number(a:Pricing/a:Duty/a:Amount)'
					),
					'float',
					array(
						'merchandise_amount' => null,
						'unit_price' => null,
						'shipping_amount' => null,
						'duty_amount' => null
					)
				)
			)));
		$orderitemModelMock->expects($this->once())
			->method('_validate')
			->will($this->returnValue(true));
		$orderitemModelMock->expects($this->exactly(2))
			->method('_processTaxData')
			->will($this->returnValueMap(array(
				array($xpath, $itemNode, false, array()),
				array($xpath, $itemNode, true, array()),
			)));
		// setting class property _xpath to a known state
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderitemModelMock, '_xpath', $xpath);

		// setting class property _isValid to a known state
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($orderitemModelMock, '_isValid', true);

		$this->assertNull(EcomDev_Utils_Reflection::invokeRestrictedMethod($orderitemModelMock, '_extractData'));
	}

	/**
	 * Testing _extractByType method with the following expectations
	 * Expectation 1: the EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_extractByType is being tested for two scenarios
	 *                in this test one where the last parameter (type) change from string to float asserting the key value
	 *                map will change from string value map to float values for the array keys result
	 * Expectation 2: the EbayEnterprise_Eb2cTax_Model_Response_Orderitem::_extractByType is expected DomElement object
	 *                as its first parameter, which the test will provide by the provider as the response string
	 *                content which it load into a DOMDocument object, this dom object get pass to the creation of a DOMXPath object
	 *                this xpath object register the name space of the dom object document element, the helper method get the shipgroup
	 *                DomElement to be pass as the first parameter to the _extractByType method and along with the xpath object as the second
	 *                parameter, also the array key map to extract the key values, the fourth parameter determine how set the value to the array key
	 *                if float is pass and the key values are no zero the value will be cast as float
	 * @param string $response the xml string content to be loaded into the DOMDocument object
	 * @dataProvider dataProvider
	 */
	public function testExtractByType($response)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($response);

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('a', $doc->documentElement->namespaceURI);

		$itemNode = $this->_getItemNode($doc, $xpath);

		$mapFloat = array(
			'merchandise_amount' => 'number(a:Pricing/a:Merchandise/a:Amount)',
			'unit_price' => 'number(a:Pricing/a:Merchandise/a:UnitPrice)',
			'shipping_amount' => 'number(a:Pricing/a:Shipping/a:Amount)',
			'duty_amount' => 'number(a:Pricing/a:Duty/a:Amount)'
		);

		$orderItem = Mage::getModel('eb2ctax/response_orderitem');
		$this->assertSame(
			array(
				'merchandise_amount' => 200.00,
				'unit_price' => 100.00,
				'shipping_amount' => 10.00,
				'duty_amount' => 2.00
			),
			EcomDev_Utils_Reflection::invokeRestrictedMethod($orderItem, '_extractByType', array($itemNode, $xpath, $mapFloat, 'float'))
		);

		$mapString = array(
			'sku' => 'string(./a:ItemId)',
			'line_number' => 'string(./@lineNumber)',
			'item_desc' => 'string(./a:ItemDesc)',
			'hts_code' => 'string(./a:HTSCode)',
		);

		$orderItem = Mage::getModel('eb2ctax/response_orderitem');
		$this->assertSame(
			array(
				'sku' => 'gc_virtual1',
				'line_number' => '1',
				'item_desc' => 'Test Item 1',
				'hts_code' => 'duty code'
			),
			EcomDev_Utils_Reflection::invokeRestrictedMethod($orderItem, '_extractByType', array($itemNode, $xpath, $mapString, 'string'))
		);
	}
}
