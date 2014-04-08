<?php
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
	 * @test
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
	 * Test _getAttributeValueByProductId method with the following expectations
	 * Expectation 1: this test is expected to mockout the method Mage_Catalog_Model_Resource_Product::getAttributeRawValue
	 *                to be called once with product id (9) as first parameter, the attribute which is being passed the
	 *                _getAttributeValueByProductId method in this test as 'tax_code' and also the value being returned
	 *                from the mocked method Mage_Core_Helper_Data::getStoreId which is expected to return the value 1
	 * Expectation 2: the method Mage_Core_Helper_Data::getStoreId is expected to be called once to return the value 1
	 * @mock Mage_Catalog_Model_Resource_Product::getAttributeRawValue
	 * @mock Mage_Core_Helper_Data::getStoreId
	 */
	public function testGetAttributeValueByProductId()
	{
		$productResourceModelMock = $this->getResourceModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getAttributeRawValue'))
			->getMock();
		$productResourceModelMock->expects($this->once())
			->method('getAttributeRawValue')
			->with($this->equalTo(9), $this->equalTo('tax_code'), $this->equalTo(1))
			->will($this->returnValue('12334423'));
		$this->replaceByMock('resource_model', 'catalog/product', $productResourceModelMock);

		$coreHelperMock = $this->getHelperMockBuilder('core/data')
			->disableOriginalConstructor()
			->setMethods(array('getStoreId'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getStoreId')
			->will($this->returnValue(1));
		$this->replaceByMock('helper', 'core', $coreHelperMock);

		$create = Mage::getModel('eb2corder/create');
		$this->assertSame('12334423', $this->_reflectMethod($create, '_getAttributeValueByProductId')->invoke($create, 'tax_code', 9));
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
	 * Expectation 5: the mocked method EbayEnterprise_Eb2cOrder_Model_Create::_getAttributeValueByProductId is expected to get the attribute 'tax_code'
	 *                as its first parameter and the product id which is added in the real order item object to be the value 9, this method is expected
	 *                to be called once and will return the value '1234533' which is then get asserted within the test as the value in the TaxClass node
	 * @mock EbayEnterprise_Eb2cOrder_Model_Create::_getAttributeValueByProductId
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
			->setMethods(array('_getAttributeValueByProductId'))
			->getMock();
		$create->expects($this->once())
			->method('_getAttributeValueByProductId')
			->with($this->equalTo('tax_code'), $this->equalTo(9))
			->will($this->returnValue('1234533'));

		$request = $this->_reflectProperty($create, '_domRequest');
		$request->setValue($create, Mage::helper('eb2ccore')->getNewDomDocument());
		$taxFragment = $this->_reflectMethod($create, '_buildTaxDataNodes')->invoke($create, $taxQuotesCollection, Mage::getModel('sales/order_item')->addData(array(
			'product_id' => 9,
		)));
		// probe the tax fragment a bit to hopefully ensure the nodes are all populated right
		$this->assertSame(1, $taxFragment->childNodes->length);
		$this->assertSame('TaxData', $taxFragment->firstChild->nodeName);
		$this->assertSame('TaxClass', $taxFragment->firstChild->firstChild->nodeName);
		$this->assertSame('1234533', $taxFragment->firstChild->firstChild->nodeValue);
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
	 * Test that we pull the TaxClass for shipping from config.
	 * @test
	 */
	public function testBuildTaxDataNodesShipping()
	{
		$this->replaceCoreConfigRegistry(
			array(
				'shippingTaxClass' => 'UNIT_TEST_CLASS',
			)
		);

		$taxQuotes[] = Mage::getModel(
			'eb2ctax/response_quote',
			array(
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
			)
		);

		$taxQuotesCollection = $this->getModelMockBuilder('eb2ctax/resource_response_quote_collection')
			->disableOriginalConstructor()
			->setMethods(array('getIterator', 'count'))
			->getMock();
		$taxQuotesCollection->expects($this->any())
			->method('getIterator')
			->will($this->returnValue(new ArrayIterator($taxQuotes)));
		$taxQuotesCollection->expects($this->any())
			->method('count')
			->will($this->returnValue(1));

		$create = Mage::getModel('eb2corder/create');
		$request = $this->_reflectProperty($create, '_domRequest');
		$request->setValue($create, Mage::helper('eb2ccore')->getNewDomDocument());
		$taxFragment = $this
			->_reflectMethod($create, '_buildTaxDataNodes')
			->invoke($create, $taxQuotesCollection, Mage::getModel('sales/order_item'), EbayEnterprise_Eb2cTax_Model_Response_Quote::SHIPPING);

		$this->assertSame('UNIT_TEST_CLASS', $taxFragment->firstChild->firstChild->nodeValue);
	}
	/**
	 * When the observer triggers, the create model should build a new request
	 * and send it.
	 * @test
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
	 * @test
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
	 * Data provider of sample XML responses and the status reported in each
	 * @return array Argument arrays of xml string and status code string
	 */
	public function providerServiceResponses()
	{
		return array(
			array(self::SAMPLE_SUCCESS_XML, 'success'),
			array(self::SAMPLE_FAILED_XML, 'failed'),
			array('', 'failed'), // when the xml is empty, there will be no status but the 'failed' here is used as a signal to the test
		);
	}
	/**
	 * Processing of the responses from the Eb2c order create service.
	 * When successful, should set order status to processing
	 * When failed, should set order status to new
	 * @param string $response [description]
	 * @param string $responseStatus [description]
	 * @test
	 * @dataProvider providerServiceResponses
	 */
	public function testResponseProcessing($response, $responseStatus)
	{
		$order = $this->getModelMock('sales/order', array('save'));
		$order->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$create = $this->getModelMock('eb2corder/create', array('_extractResponseState'));
		$create
			->expects($this->any())
			->method('_extractResponseState')
			->will($this->returnValue($this->expected($responseStatus)->getOrderState()));
		$orderProp = $this->_reflectProperty($create, '_o');
		$orderProp->setValue($create, $order);
		$processMethod = $this->_reflectMethod($create, '_processResponse');
		$processMethod->invoke($create, $response);
		$this->assertSame(
			$this->expected($responseStatus)->getOrderState(),
			$order->getState()
		);
	}
	/**
	 * Building the XML nodes for a given order item
	 * @todo This method should really be broken down into some smaller chunks to make this test less complicated
	 * @param array $itemData Order item object data
	 * @param array $orderData Order object data
	 * @param boolean $merchTax Should this item have merchandise taxes
	 * @param boolean $shippingTax Should this item have shipping taxes
	 * @param boolean $dutyTax Should this item have duty taxes
	 * @test
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
		$create = $this->getModelMock(
			'eb2corder/create',
			array('_buildTaxDataNodes', 'getItemTaxQuotes', '_buildDuty', '_getItemShippingAmount', '_getShippingChargeType', '_buildEstimatedDeliveryDate')
		);
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
	 * @test
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
			'EstimatedDeliveryDate/MessageType[.="NONE"]',
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
	 * @test
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
		$this->_reflectProperty($createModelMock, '_config')->setValue($createModelMock, (object) array(
			'apiCreateDomRootNodeName' => 'OrderCreateRequest',
			'apiXmlNs' => 'http://api.gsicommerce.com/schema/checkout/1.0',
			'apiOrderType' => 'SALES'
		));
		$this->assertInstanceOf(
			'EbayEnterprise_Dom_Element',
			$this->_reflectMethod($createModelMock, '_buildOrderCreateRequest')->invoke($createModelMock)
		);
	}
	/**
	 * Test _processResponse method
	 * @test
	 */
	public function testProcessResponse()
	{
		$orderModelMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getState', 'setState', 'save', 'getIncrementId'))
			->getMock();
		$orderModelMock->expects($this->exactly(2))
			->method('getState')
			->will($this->returnValue(Mage_Sales_Model_Order::STATE_NEW));
		$orderModelMock->expects($this->at(1))
			->method('setState')
			->with($this->equalTo(Mage_Sales_Model_Order::STATE_PROCESSING))
			->will($this->returnSelf());
		$orderModelMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		$orderModelMock->expects($this->exactly(2))
			->method('getIncrementId')
			->will($this->returnValue('0005400000000001'));
		$createModelMock = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_extractResponseState'))
			->getMock();
		$successResponse = '<?xml version="1.0" encoding="UTF-8"?><OrderCreateResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><ResponseStatus>Success</ResponseStatus></OrderCreateResponse>';
		$failResponse = '<?xml version="1.0" encoding="UTF-8"?><OrderCreateResponse xmlns="http://api.gsicommerce.com/schema/checkout/1.0"><ResponseStatus>Failure</ResponseStatus></OrderCreateResponse>';
		$createModelMock->expects($this->at(0))
			->method('_extractResponseState')
			->with($this->equalTo($successResponse))
			->will($this->returnValue(Mage_Sales_Model_Order::STATE_PROCESSING));
		$createModelMock->expects($this->at(1))
			->method('_extractResponseState')
			->with($this->equalTo($failResponse))
			->will($this->returnValue(Mage_Sales_Model_Order::STATE_NEW));
		$this->_reflectProperty($createModelMock, '_o')->setValue($createModelMock, $orderModelMock);
		$testData = array(
			array(
				'expect' => 'EbayEnterprise_Eb2cOrder_Model_Create',
				'response' => $successResponse,
			),
			array(
				'expect' => 'EbayEnterprise_Eb2cOrder_Model_Create',
				'response' => $failResponse,
			)
		);
		foreach ($testData as $data) {
			$this->assertInstanceOf($data['expect'], $this->_reflectMethod($createModelMock, '_processResponse')->invoke($createModelMock, $data['response']));
		}
	}
	/**
	 * If the response XML exists and has a ResponseStatus node with a value of 'success' in any capitalization,
	 * we should see a value of STATE_PROCESSING. Otherwise, we should see STATE_NEW.
	 *
	 * @test
	 */
	public function testExtractResponseState()
	{
		$create = Mage::getModel('eb2corder/create');
		$crRefl = new ReflectionClass($create);
		$extRespSt = $crRefl->getMethod('_extractResponseState');
		$extRespSt->setAccessible(true);
		$this->assertSame(Mage_Sales_Model_Order::STATE_NEW, $extRespSt->invoke($create, ''));
		$this->assertSame(Mage_Sales_Model_Order::STATE_NEW, $extRespSt->invoke($create, '<fail/>'));
		$this->assertSame(Mage_Sales_Model_Order::STATE_NEW, $extRespSt->invoke(
			$create,
			'<_><ResponseStatus>nobodyhome!</ResponseStatus></_>')
		);
		$this->assertSame(
			Mage_Sales_Model_Order::STATE_PROCESSING,
			$extRespSt->invoke($create, '<_><ResponseStatus>sUcCeSs</ResponseStatus></_>')
		);
	}
	/**
	 * Test _buildItems method
	 * @test
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
	 * @test
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
	 * Test buildRequest method
	 * @test
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
			->setMethods(array('getAllPayments', 'getGrandTotal'))
			->getMock();
		$order->expects($this->once())
			->method('getAllPayments')
			->will($this->returnValue(array($payment)));
		$order->expects($this->once())
			->method('getGrandTotal')
			->will($this->returnValue(50.00));

		$create = Mage::getModel('eb2corder/create');
		$this->_reflectProperty($create, '_o')->setValue($create, $order);
		$this->_reflectProperty($create, '_ebcPaymentMethodMap')->setValue($create, array('Paypal_express' => 'PayPal'));
		$this->assertSame($create, $this->_reflectMethod($create, '_buildPayments')->invoke($create, $doc->documentElement));

		$this->assertSame(sprintf($this->expected('paypal')->getPaymentNode(), "\n"), trim($doc->saveXML()));
	}

	/**
	 * @test
	 */
	public function testGetOrderGiftCardPan()
	{
		$expectedPanToken = 'abc123';
		$order = $this->getModelMock('sales/order', array('getGiftCards'));
		$order
			->expects($this->once())
			->method('getGiftCards')
			->will($this->returnValue(serialize(array(array('panToken' => $expectedPanToken)))));
		$this->assertSame($expectedPanToken, EbayEnterprise_Eb2cOrder_Model_Create::getOrderGiftCardPan($order));
	}

	/**
	 * test EbayEnterprise_Eb2cOrder_Model_Create::getOrderGiftCardPan for the following expectation
	 * Expectation 1: this test will invoke the method EbayEnterprise_Eb2cOrder_Model_Create::getOrderGiftCardPan given
	 *                a mock order object of class Mage_Sales_Model_Order in which the method Mage_Sales_Model_Order::getGiftCards
	 *                will be invoked once and return an array of array of giftcard data in the order object it will then
	 *                loop through the array of array of giftcard data and return the pan key value
	 * @mock Mage_Sales_Model_Order::getGiftCards
	 */
	public function testGetOrderGiftCardPanWhenKeyPanIsInGiftcardData()
	{
		$data = array(array('pan' => '000000000003939388322'));
		$giftcards = serialize($data);

		$orderMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getGiftCards'))
			->getMock();
		$orderMock->expects($this->once())
			->method('getGiftCards')
			->will($this->returnValue($giftcards));

		$this->assertSame($data[0]['pan'], EbayEnterprise_Eb2cOrder_Model_Create::getOrderGiftCardPan($orderMock));
	}

	/**
	 * @see self::testGetOrderGiftCardPanWhenKeyPanIsInGiftcardData except we now testing when the return value of
	 *      Mage_Sales_Model_Order::getGiftCards unserialized into an empty array
	 * @mock Mage_Sales_Model_Order::getGiftCards
	 */
	public function testGetOrderGiftCardPanWNoGiftcardData()
	{
		$giftcards = serialize(array(array()));

		$orderMock = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('getGiftCards'))
			->getMock();
		$orderMock->expects($this->once())
			->method('getGiftCards')
			->will($this->returnValue($giftcards));

		$this->assertSame('', EbayEnterprise_Eb2cOrder_Model_Create::getOrderGiftCardPan($orderMock));
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
	 * @test
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
	 * @test
	 */
	public function testBuildContext()
	{
		$order = $this->getModelMockBuilder('sales/order')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();
		$create = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('_buildSessionInfo', '_getOrderSource'))
			->getMock();
		$checkout = $this->getModelMockBuilder('checkout/session')
			->disableOriginalConstructor()
			->setMethods(array('getEb2cFraudCookies', 'getEb2cFraudConnection', 'getEb2cFraudSessionInfo', 'getEb2cFraudTimestamp'))
			->getMock();

		$this->replaceByMock('singleton', 'checkout/session', $checkout);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($create, '_o', $order);

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

		$order->setData(array(
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

		$create->expects($this->any())
			->method('_buildSessionInfo')
			->with($this->identicalTo(array('this is session data')),  $this->isInstanceOf('DOMNode'))
			->will($this->returnCallback(
				function($a, $node) use ($create) {
					$node->appendChild($node->ownerDocument->createElement('SessionInfo', $a[0]));
					return $create;
				}
			));
		$create->expects($this->any())
			->method('_getOrderSource')
			->will($this->returnValue('this is the order_source'));

		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->formatOutput = true;
		$doc->loadXML('<root/>');
		EcomDev_Utils_Reflection::invokeRestrictedMethod($create, '_buildContext', array($doc->documentElement));
		$this->assertSame($expect->saveXML(), $doc->saveXML());
	}

	/**
	 * The context element should be built up from data in the order's quote.
	 * @test
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

		$config = $this->buildCoreConfigRegistry(array(
			'apiXmlNs' => 'http://namespace/foo',
		));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($create, '_o', $order);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($create, '_config', $config);

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
	 * @test
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
		$this->assertContains('<HostName><![CDATA[null]]></HostName>', $xml);
		$this->assertContains('<CharSet><![CDATA[null]]></CharSet>', $xml);
		$this->assertNotContains('<Cookies>', $xml);
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
	 * @test
	 * @dataProvider provideForTestXsdStringLength
	 */
	public function testXsdStringLength($input, $length, $default, $expected)
	{
		$create = $this->getModelMockBuilder('eb2corder/create')
			->disableOriginalConstructor()
			->setMethods(array('none'))
			->getMock();
		$args = array($input, $length, $default);
		$this->assertSame(
			$expected,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($create, '_xsdString', $args)
		);
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
	 * @test
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
}
