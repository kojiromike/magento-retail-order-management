<?php

use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;
use eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation;
use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\TenderType\ILookupReply;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\TenderType\ILookupRequest;

class EbayEnterprise_GiftCard_Test_Model_Tendertype_LookupTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    protected $api;
    protected $request;
    protected $reply;
    protected $logger;
    protected $logContext;
    protected $cardNumber = '123242344331231231';
    protected $panIsToken = false;
    protected $tenderType = 'SV';
    protected $currencyCode = 'USD';
    protected $constructorArgs;

    public function setUp()
    {
        parent::setUp();
        $this->api = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi'
        )
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Payload\Payment\TenderType\ILookupRequest'
        )
            ->getMockForAbstractClass();
        $this->reply = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Payload\Payment\TenderType\ILookupReply'
        )
            ->getMockForAbstractClass();

        // mock the api to work wiht the request and reply
        // payload mocks
        $this->api->expects($this->once())
            ->method('getRequestBody')
            ->will($this->returnValue($this->request));
        $this->api->expects($this->any())
            ->method('setRequestBody')
            ->with($this->identicalTo($this->request))
            ->will($this->returnSelf());
        $this->api->expects($this->any())
            ->method('getResponseBody')
            ->will($this->returnValue($this->reply));

        $this->request->expects($this->once())
            ->method('setCardNumber')
            ->with($this->identicalTo($this->cardNumber))
            ->will($this->returnSelf());
        $this->request->expects($this->once())
            ->method('setCurrencyCode')
            ->with($this->identicalTo($this->currencyCode))
            ->will($this->returnSelf());
        $this->request->expects($this->once())
            ->method('setPanIsToken')
            ->with($this->identicalTo($this->panIsToken))
            ->will($this->returnSelf());
        $this->request->expects($this->once())
            ->method('setTenderClass')
            ->with($this->identicalTo(ILookupRequest::CLASS_STOREDVALUE))
            ->will($this->returnSelf());

        $this->logger= $this->getHelperMockBuilder('ebayenterprise_magelog/data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logContext = $this->getHelperMockBuilder('ebayenterprise_magelog/context')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logContext->expects($this->any())
            ->method('getMetaData')
            ->will($this->returnValue([]));

        $this->constructorArgs = [
            'api' => $this->api,
            'card_number' => $this->cardNumber,
            'pan_is_token' => $this->panIsToken,
            'currency_code' => $this->currencyCode,
            'logger' => $this->logger,
            'log_context' => $this->logContext,
        ];
    }



    /**
     * Scenario 1: a successful lookup returns a tender type string
     * Given a Tendertype helper object and a configured HttpApi object
     * When lookupTenderType is called with an account
     * Then then an expected tender type string is returned
     */
    public function testSuccessfulOperation()
    {
        $this->api->expects($this->any())
            ->method('send')
            ->will($this->returnSelf());
        $this->reply->expects($this->once())
            ->method('isSuccessful')
            ->will($this->returnValue(true));
        $this->reply->expects($this->once())
            ->method('getTenderType')
            ->will($this->returnValue($this->tenderType));

        $lookupModel = $this->getModelMockBuilder('ebayenterprise_giftcard/tendertype_lookup')
            ->setConstructorArgs([$this->constructorArgs])
            ->setMethods(['logRequest'])
            ->getMock();

        $this->assertSame(
            $this->tenderType,
            $lookupModel->getTenderType()
        );
    }

    /**
     * provide exceptions thrown by the sdk
     *
     * @return array
     */
    public function provideSdkExceptions()
    {
        return [
            ['\eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload'],
            ['\eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError'],
            ['\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction'],
            ['\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation'],
        ];
    }

    /**
     * Scenario 2: a lookup is unsuccessful because of sdk errors
     * Given a Tendertype helper object and a configured HttpApi object
     * When lookupTenderType is called with an account
     * And the sdk throws an exception
     * Then a EbayEnterprise_GiftCard_Exception_TenderTypeLookupFailed_Exception is thrown
     *
     * @expectedException EbayEnterprise_GiftCard_Exception_TenderTypeLookupFailed_Exception
     * @dataProvider provideSdkExceptions
     */
    public function testGetTenderTypeSdkException($exception)
    {
        $this->api->expects($this->once())
            ->method('send')
            ->will($this->throwException(new $exception));

        $lookupModel = $this->getModelMockBuilder('ebayenterprise_giftcard/tendertype_lookup')
            ->setConstructorArgs([$this->constructorArgs])
            ->setMethods(['logRequest'])
            ->getMock();

        $lookupModel->getTenderType();
    }

    /**
     * Scenario 3: a lookup begets a response with a failure response code
     * Given a Tendertype helper object and a configured HttpApi object
     * When lookupTenderType is called with a card's account information
     * And the response code indicates failure
     * Then an EbayEnterprise_GiftCard_Exception_TenderTypeLookupFailed_Exception is thrown
     *
     * @expectedException EbayEnterprise_GiftCard_Exception_TenderTypeLookupFailed_Exception
     */
    public function testGetTenderTypeWithFailedResponseCode()
    {
        $this->api->expects($this->once())
            ->method('send')
            ->will($this->returnSelf());
        $this->reply->expects($this->once())
            ->method('isSuccessful')
            ->will($this->returnValue(false));

        $lookupModel = $this->getModelMockBuilder('ebayenterprise_giftcard/tendertype_lookup')
            ->setConstructorArgs([$this->constructorArgs])
            ->setMethods(['logRequest'])
            ->getMock();

        $lookupModel->getTenderType();
    }
}
