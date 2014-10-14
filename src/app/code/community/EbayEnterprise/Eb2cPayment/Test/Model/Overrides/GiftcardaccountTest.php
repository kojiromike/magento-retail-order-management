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

class EbayEnterprise_Eb2cPayment_Test_Model_Overrides_GiftcardaccountTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * replacing by mock of the Mage_Checkout_Model_Session class
	 * @return Mock_Mage_Checkout_Model_Session
	 */
	protected function _buildCheckoutModelSession()
	{
		$salesModelQuoteMock = $this->getModelMockBuilder('sales/quote')
			->setMethods(array('getStoreId', 'save'))
			->getMock();
		$salesModelQuoteMock->expects($this->any())
			->method('getStoreId')
			->will($this->returnValue(1));
		$salesModelQuoteMock->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		$checkoutModelSessionMock = $this->getModelMockBuilder('checkout/session')
			->setMethods(array('getQuote'))
			->disableOriginalConstructor()
			->getMock();
		$checkoutModelSessionMock->expects($this->any())
			->method('getQuote')
			->will($this->returnValue($salesModelQuoteMock));
		return $checkoutModelSessionMock;
	}
	/**
	 * replacing by mock of the EbayEnterprise_Eb2cPayment_Model_Giftcardaccount class
	 * @return void
	 */
	protected function _replaceGiftCardAccountByMock()
	{
		$mock = $this->getModelMockBuilder('eb2cpaymentoverrides/giftcardaccount')
			->setMethods(array('_getCheckoutSession', 'isValid'))
			->getMock();
		$mock->expects($this->any())
			->method('_getCheckoutSession')
			->will($this->returnValue($this->_buildCheckoutModelSession()));
		$mock->expects($this->any())
			->method('isValid')
			->will($this->returnValue(true));
		$this->replaceByMock('model', 'eb2cpaymentoverrides/giftcardaccount', $mock);
	}
	/**
	 * replacing by mock of the EbayEnterprise_Eb2cPayment_Model_Giftcardaccount class
	 * @return void
	 */
	protected function _replaceGiftCardAccountByMockWithException()
	{
		$mock = $this->getModelMockBuilder('eb2cpaymentoverrides/giftcardaccount')
			->setMethods(array('_getCheckoutSession', 'isValid', 'getId'))
			->getMock();
		$mock->expects($this->any())
			->method('_getCheckoutSession')
			->will($this->returnValue($this->_buildCheckoutModelSession()));
		$mock->expects($this->any())
			->method('isValid')
			->will($this->returnValue(true));
		$mock->expects($this->any())
			->method('getId')
			->will($this->returnValue(1));
		$this->replaceByMock('model', 'eb2cpaymentoverrides/giftcardaccount', $mock);
	}
	/**
	 * testing addToCart method
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 */
	public function testAddToCart()
	{
		$this->_replaceGiftCardAccountByMock();
		$giftCardAccount = Mage::getModel('eb2cpaymentoverrides/giftcardaccount');
		$this->assertSame($giftCardAccount, $giftCardAccount->addToCart(true, null));
	}
	/**
	 * testing addToCart method - with exception
	 * @loadFixture loadWebsiteConfig.yaml
	 * @loadFixture loadEnterpriseGiftCardAccount.yaml
	 * @expectedException Mage_Core_Exception
	 */
	public function testAddToCartWithException()
	{
		$this->_replaceGiftCardAccountByMockWithException();
		$enterpriseGiftCardAccountHelperMock = $this->getHelperMockBuilder('enterprise_giftcardaccount/data')
			->disableOriginalConstructor()
			->setMethods(array('getCards', 'setCards'))
			->getMock();
		$enterpriseGiftCardAccountHelperMock->expects($this->any())
			->method('getCards')
			->will($this->returnValue(array(array('i' => 1))));
		$enterpriseGiftCardAccountHelperMock->expects($this->any())
			->method('setCards')
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'enterprise_giftcardaccount', $enterpriseGiftCardAccountHelperMock);
		$giftCardAccount = Mage::getModel('eb2cpaymentoverrides/giftcardaccount');
		$this->assertSame($giftCardAccount, $giftCardAccount->addToCart(true, null));
	}
}
