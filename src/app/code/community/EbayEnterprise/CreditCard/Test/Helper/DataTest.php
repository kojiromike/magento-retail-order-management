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

class EbayEnterprise_CreditCard_Test_Helper_DataTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test getting the ROM tender type for a given Magento CC type via the config registry.
	 */
	public function testGetTenderTypeForCcType()
	{
		$expectedType = 'VC';
		$ccType = 'VI';
		$helper = $this->getHelperMock('ebayenterprise_creditcard/data', array('getConfigModel'));
		$helper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(
				array('tenderTypeVi' => $expectedType)
			)));
		$this->assertSame($expectedType, $helper->getTenderTypeForCcType($ccType));
	}
	/**
	 * Test cleaning the CC Auth XML to remove sensitive data - e.g. CardSecurityCode
	 * and PaymentAccountUniqueId values.
	 */
	public function testCleanAuthXml()
	{
		$xml = '<_><CardSecurityCode>123</CardSecurityCode><PaymentAccountUniqueId isToken="true"><[!CDATA[1111411AP+111111333]]></PaymentAccountUniqueId></_>';
		$this->assertSame(
			'<_><CardSecurityCode>***</CardSecurityCode><PaymentAccountUniqueId isToken="true">***</PaymentAccountUniqueId></_>',
			Mage::helper('ebayenterprise_creditcard')->cleanAuthXml($xml)
		);
	}
	/**
	 * Same as self::testCleanAuthXml, just with encrypted nodes
	 */
	public function testCleanAuthXmlEncrypted()
	{
		$xml = '<_><EncryptedCardSecurityCode>$bt4|javascript_1_3_10$ThCaUU65veFuC2A7AK4CIuM=</EncryptedCardSecurityCode><EncryptedPaymentAccountUniqueId>$bt4|javascript_1_3_10$gzIUBGAUh6MRehbaWEcw+0047SxTxc=</EncryptedPaymentAccountUniqueId></_>';
		$this->assertSame(
			'<_><EncryptedCardSecurityCode>***</EncryptedCardSecurityCode><EncryptedPaymentAccountUniqueId>***</EncryptedPaymentAccountUniqueId></_>',
			Mage::helper('ebayenterprise_creditcard')->cleanAuthXml($xml)
		);
	}
}
