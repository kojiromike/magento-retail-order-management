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

class EbayEnterprise_Eb2cAddress_Test_Model_Validation_ResponseTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @dataProvider dataProvider
	 */
	public function testIsValid($valid, $message)
	{
		$response = Mage::getModel('eb2caddress/validation_response')
			->setMessage($message);
		$this->assertSame((bool) $valid, $response->isAddressValid());
	}

	/**
	 * Test creating a Mage_Customer_Model_Address from the response message.
	 *
	 * @dataProvider dataProvider
	 */
	public function testGettingOriginalAddress($message)
	{
		$response = Mage::getModel('eb2caddress/validation_response')
			->setMessage($message);
		$origAddress = $response->getOriginalAddress();
		$this->assertInstanceOf('Mage_Customer_Model_Address', $origAddress);
		$this->assertSame($origAddress->getStreet(1), '1671 Clark Street Rd');
		$this->assertSame($origAddress->getStreet(2), 'Omnicare Building');
		$this->assertSame($origAddress->getCity(), 'Auburn');
		$this->assertSame($origAddress->getRegionId(), 43);
		$this->assertSame($origAddress->getCountryId(), 'US');
		$this->assertSame($origAddress->getPostcode(), '13025');
	}

	/**
	 * Test creating Mage_Customer_Model_Address objects from the response message
	 * for each of the suggested addresses. The code in the helper/data for converting
	 * address xml to address objects is thoroughly covered so I'm not so much concerend
	 * in the address objects being instantiated and populated properly, hence minimal
	 * checking here of the data in the address objects. Main concern here is:
	 * - The number of suggested addresses returned
	 * - That each suggested address is a proper address object
	 *
	 * @dataProvider dataProvider
	 */
	public function testGettingSuggestedAddresses($message)
	{
		$response = Mage::getModel('eb2caddress/validation_response')
			->setMessage($message);
		$suggestions = $response->getAddressSuggestions();
		$this->assertSame(count($suggestions), 2);
		$first = $suggestions[0];
		$this->assertInstanceOf('Mage_Customer_Model_Address', $first);
		$this->assertSame($first->getCity(), 'Foo');
		$second = $suggestions[1];
		$this->assertInstanceOf('Mage_Customer_Model_Address', $second);
		$this->assertSame($second->getRegionId(), 51);
	}

	/**
	 * When there are multiple suggestions, and the supplied is not considered valid,
	 * there should be no valid address, hence getValidAddress should return null.
	 *
	 * @dataProvider dataProvider
	 */
	public function testNoValidAddress($message)
	{
		$response = Mage::getModel('eb2caddress/validation_response')
			->setMessage($message);
		$this->assertNull($response->getValidAddress());
	}

	/**
	 * When there are no suggestions and the original address is considered valid,
	 * the valid address should be the same as the original address.
	 *
	 * @dataProvider dataProvider
	 */
	public function testOriginalAddressValid($message)
	{
		$response = Mage::getModel('eb2caddress/validation_response')
			->setMessage($message);
		$this->assertSame(
			$response->getOriginalAddress(),
			$response->getValidAddress()
		);
	}

	/**
	 * When there are more than one suggestion in the response message,
	 * should accurately detect that there are suggestions.
	 *
	 * @dataProvider dataProvider
	 */
	public function testDetectingSuggestionsInMessage($message)
	{
		$response = Mage::getModel('eb2caddress/validation_response')
			->setMessage($message);
		$this->assertTrue($response->hasAddressSuggestions());
	}

	/**
	 * Should accurately detect and report that there are no suggestions when
	 * no suggestions exist in the response message.
	 *
	 * @dataProvider dataProvider
	 */
	public function testDetectNoSuggestionsInMessage($message)
	{
		$response = Mage::getModel('eb2caddress/validation_response')
			->setMessage($message);
		$this->assertFalse($response->hasAddressSuggestions());
	}
}
