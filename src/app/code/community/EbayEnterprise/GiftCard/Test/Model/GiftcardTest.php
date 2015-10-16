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

class EbayEnterprise_GiftCard_Test_Model_GiftcardTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_GiftCard_Model_Giftcard $giftCard */
    protected $giftCard;

    public function setUp()
    {
        parent::setUp();

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
        $this->giftCard = Mage::getModel('ebayenterprise_giftcard/giftcard');
    }

    /**
     * Test setting payment account unique id information on a payload
     * @param  array $giftCardData Key/value pairs of gift card setter method to data to set - tokenized number must be set after non-tokenized number
     * @param  string $cardNumber
     * @param  bool $isToken
     * @dataProvider dataProvider
     */
    public function testSetPayloadAccountUniqueId($giftCardData, $cardNumber, $isToken)
    {
        $giftCard = $this->giftCard;
        foreach ($giftCardData as $accessorMethod => $value) {
            $giftCard->$accessorMethod($value);
        }
        $payload = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPaymentAccountUniqueId');
        $payload->expects($this->once())
            ->method('setCardNumber')
            ->with($this->identicalTo($cardNumber))
            ->will($this->returnSelf());
        $payload->expects($this->once())
            ->method('setPanIsToken')
            ->with($this->identicalTo($isToken))
            ->will($this->returnSelf());

        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $giftCard,
            'setPayloadAccountUniqueId',
            [$payload]
        );
    }
    /**
     * Provide errors that may be thrown by the SDK when making the API request
     * and the exception that is expected to be thrown, if any, in response to
     * the SDK exception.
     *
     * @return array
     */
    public function provideApiExceptions()
    {
        return [
            ['\eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError', 'EbayEnterprise_GiftCard_Exception'],
            ['\eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload', 'EbayEnterprise_GiftCard_Exception'],
            ['\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation', '\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation'],
            ['\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction', '\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction'],
            ['\Exception', '\Exception'],
       ];
    }
    /**
     * Any exceptions thrown by the SDK should be caught and converted to gift card
     * exceptions.
     *
     * @param string
     * @param string
     * @dataProvider provideApiExceptions
     */
    public function testSendingApiRequestErrorHandling($exceptionType, $expectedExceptionType)
    {
        $api = $this->getMockBuilder('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi')
            ->disableOriginalConstructor()
            ->getMock();
        $api->expects($this->any())
            ->method('send')
            ->will($this->throwException(new $exceptionType(__METHOD__ . ': test exception')));

        $this->setExpectedException($expectedExceptionType);
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->giftCard,
            'sendRequest',
            [$api]
        );
    }
    /**
     * Changing the card number should clear out any set tokenized card numbers.
     */
    public function testSettingCardNumber()
    {
        $origNumber = '123412341234';
        $origTokenized = '1234abcd1234';
        $newNumber = '555555555555';
        $gc = $this->giftCard;
        $gc->setCardNumber($origNumber)->setTokenizedCardNumber($origTokenized);
        // check some pre-conditions - card number and tokenized number are set
        $this->assertSame($origNumber, $gc->getCardNumber());
        $this->assertSame($origTokenized, $gc->getTokenizedCardNumber());
        // change the card number
        $gc->setCardNumber($newNumber);
        // card should have new number and no tokenized number any longer
        $this->assertSame($newNumber, $gc->getCardNumber());
        $this->assertNull($gc->getTokenizedCardNumber());
    }
    /**
     * Unsuccessful balance requests should result in an exception
     */
    public function testHandleBalanceResponseFailed()
    {
        $api = $this->getMock('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi');
        $payload = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Payment\IStoredValueBalanceReply');
        $api->expects($this->any())
            ->method('getResponseBody')
            ->will($this->returnValue($payload));
        $payload->expects($this->any())
            ->method('isSuccessful')
            ->will($this->returnValue(false));

        $this->setExpectedException('EbayEnterprise_GiftCard_Exception');
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->giftCard,
            'handleBalanceResponse',
            [$api]
        );
    }
    /**
     * Unsuccessful redeem requests should result in an exception
     */
    public function testHandleRedeemResponseFailed()
    {
        $api = $this->getMock('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi');
        $payload = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Payment\IStoredValueRedeemReply');
        $api->expects($this->any())
            ->method('getResponseBody')
            ->will($this->returnValue($payload));
        $payload->expects($this->any())
            ->method('wasRedeemed')
            ->will($this->returnValue(false));

        $this->setExpectedException('EbayEnterprise_GiftCard_Exception');
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->giftCard,
            'handleRedeemResponse',
            [$api]
        );
    }
    /**
     * Unsuccessful void requests should result in an exception
     */
    public function testHandleVoidResponseFailed()
    {
        $api = $this->getMock('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi');
        $payload = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Payment\IStoredValueRedeemVoidReply');
        $api->expects($this->any())
            ->method('getResponseBody')
            ->will($this->returnValue($payload));
        $payload->expects($this->any())
            ->method('wasVoided')
            ->will($this->returnValue(false));

        $this->setExpectedException('EbayEnterprise_GiftCard_Exception');
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->giftCard,
            'handleVoidResponse',
            [$api]
        );
    }

    /**
     * @return array
     */
    public function providerLogStoredValuePayload()
    {
        return [
            [
                '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IStoredValueBalanceRequest',
                true,
                '<StoredValueBalanceRequest/>',
                '<StoredValueBalanceRequest/>',
                'Sending StoredValueBalanceRequest.'
            ],
            [
                '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IStoredValueBalanceReply',
                false,
                '<StoredValueBalanceReply/>',
                '<StoredValueBalanceReply/>',
                'Receive StoredValueBalanceReply.'
            ],
        ];
    }

    /**
     * Scenario: Mask API Request/Response XML Payload sensitive data and log
     * Given an API Request/Response, a flag indicating log request or log response, and the message to log.
     * When Log payload for API request/response.
     * Then the sensitive data in the request/response are masked and logged.
     *
     * @param string
     * @param bool
     * @param string
     * @param string
     * @param string
     * @dataProvider providerLogStoredValuePayload
     */
    public function testLogStoredValuePayload($payloadClass, $isRequest, $xml, $maskXml, $message)
    {
        /** @var EbayEnterprise_Giftcard_Helper_Data */
        $mask = $this->getModelMock('ebayenterprise_giftcard/mask', ['maskXmlNodes']);
        $mask->expects($this->once())
            ->method('maskXmlNodes')
            ->with($this->identicalTo($xml))
            ->will($this->returnValue($maskXml));

        /** @var array */
        $metaData = $isRequest ? ['rom_request_body' => $maskXml] : ['rom_response_body' => $maskXml];
        /** @var Mock_EbayEnterprise_MageLog_Helper_Data */
        $logger = $this->getHelperMock('ebayenterprise_magelog/data', ['debug']);
        $logger->expects($this->once())
            ->method('debug')
            ->with($this->identicalTo($message), $this->identicalTo($metaData))
            ->will($this->returnValue(null));

        /** @var EbayEnterprise_MageLog_Helper_Context */
        $context = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
        $context->expects($this->once())
            ->method('getMetaData')
            ->with($this->identicalTo('EbayEnterprise_GiftCard_Model_Giftcard'), $this->identicalTo($metaData))
            ->will($this->returnValue($metaData));

        /** Mock_IPayload $payload */
        $payload = $this->getMockForAbstractClass($payloadClass, [], '', true, true, true, ['serialize']);
        $payload->expects($this->once())
            ->method('serialize')
            ->will($this->returnValue($xml));

        /** Mock_IBidirectionalApi */
        $api = $this->getMockForAbstractClass('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi', [], '', true, true, true, ['getRequestBody', 'getResponseBody']);
        $api->expects($isRequest ? $this->once() : $this->never())
            ->method('getRequestBody')
            ->will($this->returnValue($payload));
        $api->expects($isRequest ? $this->never() : $this->once())
            ->method('getResponseBody')
            ->will($this->returnValue($payload));

        /** @var EbayEnterprise_GiftCard_Model_Giftcard */
        $giftCard = Mage::getModel('ebayenterprise_giftcard/giftcard', [
            'mask' => $mask,
            'logger' => $logger,
            'context' => $context,
        ]);

        $this->assertSame($giftCard, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $giftCard, 'logStoredValuePayload', [$api, $isRequest, $message]
        ));
    }
}
