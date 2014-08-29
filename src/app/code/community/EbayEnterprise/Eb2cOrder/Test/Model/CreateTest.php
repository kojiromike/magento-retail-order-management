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

class EbayEnterprise_Eb2cOrder_Test_Model_CreateTest extends EbayEnterprise_Eb2cOrder_Test_Abstract
{
	const SAMPLE_SUCCESS_XML = <<<SUCCESS_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderCreateResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
  <ResponseStatus>Success</ResponseStatus>
</OrderCreateResponse>
SUCCESS_XML;
	const SAMPLE_FAILED_XML = <<<FAILED_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderCreateResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0">
  <ResponseStatus>Failed</ResponseStatus>
</OrderCreateResponse>
FAILED_XML;
	const SAMPLE_INVALID_XML = <<<INVALID_XML
<?xml version="1.0" encoding="UTF-8"?>
<OrderCreateResponse>
This is a fine mess ollie.
INVALID_XML;

	const SAMPLE_PBRIDGE_ADDITIONAL_DATA = 'a:1:{s:12:"pbridge_data";a:5:{s:23:"original_payment_method";s:22:"pbridge_eb2cpayment_cc";s:5:"token";s:32:"aee4b59993ceffaa5de7b154f9e494a3";s:8:"cc_last4";s:4:"0101";s:7:"cc_type";s:2:"VI";s:8:"x_params";s:4:"null";}}';
	/**
	 * Disable events for these tests to prevent unwanted event based side effects.
	 */
	public function setUp()
	{
		Mage::app()->disableEvents();
		parent::setUp();
	}
	/**
	 * Re-enable events disabled in setUp
	 */
	public function tearDown()
	{
		Mage::app()->enableEvents();
		parent::tearDown();
	}
	/**
	 * Test getPbridgeData returns format we can consume
	 */
	public function testParsePbridgeAdditionalData()
	{
		$create = Mage::getModel('eb2corder/create');
		$method = $this->_reflectMethod($create, '_getPbridgeData');
		$testPbridge = $method->invoke($create, self::SAMPLE_PBRIDGE_ADDITIONAL_DATA);
		$this->assertEquals('VI', $testPbridge['cc_type']);
	}
	/**
	 * Test getting tax quotes for a given item
	 * @dataProvider dataProvider
	 */
	public function testGettingTaxQuotesForItem($taxType)
	{
		$item = $this->getModelMock('sales/order_item', array('getQuoteItemId'));
		$item->expects($this->any())
			->method('getQuoteItemId')
			->will($this->returnValue(23));
		$quoteCollection = $this->getModelMockBuilder('eb2ctax/resource_response_quote_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToFilter'))
			->getMock();
		$quoteCollection->expects($this->exactly(2))
			->method('addFieldToFilter')
			->will($this->returnSelf());
		$quoteCollection->expects($this->at(0))
			->method('addFieldToFilter')
			->with($this->identicalTo('quote_item_id'), $this->identicalTo(23));
		$quoteCollection->expects($this->at(1))
			->method('addFieldToFilter')
			->with($this->identicalTo('type'), $this->identicalTo($taxType));
		$taxQuote = $this->getModelMock('eb2ctax/response_quote', array('getCollection'));
		$taxQuote->expects($this->any())
			->method('getCollection')
			->will($this->returnValue($quoteCollection));
		$this->replaceByMock('model', 'eb2ctax/response_quote', $taxQuote);
		Mage::getModel('eb2corder/create')->getItemTaxQuotes($item, $taxType);
	}
	/**
	 * Test building out a DOMDocumentFragment for tax nodes
	 * The expectations for the EbayEnterprise_Eb2cOrder_Model_Create::_buildTaxDataNodes is as followed
	 * Expectation 1: the EbayEnterprise_Eb2cOrder_Model_Create::_buildTaxDataNodes method is expected to be invoked with
	 *                a mock of EbayEnterprise_Eb2cTax_Model_Resource_Response_Quote_Collection object as the its first parameter
	 *                and a real Mage_Sales_Model_Order_Item object with product object loaded to it, then this method (_buildTaxDataNodes)
	 *                is expected to return DOMDocumentFragment object
	 * Expectation 2: the return value of EbayEnterprise_Eb2cOrder_Model_Create::_buildTaxDataNodes method get inspected for
	 *                for valid nodes that should exists in the returned DOMDocumentFragment object
	 * Expectation 3: the Mage_Tax_Model_Calculation::round method is expected to be called 6 times because the test mocked
	 *                the EbayEnterprise_Eb2cTax_Model_Resource_Response_Quote_Collection::getIterator methods to run an array of two
	 *                real EbayEnterprise_Eb2cTax_Model_Response_Quote object elements with data loaded to them. Each iteration from the
	 *                EbayEnterprise_Eb2cTax_Model_Resource_Response_Quote_Collection::getIterator result will call the
	 *                Mage_Tax_Model_Calculation::round method 3 times
	 * Expectation 4: the class property EbayEnterprise_Eb2cOrder_Model_Create::_domRequest is expected to be initalized with an object of
	 *                EbayEnterprise_Dom_Document type, so the test set this property to a known state with the instantiation of
	 *                EbayEnterprise_Dom_Document class
	 * @mock Mage_Tax_Model_Calculation::round
	 * @mock EbayEnterprise_Eb2cTax_Model_Resource_Response_Quote_Collection::getIterator
	 * @mock EbayEnterprise_Eb2cTax_Model_Resource_Response_Quote_Collection::count
	 */
	public function testBuildingTaxNodes()
	{
		$calculationModelMock = $this->getModelMockBuilder('tax/calculation')
			->disableOriginalConstructor()
			->setMethods(array('round'))
			->getMock();
		$calculationModelMock->expects($this->exactly(6))
			->method('round')
			->will($this->returnCallback(function($n) {
					return round($n, 2);
				}));
		$this->replaceByMock('model', 'tax/calculation', $calculationModelMock);
		$taxQuotes = array();
		$taxQuotes[] = Mage::getModel('eb2ctax/response_quote', array(
			'id' => '1',
			'quote_item_id' => '15',
			'type' => '0',
			'tax_type' => 'SALES',
			'taxability' => 'TAXABLE',
			'jurisdiction' => 'PENNSYLVANIA',
			'jurisdiction_id' => '31152',
			'jurisdiction_level' => 'STATE',
			'imposition' => 'Sales and Use Tax',
			'imposition_type' => 'General Sales and Use Tax',
			'situs' => 'ADMINISTRATIVE_ORIGIN',
			'effective_rate' => 0.06,
			'taxable_amount' => 43.96,
			'calculated_tax' => 2.64,
		));
		$taxQuotes[] = Mage::getModel('eb2ctax/response_quote', array(
			'id' => '2',
			'quote_item_id' => '15',
			'type' => '0',
			'tax_type' => 'CONSUMER_USE',
			'taxability' => 'TAXABLE',
			'jurisdiction' => 'PENNSYLVANIA',
			'jurisdiction_id' => '31152',
			'jurisdiction_level' => 'STATE',
			'imposition' => 'Some Other Tax',
			'imposition_type' => 'General Sales and Use Tax',
			'situs' => 'ADMINISTRATIVE_ORIGIN',
			'effective_rate' => 0.01,
			'taxable_amount' => 43.96,
			'calculated_tax' => 00.44,
		));
		$taxQuotesCollection = $this->getModelMockBuilder('eb2ctax/resource_response_quote_collection')
			->disableOriginalConstructor()
			->setMethods(array('getIterator', 'count'))
			->getMock();
		$taxQuotesCollection->expects($this->any())
			->method('getIterator')
			->will($this->returnValue(new ArrayIterator($taxQuotes)));
		$taxQuotesCollection->expects($this->any())
			->method('count')
			->will($this->returnValue(2));

		$create = $this->getModelMockBuilder('eb2corder/create')
			->getMock();

		$request = $this->_reflectProperty($create, '_domRequest');
		$request->setValue($create, Mage::helper('eb2ccore')->getNewDomDocument());
		$taxFragment = $this->_reflectMethod($create, '_buildTaxDataNodes')->invoke($create, $taxQuotesCollection, '77');
		// probe the tax fragment a bit to hopefully ensure the nodes are all populated right
		$this->assertSame(1, $taxFragment->childNodes->length);
		$this->assertSame('TaxData', $taxFragment->firstChild->nodeName);
		$this->assertSame('TaxClass', $taxFragment->firstChild->firstChild->nodeName);
		$this->assertSame('77', $taxFragment->firstChild->firstChild->nodeValue);
		$this->assertSame('Taxes', $taxFragment->lastChild->lastChild->nodeName);

		$taxes = $taxFragment->lastChild->lastChild;
		$this->assertSame(2, $taxes->childNodes->length);
		foreach ($taxes->childNodes as $idx => $taxNode) {
			$this->assertSame('Tax', $taxNode->nodeName);
			// check the attributes on the Tax node
			$attrs = $taxNode->attributes;
			$this->assertSame($taxQuotes[$idx]->getTaxType(), $attrs->getNamedItem('taxType')->nodeValue);
			$this->assertSame($taxQuotes[$idx]->getTaxability(), $attrs->getNamedItem('taxability')->nodeValue);
			foreach ($taxNode->childNodes as $taxData) {
				// test a few of the child nodes, making sure they're getting set properly per tax quote
				switch ($taxData->nodeName) {
					case 'Situs':
						$this->assertSame($taxQuotes[$idx]->getSitus(), $taxData->nodeValue);
						break;
					case 'EffectiveRate':
						$this->assertSame($taxQuotes[$idx]->getEffectiveRate(), (float) $taxData->nodeValue);
						break;
					case 'Imposition':
						$this->assertSame($taxQuotes[$idx]->getImposition(), $taxData->nodeValue);
						break;
				}
			}
		}
	}
	/**
	 * When the observer triggers, the create model should build a new request
	 * and send it.
	 */
	public function testObserverCreate()
	{
		$order = Mage::getModel('sales/order');
		$event = new Varien_Event(array('order' => $order));
		$observer = new Varien_Event_Observer(array('event' => $event));
		$create = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('buildRequest', 'sendRequest'))
			->getMock();
		$create->expects($this->once())
			->method('buildRequest')
			->with($this->identicalTo($order))
			->will($this->returnSelf());
		$create->expects($this->once())
			->method('sendRequest')
			->will($this->returnSelf());
		$create->observerCreate($observer);
	}
	/**
	 * Successful sending of the request should take the already constructed OrderCreate
	 * request and send it via the Eb2cCore Api model	and then process the response.
	 */
	public function testSendRequest()
	{
		$requestDoc = Mage::helper('eb2ccore')->getNewDomDocument();
		$this->replaceCoreConfigRegistry(array(
			'serviceOrderTimeout' => 100,
			'xsdFileCreate' => 'example.xsd',
			'apiService' => 'orders',
			'apiCreateOperation' => 'create',
		));
		$helperStub = $this->getHelperMock('eb2corder/data', array('getOperationUri'));
		$helperStub->expects($this->once())
			->method('getOperationUri')
			->with($this->identicalTo('create'))
			->will($this->returnValue('http://example.com/order/create.xml'));
		$this->replaceByMock('helper', 'eb2corder', $helperStub);
		$apiStub = $this->getModelMock('eb2ccore/api', array('addData', 'request'));
		$apiStub->expects($this->any())
			->method('addData')
			->with($this->logicalAnd(
				$this->arrayHasKey('uri'),
				$this->arrayHasKey('timeout'),
				$this->arrayHasKey('xsd')
			))
			->will($this->returnSelf());
		$apiStub->expects($this->once())
			->method('request')
			->with($this->identicalTo($requestDoc))
			->will($this->returnValue(self::SAMPLE_SUCCESS_XML));
		$this->replaceByMock('model', 'eb2ccore/api', $apiStub);
		$create = $this->getModelMockBuilder('eb2corder/create')
			->setMethods(array('_processResponse'))
			->getMock();
		$create->expects($this->once())
			->method('_processResponse')
			->with($this->identicalTo(self::SAMPLE_SUCCESS_XML))
			->will($this->returnSelf());
		$createRequest = $this->_reflectProperty($create, '_domRequest');
		$createRequest->setValue($create, $requestDoc);
		$create->sendRequest();
	}
	/**
	 * Building the XML nodes for a given order item
	 * @todo This method should really be broken down into some smaller chunks to make this test less complicated
	 * @param array $itemData Order item object data
	 * @param array $orderData Order object data
	 * @param boolean $merchTax Should this item have merchandise taxes
	 * @param boolean $shippingTax Should this item have shipping taxes
	 * @param boolean $dutyTax Should this item have duty taxes
	 * @dataProvider dataProvider
	 */
	public function testBuildOrderItemNodes($itemData, $orderData, $merchTax, $shippingTax, $dutyTax)
	{
		$order = Mage::getModel('sales/order', $orderData);
		$item = Mage::getModel('sales/order_item', $itemData);
		$item->setOrder($order);
		if (!isset($itemData['eb2c_reservation_id'])) {
			$invHelper = $this->getHelperMock('eb2cinventory/data', array('getRequestId'));
			$invHelper->expects($this->once())
				->method('getRequestId')
				->with($orderData['quote_id'])
				->will($this->returnValue('generated_reservation_id'));
			$this->replaceByMock('helper', 'eb2cinventory', $invHelper);
		}
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$itemElement = $doc->appendChild($doc->createElement('root', null))->appendChild($doc->createElement('Item', null));
		// DOMDocumentFragments for mocked responses to _buildTaxDataNodes and _buildDuty
		$emptyFragment = $doc->createDocumentFragment();
		$taxFragment = $doc->createDocumentFragment();
		$taxFragment->appendChild($doc->createElement('MockedTaxNodes'));
		$dutyFragment = $doc->createDocumentFragment();
		$dutyFragment->appendChild($doc->createElement('MockedDutyNodes'));
		$create = $this->getModelMock('eb2corder/create', array(
			'_buildTaxDataNodes', 'getItemTaxQuotes', '_buildDuty',
			'_getItemShippingAmount', '_getShippingChargeType',
			'_buildEstimatedDeliveryDate', '_buildDiscount'
		));
		$create->expects($this->exactly(2))
			->method('_buildTaxDataNodes')
			->will($this->onConsecutiveCalls(
				$merchTax ? $taxFragment : $emptyFragment,
				$shippingTax ? $taxFragment : $emptyFragment
			));
		$create->expects($this->exactly(2))
			->method('getItemTaxQuotes')
			->with(
				$this->identicalTo($item),
				$this->logicalOr(
					$this->identicalTo(EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE),
					$this->identicalTo(EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING)
				)
			)
			->will($this->returnValue(Mage::getModel('eb2ctax/resource_response_quote_collection')));
		$create->expects($this->any())
			->method('_buildDuty')
			->will(
				$this->returnValue($dutyTax ? $dutyFragment : $emptyFragment)
			);
		$create->expects($this->any())
			->method('_getShippingChargeType')
			->will($this->returnValue('FLATRATE'));
		$create->expects($this->any())
			->method('_getItemShippingAmount')
			->will($this->returnValue(5.00));
		$create->expects($this->once())
			->method('_buildEstimatedDeliveryDate')
			->with($itemElement, $item)
			->will($this->returnValue(null));

		$create->expects($this->any())
			->method('_buildDiscount')
			->will($this->returnSelf());

		$orderProp = $this->_reflectProperty($create, '_o');
		$orderProp->setValue($create, $order);
		$buildOrderItemMethod = $this->_reflectMethod($create, '_buildOrderItem');
		$buildOrderItemMethod->invoke($create, $itemElement, $item, 1);
		// the itemElement should have been modified, adding the item nodes onto it
		$this->assertTrue($itemElement->hasChildNodes(), 'No child nodes added to Item node');
		$expectedChildNodes = array('ItemId', 'Quantity', 'Description', 'Pricing', 'ShippingMethod', 'ReservationId');
		$includedChildNodes = array();
		foreach ($itemElement->childNodes as $node) {
			$includedChildNodes[] = $node->nodeName;
		}
		$diff = array_diff($expectedChildNodes, $includedChildNodes);
		$this->assertEmpty($diff, 'Item is missing required child nodes - ' . implode(', ', $diff));
	}
	/**
	 * verify the order collection filters are prepared properly
	 */
	public function testGetNewOrders()
	{
		$collection = $this->getResourceModelMockBuilder('sales/order_collection')
			->disableOriginalConstructor()
			->setMethods(array(
				'addAttributeToSelect',
				'addFieldToFilter',
				'load',
			))
			->getMock();
		$collection->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->identicalTo('*'))
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('addFieldToFilter')
			->with(
				$this->identicalTo('state'),
				$this->identicalTo(array('eq' => 'new'))
			)
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('load')
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'sales/order_collection', $collection);
		$testModel = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();
		$this->assertSame(
			$collection,
			$this->_reflectMethod($testModel, '_getNewOrders')->invoke($testModel)
		);
	}
	/**
	 * verify the delivery window dates are extracted from $item
	 * verify the dom nodes returned have the correct structure.
	 */
	public function testBuildEstimatedDeliveryDate()
	{
		$item = $this->getModelMockBuilder('sales/order_item')
			->disableOriginalConstructor()
			->setMethods(array(
				'getEb2cDeliveryWindowFrom',
				'getEb2cDeliveryWindowTo',
				'getEb2cShippingWindowFrom',
				'getEb2cShippingWindowTo',
			))
			->getMock();
		$item->expects($this->once())
			->method('getEb2cDeliveryWindowFrom')
			->will($this->returnValue('2014-01-28T20:46:34+00:00'));
		$item->expects($this->once())
			->method('getEb2cDeliveryWindowTo')
			->will($this->returnValue('2014-01-29T17:36:08Z'));
		$item->expects($this->once())
			->method('getEb2cShippingWindowFrom')
			->will($this->returnValue('2014-01-21 17:36:08'));
		$item->expects($this->once())
			->method('getEb2cShippingWindowTo')
			->will($this->returnValue('2014-01-27T17:36:08Z'));
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$orderItem = $doc->addElement('OrderItem')
			->documentElement;
		$testModel = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();
		$this->_reflectMethod($testModel, '_buildEstimatedDeliveryDate')
			->invoke($testModel, $orderItem, $item);
		$x = new DomXPath($doc);
		$paths = array(
			'EstimatedDeliveryDate/DeliveryWindow/From[.="2014-01-28T20:46:34+00:00"]',
			'EstimatedDeliveryDate/DeliveryWindow/To[.="2014-01-29T17:36:08+00:00"]',
			'EstimatedDeliveryDate/ShippingWindow/From[.="2014-01-21T17:36:08+00:00"]',
			'EstimatedDeliveryDate/ShippingWindow/To[.="2014-01-27T17:36:08+00:00"]',
			'EstimatedDeliveryDate/Mode[.="LEGACY"]',
			'EstimatedDeliveryDate/MessageType[.="DeliveryDate"]',
		);
		foreach ($paths as $path) {
			$this->assertNotNull(
				$x->query($path, $orderItem)->item(0),
				$path . ' does not exist'
			);
		}
	}
	/**
	 * Test _buildOrderCreateRequest method
	 */
	public function testBuildOrderCreateRequest()
	{
		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_getRequestId'))
			->getMock();
		$createModelMock->expects($this->once())
			->method('_getRequestId')
			->will($this->returnValue('12838-383848-944'));
		$this->_reflectProperty($createModelMock, '_domRequest')->setValue($createModelMock, Mage::helper('eb2ccore')->getNewDomDocument());

		$helperMock = $this->getHelperMock('eb2corder/data', array('getConfigModel'));
		$helperMock->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'apiCreateDomRootNodeName' => 'OrderCreateRequest',
				'apiXmlNs' => 'http://api.gsicommerce.com/schema/checkout/1.0',
				'apiOrderType' => 'SALES'
			))));
		$this->replaceByMock('helper', 'eb2corder', $helperMock);

		$this->assertInstanceOf(
			'EbayEnterprise_Dom_Element',
			$this->_reflectMethod($createModelMock, '_buildOrderCreateRequest')->invoke($createModelMock)
		);
	}
	/**
	 * Test updating a quote based on the response from the Exchange Platform
	 * service call. Should update the order state based on the success/failure
	 * of the response as well as store the original request message with the
	 * order.
	 */
	public function testProcessResponse()
	{
		$mockRequestMessage = '<MockRequest/>';
		$mockResponseMessage = '<MockResponse/>';
		$expectedState = 'NEW';

		$orderCreate = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_extractResponseState'))
			->getMock();
		$order = $this->getModelMock('sales/order', array('setState', 'setEb2cOrderCreateRequest'));
		$requestDom = new DOMDocument();
		$requestDom->loadXML($mockRequestMessage);

		EcomDev_Utils_Reflection::setRestrictedPropertyValue(
			$orderCreate, '_o', $order
		);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue(
			$orderCreate, '_domRequest', $requestDom
		);

		$order->expects($this->once())
			->method('setState')
			->with($this->identicalTo($expectedState))
			->will($this->returnSelf());
		$order->expects($this->once())
			->method('setEb2cOrderCreateRequest')
			->with($this->identicalTo($requestDom->saveXML()))
			->will($this->returnSelf());

		$orderCreate->expects($this->once())
			->method('_extractResponseState')
			->with($this->identicalTo($mockResponseMessage))
			->will($this->returnValue($expectedState));

		$this->assertSame(
			$orderCreate,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$orderCreate,
				'_processResponse',
				array($mockResponseMessage)
			)
		);
	}
	/**
	 * If the response XML exists and has a ResponseStatus node with a value of 'success' in any capitalization,
	 * we should see a value of STATE_PROCESSING. Otherwise, we should see STATE_NEW.
	 *
	 */
	public function testExtractResponseState()
	{
		// stub out the order helper used for translating the failure message
		$translateHelper = $this->getHelperMock('eb2corder/data', array('__'));
		$translateHelper->expects($this->any())->method('__')->will($this->returnArgument(0));
		$this->replaceByMock('helper', 'eb2corder', $translateHelper);

		$create = Mage::getModel('eb2corder/create');
		$crRefl = new ReflectionClass($create);
		$extRespSt = $crRefl->getMethod('_extractResponseState');
		$extRespSt->setAccessible(true);
		$this->assertSame(Mage_Sales_Model_Order::STATE_NEW, $extRespSt->invoke($create, ''));
		$this->assertSame(Mage_Sales_Model_Order::STATE_NEW, $extRespSt->invoke($create, '<fail/>'));
		$this->assertSame(Mage_Sales_Model_Order::STATE_NEW, $extRespSt->invoke(
			$create,
			'<_><ResponseStatus>nobodyhome!</ResponseStatus></_>'
		));
		$this->assertSame(
			Mage_Sales_Model_Order::STATE_PROCESSING,
			$extRespSt->invoke($create, '<_><ResponseStatus>sUcCeSs</ResponseStatus></_>')
		);
		$this->setExpectedException('EbayEnterprise_Eb2cOrder_Exception_Order_Create_Fail');
		$extRespSt->invoke($create, '<_><ResponseStatus>fail</ResponseStatus></_>');
	}
	/**
	 * Test _buildItems method
	 */
	public function testBuildItems()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root/>');
		$itemModelMock = $this->getModelMockBuilder('sales/order_item')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$orderModelMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getAllVisibleItems'))
			->getMock();
		$orderModelMock->expects($this->once())
			->method('getAllVisibleItems')
			->will($this->returnValue(array($itemModelMock)));
		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_buildOrderItem'))
			->getMock();
		$createModelMock->expects($this->once())
			->method('_buildOrderItem')
			->with($this->isInstanceOf('DOMElement'), $this->isInstanceOf('Mage_Sales_Model_Order_Item'), $this->equalTo(1))
			->will($this->returnValue(null));
		$this->_reflectProperty($createModelMock, '_o')->setValue($createModelMock, $orderModelMock);
		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cOrder_Model_Create',
			$this->_reflectMethod($createModelMock, '_buildItems')->invoke($createModelMock, $doc->documentElement)
		);
	}
	/**
	 * Test _buildShip method
	 */
	public function testBuildShip()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(
			'<root>
				<foo></foo>
			</root>'
		);
		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_buildShipGroup', '_buildShipping'))
			->getMock();
		$createModelMock->expects($this->once())
			->method('_buildShipGroup')
			->with($this->isInstanceOf('DOMElement'))
			->will($this->returnValue(null));
		$createModelMock->expects($this->once())
			->method('_buildShipping')
			->with($this->isInstanceOf('DOMElement'))
			->will($this->returnValue(null));
		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cOrder_Model_Create',
			$this->_reflectMethod($createModelMock, '_buildShip')->invoke($createModelMock, $doc->documentElement)
		);
	}
	/**
	 * Provide gift message id, if the message should include a printed card
	 * and the gift wrapping id.
	 * @return array
	 */
	public function provideGiftMessage()
	{
		return array(
			array(12, true, null, 'message-card-nowrap'),
			array(12, true, 3, 'message-card-wrap'),
			array(12, false, null, 'message-pack-nowrap'),
			array(12, false, 3, 'message-pack-wrap'),
			array(null, null, 3, 'nomessage-wrap'),
			array(null, null, null, 'nomessage-nowrap'),
		);
	}
	/**
	 * Test building out the gifting nodes for an order item. The order item
	 * could just as well be an order as the interface for getting a gift message
	 * from the order is exactly the same as an order item. Given an order or
	 * order item, should look for a gift message associated with the order
	 * or order item. If present, should add the appripriate "Gifting" nodes to
	 * the a given node.
	 * @param int|null $giftMessageId ID of the related message if one is expected to exist
	 * @param bool|null $addCard If the order should include a printed card
	 * @param int|null $giftWrapId ID of the gift wrapping to apply if gift wrapping was added
	 * @param string $expectationKey Expectation key for the scenario
	 * @dataProvider provideGiftMessage
	 */
	public function testBuildGiftingNodes($giftMessageId, $addCard, $giftWrapId, $expectationKey)
	{
		$sender = 'John<b/> Doe';
		$cleanSender = 'John Doe';
		$recipient = 'Jane <i>Doe</i>';
		$cleanRecipient = 'Jane Doe';
		$message = 'Who <s>are</s> you?';
		$cleanMessage = 'Who are you?';
		// Expect this DOMDocument to be modified to include the appropriate nodes
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root></root>');

		// Order item to get gift message data from. May just as well be an order
		// object as means of getting related gift messages is the same for both
		// types of objects.
		$orderItem = Mage::getModel(
			'sales/order_item',
			array('gift_message_id' => $giftMessageId, 'gw_id' => $giftWrapId)
		);
		// Order object being processed - order create model's "_o" property.
		// Whether the object being processed is an order item or order, only the
		// order in the "_o" property should be checked for the printed card.
		$order = Mage::getModel(
			'sales/order',
			array('gw_add_card' => $addCard)
		);

		// Related gift message
		$giftMessage = $this->getModelMock(
			'giftmessage/message',
			array('load'),
			false,
			array(array('sender' => $sender, 'recipient' => $recipient, 'message' => $message, 'gift_message_id' => $giftMessageId))
		);
		$this->replaceByMock('model', 'giftmessage/message', $giftMessage);
		// If there is a message id on the order, load the gift message with that id
		if ($giftMessageId) {
			$giftMessage->expects($this->once())
				->method('load')
				->with($this->identicalTo($giftMessageId))
				->will($this->returnSelf());
		}

		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->getMock();

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($createModelMock, '_o', $order);

		EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$createModelMock,
			'_buildGifting',
			array($doc->documentElement, $orderItem)
		);

		// When there is a related gift message, should inject this XML. When there
		// isn't a related gift message, shouldn't insert any additional XML.
		$giftingNode = sprintf(
			$this->expected($expectationKey)->getXml(),
			$cleanSender, $cleanRecipient, $cleanMessage
		);

		$this->assertSame(
			"<root>{$giftingNode}</root>",
			$doc->C14N()
		);
	}
	/**
	 * Test buildRequest method
	 */
	public function testBuildRequest()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(
			'<root>
				<foo></foo>
			</root>'
		);
		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($doc));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);
		$orderModelMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();
		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_buildOrderCreateRequest', '_buildOrder', '_buildItems', '_buildShip', '_buildPayment', '_buildAdditionalOrderNodes', '_buildContext'))
			->getMock();
		$createModelMock->expects($this->once())
			->method('_buildOrderCreateRequest')
			->will($this->returnValue($doc->documentElement));
		$createModelMock->expects($this->once())
			->method('_buildOrder')
			->with($this->isInstanceOf('EbayEnterprise_Dom_Element'))
			->will($this->returnValue($doc->documentElement));
		$createModelMock->expects($this->once())
			->method('_buildItems')
			->with($this->isInstanceOf('EbayEnterprise_Dom_Element'))
			->will($this->returnSelf());
		$createModelMock->expects($this->once())
			->method('_buildShip')
			->with($this->isInstanceOf('EbayEnterprise_Dom_Element'))
			->will($this->returnSelf());
		$createModelMock->expects($this->once())
			->method('_buildPayment')
			->with($this->isInstanceOf('EbayEnterprise_Dom_Element'))
			->will($this->returnSelf());
		$createModelMock->expects($this->once())
			->method('_buildAdditionalOrderNodes')
			->with($this->isInstanceOf('EbayEnterprise_Dom_Element'))
			->will($this->returnSelf());
		$createModelMock->expects($this->once())
			->method('_buildContext')
			->with($this->isInstanceOf('EbayEnterprise_Dom_Element'))
			->will($this->returnSelf());
		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cOrder_Model_Create',
			$createModelMock->buildRequest($orderModelMock)
		);
	}
	/**
	 * Test _buildPayments method for the following expectations for building paypal payment node type
	 * Expectation 1: first this test is mocking the EbayEnterprise_Eb2cCore_Model_Config_Registry::__get so that it can
	 *                enabled the eb2cpayment method, when the test run this mock will run with the parament of 'isPaymentEnabled'
	 *                in which it will return true
	 * Expectation 2: the method EbayEnterprise_Eb2cPayment_Helper_Data::getConfigModel get mocked and is expected to be called
	 *                once with and is expected to return the mocked EbayEnterprise_Eb2cCore_Model_Config_Registry object
	 * Expectation 3: setting the class property EbayEnterprise_Eb2cOrder_Model_Create::_o to a known states with a mock of order
	 *                Mage_Sales_Model_Order object, the order object mocked the method Mage_Sales_Model_Order::getAllPayments to return
	 *                a real object of Mage_Sales_Model_Order_Payment with expected data to be used in the run test method
	 * Expectation 4: set the class property to EbayEnterprise_Eb2cOrder_Model_Create::_ebcPaymentMethodMap array key value
	 *                that we expect will return when the method call the Mage_Sales_Model_Order_Payment::getMethod, which we added data for
	 * Expectation 5: mocked Mage_Sales_Model_Order::getGrandTotal method and expected to run once and to return a known value
	 * Expectation 6: this take data provider  that pass in a request base root node to be loaded into the EbayEnterprise_Dom_Document object
	 *                and then pass in the DomElment of this dom object to the EbayEnterprise_Eb2cOrder_Model_Create::_buildPayments method
	 *                when invoked by this test, it then asserted the xml in this dom object should match what is expected in the loaded expectation
	 *                for this test.
	 * @mock EbayEnterprise_Eb2cPayment_Helper_Data::getConfigModel
	 * @mock EbayEnterprise_Eb2cCore_Model_Config_Registry::__get
	 * @mock Mage_Sales_Model_Order::getAllPayments
	 * @mock Mage_Sales_Model_Order::getGrandTotal
	 * @param string $response the xml string content to be loaded into the DOMDocument object
	 * @dataProvider dataProvider
	 * @loadExpectation
	 */
	public function testBuildPaymentsPaypal($response)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($response);

		$mockConfig = $this->getModelMockBuilder('eb2ccore/config_registry')
			->setMethods(array('__get'))
			->getMock();
		$mockConfig->expects($this->once())
			->method('__get')
			->will($this->returnValueMap(array(
				array('isPaymentEnabled', true)
			)));

		$helperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($mockConfig));
		$this->replaceByMock('helper', 'eb2cpayment', $helperMock);

		$payment = Mage::getModel('sales/order_payment')->addData(array(
			'entity_id' => 1,
			'method' => 'Paypal_express',
			'created_at' => '2012-07-06 10:09:05',
			'amount_authorized' => 50.00,
			'cc_status' => 'success'
		));

		$order = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getAllPayments', 'getGrandTotal', 'getIncrementId'))
			->getMock();
		$order->expects($this->once())
			->method('getAllPayments')
			->will($this->returnValue(array($payment)));
		$order->expects($this->once())
			->method('getGrandTotal')
			->will($this->returnValue(50.00));
		$order->expects($this->once())
			->method('getIncrementId')
			->will($this->returnValue('012345678'));

		$create = Mage::getModel('eb2corder/create');
		$this->_reflectProperty($create, '_o')->setValue($create, $order);
		$this->_reflectProperty($create, '_ebcPaymentMethodMap')->setValue($create, array('Paypal_express' => 'PayPal'));
		$this->assertSame($create, $this->_reflectMethod($create, '_buildPayments')->invoke($create, $doc->documentElement));

		$this->assertSame(sprintf($this->expected('paypal')->getPaymentNode(), "\n"), trim($doc->saveXML()));
	}
	/**
	 * Test building the payment nodes for stored value cards. Should build the
	 * <StoreValueCard> and necessary child nodes and attach it to the DOMElement
	 * passed to the method.
	 */
	public function testBuildPaymentsStoredValue()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root/>');

		$paymentId = 12;
		$orderIncrementId = '012345678';
		$svcPan = '1234567890';
		$svcPanToken = 'abcdefghij';
		$svcPin = '1234';
		$svcTenderType = 'SV';
		$gcAmount = 29.99;
		$paymentCreatedAt = '2014-01-01T01:00:00';

		// mock up the payment helper which will be used to get config if payments
		// are enabled and lookup the tender type of the SVC
		$paymentHelper = $this->getHelperMockBuilder('eb2cpayment/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel', 'getTenderType'))
			->getMock();
		// swap out config with one that will indicate payments are enabled
		$paymentHelper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array('isPaymentEnabled' => true))));
		// map the PAN to a specific tender type
		$paymentHelper->expects($this->any())
			->method('getTenderType')
			->will($this->returnValueMap(array(
				array($svcPan, $svcTenderType)
			)));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelper);

		// payment model for the SVC payment - the "Free" method is interpreted as SVC
		$payment = Mage::getModel(
			'sales/order_payment',
			array('entity_id' => $paymentId, 'created_at' => $paymentCreatedAt, 'method' => 'Free')
		);
		$order = $this->getModelMock(
			'sales/order',
			array('getAllPayments'),
			false,
			array(array(
				'increment_id' => $orderIncrementId, 'gift_cards_amount' => $gcAmount,
				'gift_cards' => serialize(array(array('pan' => $svcPan, 'panToken' => $svcPanToken, 'pin' => $svcPin)))
			))
		);
		$order->expects($this->any())
			->method('getAllPayments')
			->will($this->returnValue(array($payment)));

		$create = Mage::getModel('eb2corder/create');
		$this->_reflectProperty($create, '_o')->setValue($create, $order);
		$this->assertSame($create, $this->_reflectMethod($create, '_buildPayments')->invoke($create, $doc->documentElement));

		$resultDoc = new DOMDocument();
		$resultDoc->loadXML(sprintf(
			'<?xml version="1.0"?><root><StoredValueCard><PaymentContext><PaymentSessionId>%1$s</PaymentSessionId><TenderType>%2$s</TenderType><PaymentAccountUniqueId isToken="true">%3$s</PaymentAccountUniqueId></PaymentContext><CreateTimeStamp>%5$s</CreateTimeStamp><Pin>%6$s</Pin><Amount>%4$.2f</Amount></StoredValueCard></root>',
			$orderIncrementId, $svcTenderType, $svcPanToken, $gcAmount, $paymentCreatedAt, $svcPin
		));
		$this->assertSame(
			$resultDoc->C14N(),
			trim($doc->C14N())
		);
	}

	/**
	 * Provide a gift card PAN and PAN token, whether the lookup should be for
	 * a token or raw value and what the test should expect to be found
	 * @return array Args array
	 */
	public function provideOrderPanAndToken()
	{
		return array(
			array('1234567890', 'abcdefghij', true, 'abcdefghij'),
			array('1234567890', '', true, ''),
			array('1234567890', null, true, ''),
			array(null, null, true, ''),
			array('1234567890', 'abcdefghij', false, '1234567890'),
			array('', 'abcdefghij', false, ''),
			array(null, 'abcdefghij', false, ''),
			array(null, null, false, ''),
		);
	}
	/**
	 * Test getting the PAN from an order. Should be able to return the raw
	 * PAN code or the tokenized PAN code received when redeeming the SVC.
	 * When $useToken is true, $expected must be the $panToken or '' if no token
	 * When $useToken is false, $expected must be the $pan or '' if no PAN
	 * @param string $pan SVC PAN
	 * @param string $panToken Tokenized SVC PAN
	 * @param bool   $useToken Look for a token, true, or raw value, false
	 * @param string $expected Value expected to be returned
	 * @dataProvider provideOrderPanAndToken
	 */
	public function testGetOrderGiftCardPan($pan, $panToken, $useToken, $expected)
	{
		$gcData = array();
		// only add the 'pan' and 'panToken' if they were supplied - simulate the
		// data not existing at all instead of just being null
		if (!is_null($pan)) {
			$gcData['pan'] = $pan;
		}
		if (!is_null($panToken)) {
			$gcData['panToken'] = $panToken;
		}

		$order = Mage::getModel(
			'sales/order',
			array('gift_cards' => serialize(!empty($gcData) ? array($gcData) : array()))
		);

		$create = Mage::getModel('eb2corder/create');
		$this->assertSame(
			$expected,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($create, '_getOrderGiftCardPan', array($order, $useToken))
		);
	}
	/**
	 * Test getting the PIN of the SVC used for the order.
	 */
	public function testGetOrderGiftCardPin()
	{
		$svcPin = '1234';
		$gcData = array('pin' => $svcPin);
		$order = Mage::getModel(
			'sales/order',
			array('gift_cards' => serialize(array($gcData)))
		);
		$create = Mage::getModel('eb2corder/create');
		$this->assertSame(
			$svcPin,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($create, '_getOrderGiftCardPin', array($order))
		);
	}
	public function provideForTestGetOrderSource()
	{
		return array(
			array(true, "don't care", EbayEnterprise_Eb2cOrder_Model_Create::BACKEND_ORDER_SOURCE),
			array(false, 'a referrer', 'a referrer'),
			array(false, '', EbayEnterprise_Eb2cOrder_Model_Create::FRONTEND_ORDER_SOURCE),
		);
	}
	/**
	 * Test EbayEnterprise_Eb2cOrder_Model_Create::_getOrderSource method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cOrder_Model_Create::_getOrderSource and expects
	 *                the method EbayEnterprise_Eb2cCore_Helper_Data::getCurrentStore to be invoked and return a mocked
	 *                of Mage_Core_Model_Store object, then the method Mage_Core_Model_Store::isAdmin is called where
	 *                true to indicate this order was created in admin which will return the class constant
	 *                EbayEnterprise_Eb2cOrder_Model_Create::BACKEND_ORDER_SOURCE
	 * @dataProvider provideForTestGetOrderSource
	 */
	public function testGetOrderSource($isAdmin, $fraudReferrer, $expected)
	{
		$orderMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getEb2cFraudReferrer'))
			->getMock();
		$orderMock->expects($this->any())
			->method('getEb2cFraudReferrer')
			->will($this->returnValue($fraudReferrer));

		$storeMock = $this->getModelMockBuilder('core/store')
			->disableOriginalConstructor()
			->setMethods(array('isAdmin'))
			->getMock();
		$storeMock->expects($this->once())
			->method('isAdmin')
			->will($this->returnValue($isAdmin));

		$helperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getCurrentStore'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getCurrentStore')
			->will($this->returnValue($storeMock));
		$this->replaceByMock('helper', 'eb2ccore', $helperMock);

		$createMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($createMock, '_o', $orderMock);
		$this->assertSame($expected, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$createMock, '_getOrderSource', array()
		));
	}

	/**
	 * Test EbayEnterprise_Eb2cOrder_Model_Create::_buildCustomer method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cOrder_Model_Create::_buildCustomer given
	 *                a mocked EbayEnterprise_Dom_Element object, the following method
	 *                EbayEnterprise_Eb2cCore_Model_Config_Registry::addConfigModel given a EbayEnterprise_Eb2cCore_Model_Config
	 *                object, the test continue to expect the following methods (getCustomerId,getCustomerPrefix,getCustomerLastname,
	 *                getCustomerSuffix,getCustomerMiddlename, getCustomerGender, getCustomerDob, getCustomerEmail, getCustomerTaxvat)
	 *                on the mocked class Mage_Sales_Model_Order, the value from the calling the method
	 *                Mage_Sales_Model_Order::getCustomerId is expected to be concatenate with the value from mocked config
	 *                registry magic class property 'clientCustomerIdPrefix' and pass as the second paremeter to the
	 *                EbayEnterprise_Dom_Element::setAttribute and the first parameter is passed as a know literal,
	 *                the method EbayEnterprise_Dom_Element::createChild is expected to be call 9 time with various parameters
	 */
	public function testBuildCustomer()
	{
		$ccPrefix = '04';
		$customerId = '93';
		$honorific = 'Mr.';
		$lastName = 'Doe';
		$suffix = 'K.';
		$middleName = '';
		$firstName = 'John';
		$gender = EbayEnterprise_Eb2cOrder_Model_Create::GENDER_MALE;
		$gMap = array($gender => 'M');
		$dob = '1985-04-19 00:00:00';
		$newDob = '1985-04-19';
		$email = 'customer@example.com';
		$taxId = '89';

		$this->replaceCoreConfigRegistry(array('clientCustomerIdPrefix' => $ccPrefix));

		$orderMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array(
				'getCustomerId', 'getCustomerPrefix', 'getCustomerLastname', 'getCustomerSuffix', 'getCustomerMiddlename',
				'getCustomerFirstname', 'getCustomerGender', 'getCustomerDob', 'getCustomerEmail', 'getCustomerTaxvat'
			))
			->getMock();
		$orderMock->expects($this->once())
			->method('getCustomerId')
			->will($this->returnValue($customerId));
		$orderMock->expects($this->once())
			->method('getCustomerPrefix')
			->will($this->returnValue($honorific));
		$orderMock->expects($this->once())
			->method('getCustomerLastname')
			->will($this->returnValue($lastName));
		$orderMock->expects($this->once())
			->method('getCustomerSuffix')
			->will($this->returnValue($suffix));
		$orderMock->expects($this->once())
			->method('getCustomerMiddlename')
			->will($this->returnValue($middleName));
		$orderMock->expects($this->once())
			->method('getCustomerFirstname')
			->will($this->returnValue($firstName));
		$orderMock->expects($this->exactly(2))
			->method('getCustomerGender')
			->will($this->returnValue($gender));
		$orderMock->expects($this->exactly(2))
			->method('getCustomerDob')
			->will($this->returnValue($dob));
		$orderMock->expects($this->once())
			->method('getCustomerEmail')
			->will($this->returnValue($email));
		$orderMock->expects($this->once())
			->method('getCustomerTaxvat')
			->will($this->returnValue($taxId));

		$elementMock = $this->getMockBuilder('EbayEnterprise_Dom_Element')
			->disableOriginalConstructor()
			->setMethods(array('setAttribute', 'createChild'))
			->getMock();
		$elementMock->expects($this->once())
			->method('setAttribute')
			->with($this->identicalTo('customerId'), $this->identicalTo($ccPrefix . $customerId))
			->will($this->returnSelf());
		$elementMock->expects($this->exactly(9))
			->method('createChild')
			->will($this->returnValueMap(array(
				array('Name', null, null, null, $elementMock),
				array('Honorific', $honorific, null, null, $elementMock),
				array('LastName', $lastName . ' ' . $suffix, null, null, $elementMock),
				array('MiddleName', $middleName, null, null, $elementMock),
				array('FirstName', $firstName, null, null, $elementMock),
				array('Gender', $gMap[$gender], null, null, $elementMock),
				array('DateOfBirth', $newDob, null, null, $elementMock),
				array('EmailAddress', $email, null, null, $elementMock),
				array('CustomerTaxId', $taxId, null, null, $elementMock),
			)));

		$createMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($createMock, '_o', $orderMock);

		$this->assertSame($createMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$createMock, '_buildCustomer', array($elementMock)
		));
	}
	/**
	 * The context element should be built up from data fetched by the fraud module.
	 */
	public function testBuildContext()
	{
		$expect = Mage::helper('eb2ccore')->getNewDomDocument();
		$expect->preserveWhiteSpace = false;
		$expect->formatOutput = true;
		$expect->loadXML('
		<root>
			<BrowserData>
				<HostName><![CDATA[some.h.ost]]></HostName>
				<IPAddress><![CDATA[1.0.0.1]]></IPAddress>
				<SessionId><![CDATA[sessionid]]></SessionId>
				<UserAgent><![CDATA[the name\'s fox. fire fox. i have a license - gpl]]></UserAgent>
				<Connection><![CDATA[close]]></Connection>
				<Cookies><![CDATA[cookie1=dacookies;cookie2=dacookiespartdeux]]></Cookies>
				<JavascriptData><![CDATA[data stuff n things]]></JavascriptData>
				<Referrer><![CDATA[this is the order_source]]></Referrer>
				<HTTPAcceptData>
					<ContentTypes><![CDATA[text/test]]></ContentTypes>
					<Encoding><![CDATA[holy encoded text, batman]]></Encoding>
					<Language><![CDATA[ebonicode]]></Language>
					<CharSet><![CDATA[some charset]]></CharSet>
				</HTTPAcceptData>
			</BrowserData>
			<TdlOrderTimestamp><![CDATA[2014-01-01T10:05:01]]></TdlOrderTimestamp>
			<SessionInfo><![CDATA[this is session data]]></SessionInfo>
		</root>'
		);

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->formatOutput = true;
		$doc->loadXML('<root/>');
		$element = $doc->documentElement;

		$order = Mage::getModel('sales/order', array(
			'eb2c_fraud_char_set'        => 'some charset',
			'eb2c_fraud_content_types'   => 'text/test',
			'eb2c_fraud_encoding'        => 'holy encoded text, batman',
			'eb2c_fraud_host_name'       => 'some.h.ost',
			'eb2c_fraud_user_agent'      => 'the name\'s fox. fire fox. i have a license - gpl',
			'eb2c_fraud_language'        => 'ebonicode',
			'eb2c_fraud_ip_address'      => '1.0.0.1',
			'eb2c_fraud_session_id'      => 'sessionid',
			'eb2c_fraud_javascript_data' => 'data stuff n things',
		));

		$checkout = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array('getEb2cFraudCookies', 'getEb2cFraudConnection', 'getEb2cFraudSessionInfo', 'getEb2cFraudTimestamp'))
			->getMock();
		$checkout->expects($this->any())
			->method('getEb2cFraudConnection')
			->will($this->returnValue('close'));
		$checkout->expects($this->any())
			->method('getEb2cFraudCookies')
			->will($this->returnValue(array(
				'cookie1' => 'dacookies',
				'cookie2' => 'dacookiespartdeux'
			)));
		$checkout->expects($this->any())
			->method('getEb2cFraudTimestamp')
			->will($this->returnValue('2014-01-01T10:05:01'));
		$checkout->expects($this->any())
			->method('getEb2cFraudSessionInfo')
			->will($this->returnValue(array('this is session data')));
		$this->replaceByMock('singleton', 'checkout/session', $checkout);

		$create = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_buildSessionInfo', '_getOrderSource', '_buildCustomAttributesByLevel'))
			->getMock();
		$create->expects($this->any())
			->method('_buildSessionInfo')
			->with($this->identicalTo(array('this is session data')), $this->identicalTo($element))
			->will($this->returnCallback(function($a, $node) use ($create) {
					$node->appendChild($node->ownerDocument->createElement('SessionInfo', $a[0]));
					return $create;
				}));
		$create->expects($this->any())
			->method('_buildCustomAttributesByLevel')
			->with(
				$this->identicalTo(EbayEnterprise_Eb2cOrder_Model_Create::CONTEXT_LEVEL),
				$this->identicalTo($element),
				$this->identicalTo($order)
			)
			->will($this->returnSelf());
		$create->expects($this->any())
			->method('_getOrderSource')
			->will($this->returnValue('this is the order_source'));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($create, '_o', $order);
		EcomDev_Utils_Reflection::invokeRestrictedMethod($create, '_buildContext', array($element));
		$this->assertXmlStringEqualsXmlString($expect->C14N(), $doc->C14N());
	}

	/**
	 * The context element should be built up from data in the order's quote.
	 */
	public function testBuildSessionInfo()
	{
		$create = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();
		$order = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();

		$helperMock = $this->getHelperMock('eb2corder/data', array('getConfigModel'));
		$helperMock->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'apiXmlNs' => 'http://namespace/foo',
			))));
		$this->replaceByMock('helper', 'eb2corder', $helperMock);

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($create, '_o', $order);

		$expect = Mage::helper('eb2ccore')->getNewDomDocument();
		$expect->preserveWhiteSpace = false;
		$expect->formatOutput = true;
		$expect->loadXML('
			<root xmlns="http://namespace/foo">
				<SessionInfo>
					<TimeSpentOnSite><![CDATA[1:00:00]]></TimeSpentOnSite>
					<LastLogin><![CDATA[2014-01-01T09:05:01]]></LastLogin>
					<UserPassword><![CDATA[password]]></UserPassword>
					<TimeOnFile><![CDATA[milliseconds]]></TimeOnFile>
				</SessionInfo>
			</root>
		');
		$sessionInfoData = array(
			'TimeSpentOnSite' => '1:00:00',
			'LastLogin' => '2014-01-01T09:05:01',
			'UserPassword' => 'password',
			'TimeOnFile' => 'milliseconds',
			'RTCTransactionResponseCode' => '',
			'RTCReasonCodes' => '',
		);

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root xmlns="http://namespace/foo"/>');
		$doc->formatOutput = true;
		EcomDev_Utils_Reflection::invokeRestrictedMethod($create, '_buildSessionInfo', array($sessionInfoData, $doc->documentElement));
		$this->assertSame($expect->C14N(), $doc->C14N());
	}

	/**
	 * ensure the hostname and charset nodes do not have empty values
	 */
	public function testBuildBrowserDataMissingValues()
	{
		$order = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();
		$create = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_buildSessionInfo'))
			->getMock();
		$checkout = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array('getEb2cFraudCookies', 'getEb2cFraudConnection', 'getEb2cFraudSessionInfo', 'getEb2cFraudTimestamp'))
			->getMock();

		$checkout->expects($this->any())
			->method('getEb2cFraudSessionInfo')
			->will($this->returnValue(array()));

		$this->replaceByMock('singleton', 'checkout/session', $checkout);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($create, '_o', $order);

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root/>');
		EcomDev_Utils_Reflection::invokeRestrictedMethod($create, '_buildBrowserData', array($doc->documentElement));
		$xml = $doc->saveXML();

		$this->assertTag(array('tag' => 'HostName'), $xml, '', false);
		$this->assertTag(array('tag' => 'CharSet'), $xml, '', false);
		$this->assertNotTag(array('tag' => 'Cookies'), $xml, '', false);
	}

	public function provideForTestXsdStringLength()
	{
		return array(
			array('', 10, 'thedefault', 'thedefault'),
			array('abc', 1, 'thedefault', 'a'),
			array('abc', 0, 'thedefault', 'abc'),
		);
	}
	/**
	 * truncate a $str if it is longer than $maxLength.
	 * if $str evaluates to false, return $default
	 * @dataProvider provideForTestXsdStringLength
	 */
	public function testRestrictTextLength($input, $length, $default, $expected)
	{
		$create = Mage::getModel('eb2corder/create');
		$actual = EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$create, '_restrictText', array($input, $length, $default)
		);

		$this->assertSame($expected, $actual->nodeValue);
	}
	public function provideForTestAddElementIfNotEmpty()
	{
		return array(
			array('', 10, true),
			array('abc', null, false),
		);
	}
	/**
	 * add element only if the value is not empty
	 * @dataProvider provideForTestAddElementIfNotEmpty
	 */
	public function testAddElementIfNotEmpty($input, $length, $expected)
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root/>');
		$create = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();

		$args = array('SomeNode', $input, $doc->documentElement, $length);
		$this->assertSame(
			$create,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($create, '_addElementIfNotEmpty', $args)
		);
		$xpath = new DOMXPath($doc);
		$this->assertSame($expected, is_null($xpath->query('//SomeNode')->item(0)));
	}
	/**
	 * Test EbayEnterprise_Eb2cOrder_Model_Create::retryOrderCreate method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cOrder_Model_Create::retryOrderCreate
	 *                and expect the method EbayEnterprise_Eb2cOrder_Model_Create::_getNewOrders method to be called
	 *                given a Mage_Sales_Model_Resource_Order_Collection object, then the method
	 *                Mage_Core_Model_Date::date is expected to be called 2 time given a string date format and expect
	 *                to return a know date string value, the the Mage_Sales_Model_Resource_Order_Collection is expected
	 *                to be looped through which will return Mage_Sales_Model_Order object each loop iteration, which
	 *                will be assigned to the class property EbayEnterprise_Eb2cOrder_Model_Create::_o, then expect the
	 *                invocation of the method EbayEnterprise_Eb2cOrder_Model_Create::_loadRequest given the return value
	 *                from calling the method Mage_Sales_Model_Order::getEb2cOrderCreateRequest, then the method
	 *                EbayEnterprise_Eb2cOrder_Model_Create::sendRequest will be called, then the class properties
	 *                EbayEnterprise_Eb2cOrder_Model_Create::_o and _domRequest as set to a know state of null
	 */
	public function testRetryOrderCreate()
	{
		$dateTime = '04/10/2014 06:32:56';
		$orderCreateRequest = '<OrderCreateRequest/>';
		$order = Mage::getModel(
			'sales/order', array('eb2c_order_create_request' => $orderCreateRequest)
		);
		$invalidOrder = Mage::getModel(
			'sales/order', array('eb2c_order_create_request' => null)
		);
		$collection = $this->getResourceModelMock(
			'sales/order_collection',
			// mock out methods that will cause DB interactions that we don't want here
			array('save', 'load')
		);
		$collection->addItem($order);
		$collection->addItem($invalidOrder);

		// Ensure the collection is saved so any changes to orders based upon the
		// request retries get saved
		$collection->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$collection->expects($this->any())
			->method('load')
			->will($this->returnSelf());

		$logHelperMock = $this->getHelperMockBuilder('ebayenterprise_magelog/data')
			->disableOriginalConstructor()
			->setMethods(array('logDebug', 'logWarn'))
			->getMock();
		// Test that logWarn gets called once, because my invalidOrder has no XML data:
		$logHelperMock->expects($this->once())
			->method('logWarn')
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'ebayenterprise_magelog', $logHelperMock);

		$dateMock = $this->getModelMockBuilder('core/date')
			->disableOriginalConstructor()
			->setMethods(array('date'))
			->getMock();
		$dateMock->expects($this->exactly(2))
			->method('date')
			->with($this->identicalTo('m/d/Y H:i:s'))
			->will($this->returnValue($dateTime));
		$this->replaceByMock('model', 'core/date', $dateMock);

		$createMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_loadRequest', 'sendRequest', '_getNewOrders'))
			->getMock();
		$createMock->expects($this->once())
			->method('_loadRequest')
			->with($this->identicalTo($orderCreateRequest))
			->will($this->returnSelf());
		$createMock->expects($this->once())
			->method('sendRequest')
			->will($this->returnSelf());
		$createMock->expects($this->once())
			->method('_getNewOrders')
			->will($this->returnValue($collection));

		$createMock->retryOrderCreate();
	}
	/**
	 * Test EbayEnterprise_Eb2cOrder_Model_Create::_loadRequest method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cOrder_Model_Create::_loadRequest given a string
	 *                of OrderCreateRequest xml in which expect the method EbayEnterprise_Eb2cCore_Helper_Data::getNewDomDocument
	 *                to be invoked a return a mocked EbayEnterprise_Dom_Document object in which the method
	 *                EbayEnterprise_Dom_Document::loadXML will be called given a know xml string
	 */
	public function testLoadRequest()
	{
		$xml = '<OrderCreateRequst/>';

		$docMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('loadXML'))
			->getMock();
		$docMock->expects($this->once())
			->method('loadXML')
			->with($this->identicalTo($xml))
			->will($this->returnValue(true));

		$helperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($docMock));
		$this->replaceByMock('helper', 'eb2ccore', $helperMock);

		$createMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($createMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$createMock, '_loadRequest', array($xml)
		));
	}
	/**
	 * Test that EbayEnterprise_Eb2cOrder_Model_Create::_buildCustomAttributesByLevel
	 * will return itself and that the pass EbayEnterprise_Dom_Element will contain
	 * the 'CustomAttributes'  node
	 */
	public function testBuildCustomAttributesByLevel()
	{
		$result = '<_><CustomAttributes><Attribute><Key>increment_id</Key><Value>00003884</Value></Attribute><Attribute><Key>grand_total</Key><Value>281.77</Value></Attribute></CustomAttributes></_>';
		$level = EbayEnterprise_Eb2cOrder_Model_Create::ORDER_LEVEL;
		$data = array(
			'increment_id' => '00003884',
			'grand_total' => '281.77'
		);

		$xml = '<_></_>';
		$doc = new EbayEnterprise_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML($xml);

		$order = Mage::getModel('sales/order');
		$customAttributeOrder = $this->getModelMock('eb2corder/custom_attribute_order', array(
			'extractData'
		));
		$customAttributeOrder->expects($this->once())
			->method('extractData')
			->with($this->identicalTo($order))
			->will($this->returnValue($data));
		$this->replaceByMock('model', 'eb2corder/custom_attribute_order', $customAttributeOrder);

		$create = Mage::getModel('eb2corder/create');

		$this->assertSame($create, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$create, '_buildCustomAttributesByLevel', array($level, $doc->documentElement, $order)
		));

		$this->assertSame($result, $doc->C14N());
	}
	/**
	 * Test that EbayEnterprise_Eb2cOrder_Model_Create::_buildDiscount
	 * will return itself and that the pass EbayEnterprise_Dom_Element will contain
	 * the 'PromotionalDiscounts/Discount' node and child nodes
	 * @loadExpectation
	 */
	public function testBuildDiscount()
	{
		$result = $this->expected('result')->getDiscountNode();
		$couponCode = 'Buy1Ge1Free';
		$discountAmount = 2.040;
		$baseDiscountAmount = 2.040;
		$qtyOrdered = 2;
		$storeId = 'CPG';
		$appliedRuleIds = '5';
		$storeId = 8;
		$storeLabel = null;
		$ruleDescription = 'This is sale rule label';
		$ruleSimpleAction = 'by_percent';
		$type = EbayEnterprise_Eb2cTax_Model_Response_Quote::MERCHANDISE_PROMOTION;
		$taxCollection = Mage::getResourceModel('eb2ctax/response_quote_collection');
		$taxCode = '77'; // A stub value

		$xml = '<_></_>';
		$doc = new EbayEnterprise_Dom_Document('1.0', 'UTF-8');
		$doc->loadXML($xml);

		$fragment = $doc->createDocumentFragment();
		$fragment->appendChild($doc->createElement('TaxData'));

		$order = Mage::getModel('sales/order', array('coupon_code' => $couponCode));
		$item = Mage::getModel('sales/order_item', array(
			'discount_amount' => $discountAmount,
			'qty_ordered' => $qtyOrdered,
			'base_discount_amount' => $baseDiscountAmount,
			'applied_rule_ids' => $appliedRuleIds,
			'store_id' => $storeId,
		));

		$this->replaceCoreConfigRegistry(array(
			'storeId' => $storeId
		));

		$rule = $this->getModelMock('salesrule/rule', array('load', 'getStoreLabel'));
		$rule->addData(array(
			'description' => $ruleDescription,
			'simple_action' => $ruleSimpleAction
		));
		$rule->expects($this->any())
			->method('load')
			->with($this->identicalTo($appliedRuleIds))
			->will($this->returnSelf());
		// I'm mocking this method because it is a concrete public method in
		// Mage_SalesRule_Model_Rule::getStoreLabel
		$rule->expects($this->any())
			->method('getStoreLabel')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($storeLabel));
		$this->replaceByMock('model', 'salesrule/rule', $rule);

		$create = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_buildTaxDataNodes', 'getItemTaxQuotes', '_getProductTaxCode'))
			->getMock();
		$create->expects($this->once())
			->method('_buildTaxDataNodes')
			->with($this->identicalTo($taxCollection), $this->identicalTo($taxCode))
			->will($this->returnValue($fragment));
		$create->expects($this->once())
			->method('getItemTaxQuotes')
			->with(
				$this->identicalTo($item),
				$this->identicalTo($type)
			)
			->will($this->returnValue($taxCollection));
		$create->expects($this->once())
			->method('_getProductTaxCode')
			->with(
				$this->identicalTo($item)
			)
			->will($this->returnValue($taxCode));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($create, '_o', $order);

		$this->assertSame($create, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$create, '_buildDiscount', array($doc->documentElement, $item, $discountAmount, $type)
		));

		$this->assertSame($result, $doc->C14N());
	}
	/**
	 * Test that the method EbayEnterprise_Eb2cOrder_Model_Create::_buildGift
	 * when invoked by this test will build a gift node
	 */
	public function testBuildGift()
	{
		$level = 'item';
		$wrappingId = 5;
		$sku = '53-GWTST0001';
		$gwPrice = 10.95;
		$basePrice = 9.45;
		$giftMessageId = 0;
		$taxType = 'SALES';
		$taxability = 'TAXABLE';
		$situs = 'ADMINISTRATIVE_ORIGIN';
		$jurisdiction = 'PENNSYLVANIA';
		$jurisdictionLevel = 'STATE';
		$jurisdictionId = '31152';
		$imposition = 'Sales and Use Tax';
		$impositionType = 'General Sales and Use Tax';
		$effectiveRate = 0.06;
		$taxableAmount = 10.95;
		$calculatedTax = 0.66;

		$item = Mage::getModel('sales/order_item', array(
			'gw_price' => $gwPrice,
			'gift_message_id' => $giftMessageId
		));

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<root></root>');
		$docElement = $doc->documentElement;

		$taxData = array(
			'tax_type' => $taxType,
			'taxability' => $taxability,
			'situs' => $situs,
			'jurisdiction' => $jurisdiction,
			'jurisdiction_level' => $jurisdictionLevel,
			'jurisdiction_id' => $jurisdictionId,
			'imposition' => $imposition,
			'imposition_type' => $impositionType,
			'effective_rate' => $effectiveRate,
			'taxable_amount' => $taxableAmount,
			'calculated_tax' => $calculatedTax
		);

		$rq = Mage::getModel('eb2ctax/response_quote', $taxData);
		$quoteCollection = Mage::getResourceModel('eb2ctax/response_quote_collection');
		$quoteCollection->addItem($rq);

		$taxQuote = $this->getModelMock('eb2ctax/response_quote', array('getCollection'));
		$taxQuote->expects($this->any())
			->method('getCollection')
			->will($this->returnValue($quoteCollection));
		$this->replaceByMock('model', 'eb2ctax/response_quote', $taxQuote);

		// Related gift message
		$giftWrapping = $this->getModelMock(
			'enterprise_giftwrapping/wrapping',
			array('load'),
			false,
			array(array(
				'wrapping_id' => $wrappingId,
				'eb2c_sku' => $sku,
				'base_price' => $basePrice
			))
		);
		$giftWrapping->expects($this->once())
			->method('load')
			->with($this->identicalTo($wrappingId))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'enterprise_giftwrapping/wrapping', $giftWrapping);

		$create = Mage::getModel('eb2corder/create');

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($create, '_domRequest', $doc);

		// Building 'Gift' node
		$this->assertSame($create, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$create, '_buildGift', array($docElement, $item, $wrappingId, $level)
		));

		$expectedXml = '<root><Gift><ItemId>' . $sku . '</ItemId><Pricing><Amount>' . $gwPrice .
			'</Amount><TaxData xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><TaxClass></TaxClass><Taxes><Tax taxType="' .
			$taxType . '" taxability="' . $taxability . '"><Situs>' . $situs .
			'</Situs><Jurisdiction jurisdictionId="' . $jurisdictionId . '" jurisdictionLevel="' . $jurisdictionLevel .
			'">' . $jurisdiction . '</Jurisdiction><Imposition impositionType="' . $impositionType . '">' . $imposition .
			'</Imposition><EffectiveRate>' . $effectiveRate . '</EffectiveRate><TaxableAmount>' . $taxableAmount .
			'</TaxableAmount><CalculatedTax>' . $calculatedTax . '</CalculatedTax></Tax></Taxes></TaxData><UnitPrice>' . $basePrice .
			'</UnitPrice></Pricing></Gift></root>';
		$this->assertSame($expectedXml, $doc->C14N());
	}
	/**
	 * Test 'EbayEnterprise_Eb2cOrder_Model_Create::_buildEstimatedDeliveryDate' method when invoke will
	 * add a 'MessageType' node with the value 'DeliveryDate'.
	 * @param string $xml
	 * @param array $itemData
	 * @param string $expected
	 * @dataProvider dataProvider
	 */
	public function testMessageTypeNodeValue($xml, array $itemData, $expected)
	{
		$helper = Mage::helper('eb2ccore');
		$doc = $helper->getNewDomDocument();
		$doc->loadXML($xml);
		$xpath = $helper->getNewDomXPath($doc);
		$orderItem = Mage::getModel('sales/order_item', $itemData);
		$create = Mage::getModel('eb2corder/create');
		EcomDev_Utils_Reflection::invokeRestrictedMethod($create, '_buildEstimatedDeliveryDate', array($doc->documentElement, $orderItem));
		$this->assertSame($expected, $xpath->evaluate('string(//EstimatedDeliveryDate/MessageType)'));
	}
	/**
	 * Test 'EbayEnterprise_Eb2cOrder_Model_Create::_buildPayPalPayerInfo' method by passing in a DOMElement
	 * context node parameter, and then injects a known  'sales/order' object in the ‘eb2corder/create’ class property,
	 * then expects it to build the `<PayPalPayerInfo>` node under the `OrderCreateRequest/Context` parent nodes.
	 * This will occur when the passed in order object has a PayPal payment method and the ROM payment extension is enabled.
	 * @param string $xml
	 * @param array $paymentData
	 * @param bool $isPaymentEnabled
	 * @param string $expected
	 * @dataProvider dataProvider
	 */
	public function testBuildPayPalPayerInfo($xml, array $paymentData, $isPaymentEnabled, $expected)
	{
		$order = Mage::getModel('sales/order');
		$order->addPayment(Mage::getModel('sales/order_payment', $paymentData));

		$helper = $this->getHelperMockBuilder('eb2cpayment/data')
			/** @see EbayEnterprise_Eb2cPayment_Helper_Data::__construct **/
			// Mocking the ‘eb2ccore/config_registry’ has inadvertently caused errors in the
			// ‘eb2cpayment/data:: __construct’ method; however, disabling the constructor seems
			// the best solution since it isn’t needed for this test.
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel'))
			->getMock();
		$helper->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array('isPaymentEnabled' => $isPaymentEnabled))));
		$this->replaceByMock('helper', 'eb2cpayment', $helper);

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML($xml);

		$create = Mage::getModel('eb2corder/create');
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($create, '_o', $order);
		EcomDev_Utils_Reflection::invokeRestrictedMethod($create, '_buildPayPalPayerInfo', array($doc->documentElement, $order));

		$this->assertXmlStringEqualsXmlString($expected, $doc->C14N());
	}
}
