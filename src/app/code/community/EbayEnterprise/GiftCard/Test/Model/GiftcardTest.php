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

class EbayEnterprise_GiftCard_Test_Model_GiftcardTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	public function setUp()
	{
		parent::setUp();

		// suppressing the real session from starting
		$session = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->replaceByMock('singleton', 'core/session', $session);
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
		$giftCard = Mage::getModel('ebayenterprise_giftcard/giftcard');
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
			'_setPayloadAccountUniqueId',
			array($payload)
		);
	}
	/**
	 * Provide errors that may be thrown by the SDK when makeing the API request.
	 * @return array
	 */
	public function provideApiExceptions()
	{
		return array(
			array('\eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError'),
			array('\eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload'),
		);
	}
	/**
	 * Any exceptions thrown by the SDK should be caught and converted to gift card
	 * exceptions.
	 * @param string $exceptionType Type of exception for the SDK to throw
	 * @dataProvider provideApiExceptions
	 */
	public function testSendingApiRequestErrorHandling($exceptionType)
	{
		$api = $this->getMockBuilder('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi')
			->disableOriginalConstructor()
			->getMock();
		$api->expects($this->any())
			->method('send')
			->will($this->throwException(new $exceptionType));

		$this->setExpectedException('EbayEnterprise_GiftCard_Exception');
		EcomDev_Utils_Reflection::invokeRestrictedMethod(
			Mage::getModel('ebayenterprise_giftcard/giftcard'),
			'_sendRequest',
			array($api)
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
		$gc = Mage::getModel('ebayenterprise_giftcard/giftcard');
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
			Mage::getModel('ebayenterprise_giftcard/giftcard'),
			'_handleBalanceResponse',
			array($api)
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
			Mage::getModel('ebayenterprise_giftcard/giftcard'),
			'_handleRedeemResponse',
			array($api)
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
			Mage::getModel('ebayenterprise_giftcard/giftcard'),
			'_handleVoidResponse',
			array($api)
		);
	}
}
