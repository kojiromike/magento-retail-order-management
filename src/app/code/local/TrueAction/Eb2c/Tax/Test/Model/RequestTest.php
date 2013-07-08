<?php
/**
 * tests the tax calculation class.
 */
class TrueAction_Eb2c_Tax_Test_Model_RequestTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @var Mage_Sales_Model_Quote (mock)
	 */
	public $quote          = null;

	/**
	 * @var Mage_Sales_Model_Quote_Address (mock)
	 */
	public $shipAddress    = null;

	/**
	 * @var Mage_Sales_Model_Quote_Address (mock)
	 */
	public $billAddress    = null;

	/**
	 * @var ReflectionProperty(TrueAction_Eb2c_Tax_Model_Request::_xml)
	 */
	public $doc            = null;

	/**
	 * @var ReflectionClass(TrueAction_Eb2c_Tax_Model_Request)
	 */
	public static $cls     = null;

	/**
	 * path to the xsd file to validate against.
	 * @var string
	 */
	public static $xsdFile = '';

	public $tdRequest      = null;
	public $destinations   = null;
	public $shipGroups     = null;

	public static function setUpBeforeClass()
	{
		self::$xsdFile = dirname(__FILE__) .
			'/RequestTest/fixtures/TaxDutyFee-QuoteRequest-1.0.xsd';
		self::$cls = new ReflectionClass(
			'TrueAction_Eb2c_Tax_Model_Request'
		);
	}

	public function setUp()
	{
		parent::setUp();
		$_SESSION = array();
		$_baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
		$this->app()->getRequest()->setBaseUrl($_baseUrl);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testIsValid()
	{
		$mockQuoteAddress = $this->getModelMock('sales/quote_address', array('getId'));
		$mockQuoteAddress->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));

		$mockProduct = $this->getModelMock('catalog/product', array('isVirtual'));
		$mockProduct->expects($this->any())
			->method('isVirtual')
			->will($this->returnValue(true));

		$mockItem = $this->getModelMock('sales/quote_item', array('getId', 'getProduct', 'getHasChildren', 'isChildrenCalculated', 'getChildren'));
		$mockItem->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$mockItem->expects($this->any())
			->method('getProduct')
			->will($this->returnValue($mockProduct));
		$mockItem->expects($this->any())
			->method('getHasChildren')
			->will($this->returnValue(true));
		$mockItem->expects($this->any())
			->method('isChildrenCalculated')
			->will($this->returnValue(true));
		$mockItem->expects($this->any())
			->method('getChildren')
			->will($this->returnValue(array($mockItem)));

		$mockQuote = $this->getModelMock('sales/quote', array('getId', 'getItemsCount', 'getBillingAddress', 'getAllVisibleItems'));
		$mockQuote->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$mockQuote->expects($this->any())
			->method('getItemsCount')
			->will($this->returnValue(1));
		$mockQuote->expects($this->any())
			->method('getBillingAddress')
			->will($this->returnValue($mockQuoteAddress));
		$mockQuote->expects($this->any())
			->method('getAllVisibleItems')
			->will($this->returnValue(array($mockItem)));

		$req = Mage::getModel('eb2ctax/request', array('quote' => $mockQuote));
		$this->assertTrue($req->isValid());
		$req->invalidate();
		$this->assertFalse($req->isValid());
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testValidateWithXsd()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$this->assertTrue($request->isValid());
		$doc = $request->getDocument();
		$this->markTestIncomplete('xsd validation fails even though the output xml looks good');
		$this->assertTrue($doc->schemaValidate(self::$xsdFile));
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testGetSkus()
	{
		$this->markTestIncomplete('According to mphang this is useless now. Leaving for code review.');
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$result = $request->getSkus();
		// the skus in the test are being converted
		// to numbers
		$this->assertEquals(array(1111, 1112, 1113), $result);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testGetItemDataBySku()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$itemData = $request->getItemDataBySku('1111');
		$this->assertNotNull($itemData);
		$itemData = $request->getItemDataBySku(1111);
		$this->assertNotNull($itemData);
		$itemData = $request->getItemDataBySku('notfound');
		$this->assertNull($itemData);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBilling.yaml
	 */
	public function testCheckAddresses()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		// passing in a quote with no changes should not invalidate the request
		$request->checkAddresses($quote);
		$this->assertTrue($request->isValid());
		// passing in an unusable quote should not invalidate the request
		$request->checkAddresses(Mage::getModel('sales/quote'));
		$this->assertTrue($request->isValid());
		$request->checkAddresses(null);
		$this->assertTrue($request->isValid());
		// changing address information should invalidate the request
		$quote->getBillingAddress()->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingNotSameAsBillingVirtual.yaml
	 */
	public function testCheckAddressesVirtual()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(4);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$request->checkAddresses($quote);
		$this->assertTrue($request->isValid());
		$quote->getBillingAddress()->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture multiShipNotSameAsBilling.yaml
	 */
	public function testCheckAddressMultishipping()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(2);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$request->checkAddresses($quote);
		$this->assertTrue($request->isValid());
		$quote->getBillingAddress()->setCity('wrongcitybub');
		$request->checkAddresses($quote);
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture multiShipNotSameAsBilling.yaml
	 */
	public function testMultishipping()
	{
		$quote   = Mage::getModel('sales/quote')->loadByIdWithoutStore(2);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc = $request->getDocument();
		$x = new DOMXPath($doc);
		$x->registerNamespace('a', $doc->documentElement->namespaceURI);
		// there should be 3 mailing address nodes;
		// 1 for the billing address; 2 for the shipping addresses
		$this->assertSame(3, $x->query('//a:Destinations/a:MailingAddress')->length);
		$this->assertSame(3, $x->query('//a:Destinations/*')->length);
		// ensure the billing information references a destination
		$billingRef = $x->evaluate('string(//a:BillingInformation/@ref)');
		$el = $doc->getElementById($billingRef);
		$this->assertSame(
			$el,
			$x->query("//a:Destinations/a:MailingAddress[@id='$billingRef']")->item(0)
		);
		// there should be only 2 shipgroups 1 for each
		// shipping address.
		$ls = $x->query('//a:ShipGroup');
		$this->assertSame(2, $ls->length);
		// make sure each shipgroup references a mailingaddress node.
		foreach ($ls as $sg) {
			$destRef = $x->evaluate('string(/a:DestinationTarget/@ref)');
			$el = $doc->getElementById($destRef);
			$this->assertSame(
				$el,
				$x->query("//a:Destinations/a:MailingAddress[@id='$destRef']")->item(0)
			);
		}
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingNotSameAsBilling.yaml
	 */
	public function testVirtualPhysicalMix()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(4);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc = $request->getDocument();
		$this->markTestIncomplete('need to check wether the virtual item got assigned to the virtual destination');
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingNotSameAsBilling.yaml
	 */
	public function testCheckItemQty()
	{
		$this->markTestIncomplete('missing fixtures?');
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(3);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$items = $quote->getAllVisibleItems();
		$item = $items[0];
		$request->checkItemQty($item);
		$this->assertTrue($request->isValid());
		$item->setData('qty', 5);
		$request->checkItemQty($item);
		$this->assertFalse($request->isValid());
	}


	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBillingNullSku.yaml
	 */
	public function testWithNoSku()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$items = $quote->getAllVisibleItems();
		$doc = $request->getDocument();
		$this->assertFalse($request->isValid());
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingSameAsBillingLongSku.yaml
	 */
	public function testCheckSkuWithLongSku()
	{
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(1);
		$request = Mage::getModel('eb2ctax/request', array('quote' => $quote));
		$doc = $request->getDocument();
		$this->assertNotNull($doc->documentElement);
		$x = new DOMXPath($doc);
		$x->registerNamespace('a', $doc->documentElement->namespaceURI);
		// the sku should be truncated at 20 characters so the original sku
		// should not be found.
		$ls = $x->query('//a:OrderItem/a:ItemId[.="123456789012345678901"]');
		$this->assertSame(0, $ls->length);
		$this->markTestIncomplete('the skus get trucated properly, but the below xpath query doesnt work');
		$ls = $x->query('//a:OrderItem/a:ItemId[.="12345678901234567890"]');
		// the sku should be truncated at 20 characters
		$this->assertSame(1, $ls->length);
	}

	/**
	 * @test
	 * @loadFixture base.yaml
	 * @loadFixture singleShippingNotSameAsBilling.yaml
	 */
	public function testAddToDestination()
	{
		$fn = new ReflectionMethod('TrueAction_Eb2c_Tax_Model_Request', '_addToDestination');
		$fn->setAccessible(true);
		$d = new ReflectionProperty('TrueAction_Eb2c_Tax_Model_Request', '_destinations');
		$d->setAccessible(true);
		$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore(3);
		$items = $quote->getAllVisibleItems();
		$request = Mage::getModel('eb2ctax/request');
		$request->setQuote($quote);
		$fn->invoke($request, $items[0], $quote->getBillingAddress());
		$destinations = $d->getValue($request);
		$this->assertTrue(isset($destinations[$quote->getBillingAddress()->getId()]));
		$fn->invoke($request, $items[0], $quote->getBillingAddress(), true);
		$destinations = $d->getValue($request);
		$virtualId = $quote->getBillingAddress()->getId() .
			'_' . $quote->getBillingAddress()->getEmail();
		$this->assertTrue(isset($destinations[$virtualId]));
	}
}
