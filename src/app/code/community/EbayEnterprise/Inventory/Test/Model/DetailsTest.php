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

use eBayEnterprise\RetailOrderManagement\Api\HttpApi;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation;
use eBayEnterprise\RetailOrderManagement\Payload\PayloadFactory;

class EbayEnterprise_Inventory_Test_Model_DetailsTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const HTTP_API =
        '\eBayEnterprise\RetailOrderManagement\Api\HttpApi';
    const INVENTORY_DETAIL_REPLY_INTERFACE =
        '\eBayEnterprise\RetailOrderManagement\Payload\Inventory\IInventoryDetailsReply';
    const INVENTORY_DETAIL_REQUEST_INTERFACE =
        '\eBayEnterprise\RetailOrderManagement\Payload\Inventory\IInventoryDetailsRequest';

    /** @var EbayEnterprise_Eb2cCore_Model_Session */
    protected $coreSession;
    /** @var EbayEnterprise_Inventory_Model_Item_Selection_Interface */
    protected $selection;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_Inventory_Helper_Details_Response */
    protected $responseHelper;
    /** @var IInventoryDetailsRequest */
    protected $request;
    /** @var IInventoryDetailsReply */
    protected $reply;

    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();
        $this->logger= $this->getHelperMockBuilder('ebayenterprise_magelog/data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logContext = $this->getHelperMockBuilder('ebayenterprise_magelog/context')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logContext->expects($this->any())
            ->method('getMetaData')
            ->will($this->returnValue([]));

        $this->request = $this->getMockForAbstractClass(self::INVENTORY_DETAIL_REQUEST_INTERFACE);
        $this->reply = $this->getMockForAbstractClass(self::INVENTORY_DETAIL_REPLY_INTERFACE);
        $this->httpApi = $this->getMockBuilder(self::HTTP_API)
            ->disableOriginalConstructor()
            ->setMethods(['send', 'getRequestBody', 'getResponseBody', 'setRequestBody'])
            ->getMock();
        $this->httpApi->expects($this->any())
            ->method('setRequestBody')
            ->with($this->isInstanceOf(self::INVENTORY_DETAIL_REQUEST_INTERFACE))
            ->will($this->returnSelf());
        $this->httpApi->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($this->request));
        $this->httpApi->expects($this->any())
            ->method('getResponseBody')
            ->will($this->returnValue($this->reply));
        $this->quote = Mage::getModel('sales/quote');
    }

    public function provideRequisiteData()
    {
        return [
            [true, true, 0, false],
            [false, true, 0, false],
            [true, false, 0, false],
            [true, false, 0, false],
            [false, false, 0, false],
            [true, false, 1, true],
            [false, true, 1, true],
            [true, true, 1, true],
        ];
    }

    /**
     * verify
     * - return true if either:
     *  - customer has a usable default address
     *  - quote has a usable shipping address
     * @param  bool
     * @param  bool
     * @param  int
     * @param  bool
     * @dataProvider provideRequisiteData
     */
    public function testCanFetchDetails($hasQuoteAddress, $hasCustomerAddress, $itemCount, $expected)
    {
        $customer = $this->getModelMockBuilder('customer/customer')
            ->disableOriginalConstructor()
            ->setMethods(['getDefaultShippingAddress'])
            ->getMock();
        $quote = $this->getModelMockBuilder('sales/quote')
            ->disableOriginalConstructor()
            ->setMethods(['getShippingAddress', 'getItemsCount', 'getCustomer'])
            ->getMock();

        $quote->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($hasQuoteAddress ? $this->stubAddress() : Mage::getModel('sales/quote_address')));

        // try the customer default shipping address if theres
        // no shipping address on the quote
        $quote->expects($this->any())
            ->method('getCustomer')
            ->will($this->returnValue($customer));
        $customer->expects($this->any())
            ->method('getDefaultShippingAddress')
            ->will($this->returnValue($hasCustomerAddress ? $this->stubAddress() : null));

        // no need to run for an empty cart
        $quote->expects($this->any())
            ->method('getItemsCount')
            ->will($this->returnValue($itemCount));
        $details = Mage::getModel('ebayenterprise_inventory/details');
        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod($details, 'canFetchDetails', [$quote]);
        $this->assertSame($expected, $result);
    }

    public function testFetch()
    {
        $quote = $this->getModelMock('sales/quote');
        $coreSession = $this->getModelMockBuilder('eb2ccore/session')
            ->disableOriginalConstructor()
            ->setMethods(['isDetailsUpdateRequired', 'resetDetailsUpdateRequired'])
            ->getMock();
        $invSession = $this->getModelMockBuilder('ebayenterprise_inventory/session')
            ->disableOriginalConstructor()
            ->setMethods(['setInventoryDetailsResult', 'getInventoryDetailsResult'])
            ->getMock();
        $result = $this->getModelMockBuilder('ebayenterprise_inventory/details_result')
            ->disableOriginalConstructor()
            ->getMock();
        $detailsModel = $this->getModelMockBuilder('ebayenterprise_inventory/details')
            ->setConstructorArgs([[
                'logger' => $this->logger,
                'log_context' => $this->logContext,
            ]])
            ->setMethods(['getInventorySession', 'getCoreSession', 'tryOperation', 'canFetchDetails'])
            ->getMock();

        $detailsModel->expects($this->any())
            ->method('getCoreSession')
            ->will($this->returnValue($coreSession));
        $detailsModel->expects($this->any())
            ->method('getInventorySession')
            ->will($this->returnValue($invSession));

        $isRequestRequired = true;
        $canFetchDetails = true;

        $invSession->expects($this->once())
            ->method('getInventoryDetailsResult')
            ->will($this->returnValue($result));

        $coreSession->expects($this->once())
            ->method('isDetailsUpdateRequired')
            ->will($this->returnValue($isRequestRequired));
        $detailsModel->expects($this->once())
            ->method('canFetchDetails')
            ->will($this->returnValue($canFetchDetails));
        $detailsModel->expects($this->once())
            ->method('tryOperation')
            ->with($this->isInstanceOf('Mage_Sales_Model_Quote'))
            ->will($this->returnValue($result));
        $invSession->expects($this->once())
            ->method('setInventoryDetailsResult')
            ->with(
                $this->isInstanceOf('EbayEnterprise_Inventory_Model_Details_Result')
            )
            ->will($this->returnSelf());

        $coreSession->expects($this->once())
            ->method('resetDetailsUpdateRequired')
            ->will($this->returnSelf());

        $output = $detailsModel->fetch($quote);
        $this->assertSame($result, $output);
    }

    /**
     * verify
     * - api and request objects are setup and ready to use
     */
    public function testPrepareApi()
    {
        $coreHelper = $this->getHelperMock('eb2ccore/data', ['getSdkApi']);
        $invHelper = $this->getHelperMock('ebayenterprise_inventory/data', ['getConfigModel']);
        $config = $this->buildCoreConfigRegistry([
            'apiService' => 'inventory',
            'apiDetailsOperation' => 'details/get',
        ]);
        $coreHelper->expects($this->once())
            ->method('getSdkApi')
            ->with(
                $this->identicalTo('inventory'),
                $this->identicalTo('details/get')
            )
            ->will($this->returnValue($this->httpApi));
        $invHelper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($config));
        $details = Mage::getModel(
            'ebayenterprise_inventory/details',
            ['helper' => $invHelper, 'core_helper' => $coreHelper, 'quote' => $this->quote]
        );
        $api = EcomDev_Utils_Reflection::invokeRestrictedMethod($details, 'prepareApi');
        $this->assertInstanceOf(self::HTTP_API, $api);
    }

    /**
     * provide whether the quote address, alternate address, or neither address
     * has enough data to build the request.
     *
     * @return array
     */
    public function provideAddressAvailabilityStates()
    {
        return [
            [true, false],
            [false, true],
            [false, false],
        ];
    }

    /**
     * verify
     * - if the quote address has enough data the items will be added to the
     * payload.
     * - if an alternate address has enough data, the items for the address will
     * be added to the payload using the alternate address.
     * - no items are added to the payload if meither the quote address nor the
     * alternate address have enough data.
     *
     * @param bool
     * @param bool
     * @dataProvider provideAddressAvailabilityStates
     */
    public function testPrepareRequest($hasQuoteAddress, $hasAlternateAddress)
    {
        $factory = $this->getHelperMock(
            'ebayenterprise_inventory/details_factory',
            ['createRequestBuilder']
        );
        $builder = $this->getModelMockBuilder('ebayenterprise_inventory/details_request_builder')
            ->disableOriginalConstructor()
            ->setMethods(['addItemPayloads'])
            ->getMock();
        $selector = $this->getHelperMock('ebayenterprise_inventory/details_item_selection', ['selectFrom']);
        $details = $this->getModelMockBuilder('ebayenterprise_inventory/details')
            ->setConstructorArgs([[
                'factory' => $factory,
                'selection' => $selector,
                'logger' => $this->logger,
                'log_context' => $this->logContext,
            ]])
            ->setMethods(['selectAddresses', 'getAlternateAddress'])
            ->getMock();

        $factory->expects($this->once())
            ->method('createRequestBuilder')
            ->with($this->isInstanceOf(
                '\eBayEnterprise\RetailOrderManagement\Payload\Inventory\IInventoryDetailsRequest'
            ))
            ->will($this->returnValue($builder));

        // if the customer hasn't entered an address in checkout
        // the addresses returned by the quote are empty
        $quoteAddress = $hasQuoteAddress
            ? $this->stubAddress([$this->stubItem()])
            : Mage::getModel('sales/quote_address');
        $details->expects($this->once())
            ->method('selectAddresses')
            ->with($this->isInstanceOf('Mage_Sales_Model_Quote'))
            ->will($this->returnValue([$quoteAddress]));
        // we need to get an alternate address in case there the addresses
        // we get from the quote do not have enough data
        $alternateAddress = $hasAlternateAddress ? $this->stubAddress() : null;
        $details->expects($this->once())
            ->method('getAlternateAddress')
            ->with($this->isInstanceOf('Mage_Sales_Model_Quote'))
            ->will($this->returnValue($alternateAddress));
        $selector->expects($this->any())
            ->method('selectFrom')
            ->with($this->isType('array'))
            ->will($this->returnArgument(0));
        // if neither the quote address nor the alternate address
        // have enough information to build the request, then no items
        // should be added to the payload.
        if (!$hasQuoteAddress && !$hasAlternateAddress) {
            $builder->expects($this->never())
                ->method('addItemPayloads');
        } else {
            // the payload will be built with either the quote address or
            // the alternate address depending on whether the quote address is
            // valid or not
            $builder->expects($this->once())
                ->method('addItemPayloads')
                ->with(
                    $this->isType('array'),
                    $this->identicalTo($hasQuoteAddress ? $quoteAddress : $alternateAddress)
                )
                ->will($this->returnSelf());
        }
        EcomDev_Utils_Reflection::invokeRestrictedMethod($details, 'prepareRequest', [$this->httpApi, $this->quote]);
    }

    /**
     * stub an item with information required for an item payload
     *
     * @param  integer
     * @param  string
     * @param  integer
     * @param  Mage_Catalog_Model_Product
     * @return Mage_Sales_Model_Quote_Item_Abstract
     */
    protected function stubItem($id = 1, $sku = 'sku_1', $qty = 1, Mage_Catalog_Model_Product $product = null)
    {
        $stub = $this->getModelMock('sales/quote_item_abstract', ['getId', 'getQty', 'getSku', 'getProduct'], true);

        $stub->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $stub->expects($this->any())
            ->method('getQty')
            ->will($this->returnValue($qty));
        $stub->expects($this->any())
            ->method('getSku')
            ->will($this->returnValue($sku));
        $product = $product ?: Mage::getModel('catalog/product', ['type_id'=>'simple', ]);
        $stub->expects($this->any())
            ->method('getProduct')
            ->will($this->returnValue($product));
        return $stub;
    }

    /**
     * stub an address with information required for an item payload
     *
     * @param array
     * @return Mage_Sales_Model_Quote_Address
     */
    protected function stubAddress(array $items = [])
    {
        $stub = $this->getModelMock('sales/quote_address', ['__call', 'getAllItems', 'getStreet']);
        $stub->expects($this->any())
            ->method('getAllItems')
            ->will($this->returnValue($items));
        $stub->expects($this->any())
            ->method('getStreet')
            ->will($this->returnValue('street lines'));
        $stub->expects($this->any())
            ->method('__call')
            ->will($this->returnValueMap([
                ['getCity', [], 'a city'],
                ['getCountryId', [], 'US'],
                ['getShippingMethod', [], 'shipping_method'],
            ]));
        return $stub;
    }

    /**
     * provide various exception types thrown by
     * the sdk.
     *
     * @return array
     */
    public function provideExceptions()
    {
        return [
            ['\eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError'],
            ['\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction'],
            ['\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation'],
            ['\eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload'],
        ];
    }

    /**
     * verify exceptions from the sdk are handled
     *
     * @param string
     * @dataProvider provideExceptions
     */
    public function testTryOperationExceptions($exception)
    {
        $quote = Mage::getModel('sales/quote');
        $result = $this->getModelMock('ebayenterprise_inventory/details_result');
        $detail = $this->getModelMockBuilder('ebayenterprise_inventory/details')
            ->setConstructorArgs([['logger' => $this->logger, 'logger_context' => $this->logContext]])
            ->setMethods(['prepareApi', 'prepareRequest', 'prepareResult'])
            ->getMock();

        $detail->expects($this->once())
            ->method('prepareApi')
            ->will($this->returnValue($this->httpApi));
        $detail->expects($this->once())
            ->method('prepareRequest')
            ->will($this->returnValue($this->request));
        $this->request->expects($this->once())
            ->method('getItems')
            ->will($this->returnValue(['notempty']));
        $this->httpApi->expects($this->once())
            ->method('send')
            ->will($this->throwException(new $exception));
        // dont really care much about the prepare results call
        $detail->expects($this->any())
            ->method('prepareResult')
            ->will($this->returnValue($result));

        $this->setExpectedException('EbayEnterprise_Inventory_Exception_Details_Operation_Exception');
        EcomDev_Utils_Reflection::invokeRestrictedMethod($detail, 'tryOperation', [$quote]);
    }
}
