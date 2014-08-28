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

/**
 * Test the generation of the xml request to the address validation service
 */
class EbayEnterprise_Address_Test_Model_Validation_RequestTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Parts of an address to use when building out the address request
	 */
	protected $_addressParts = array(
		'line1'       => '123 Main St',
		'line2'       => 'STE 6',
		'line3'       => 'Foo',
		'line4'       => 'Bar',
		'city'        => 'Auburn',
		'region_id'   => '51',
		'region_code' => 'PA',
		'country_id'  => 'US',
		'postcode'    => '13021',
	);

	/**
	 * Create a Mage_Customer_Model_Address_Abstract stub for testing
	 */
	protected function _createAddressStub()
	{
		$parts = $this->_addressParts;

		$stub = $this->getMock(
			'Mage_Customer_Model_Address_Abstract',
			array('getStreet', 'getCity', 'getRegionCode', 'getCountry', 'getPostcode')
		);

		$stub->expects($this->any())
			->method('getStreet')
			->will($this->returnValue(
				array($parts['line1'], $parts['line2'], $parts['line3'], $parts['line4'])
			));
		$stub->expects($this->any())
			->method('getCity')
			->will($this->returnValue($parts['city']));
		$stub->expects($this->any())
			->method('getRegionCode')
			->will($this->returnValue($parts['region_code']));
		$stub->expects($this->any())
			->method('getCountry')
			->will($this->returnValue($parts['country_id']));
		$stub->expects($this->any())
			->method('getPostcode')
			->will($this->returnValue($parts['postcode']));
		return $stub;
	}

	/**
	 * build the message dom for an address.
	 * @large
	 */
	public function testRequestMessage()
	{
		$request = $this->_createRequest();
		$message = $request->getMessage();
		$xpath = new DOMXPath($message);
		$ns = $message->lookupNamespaceUri($message->namespaceURI);
		$xpath->registerNamespace('x', $ns);
		$config = Mage::helper('ebayenterprise_address')->getConfigModel();
		$this->assertEquals(
			$ns,
			$config->apiNamespace
		);
		$maxSuggestions = $xpath
			->query('x:AddressValidationRequest/x:Header/x:MaxAddressSuggestions', $message)
			->item(0)
			->textContent;
		// test that the MaxAddressSuggestions pulled correctly from config
		$this->assertEquals(
			$config->maxAddressSuggestions,
			$maxSuggestions
		);
		$address = $xpath
			->query('x:AddressValidationRequest/x:Address', $message);
		// make sure there is an address node - actual content tested elsewhere
		$this->assertSame(
			$address->length,
			1
		);
	}

	/**
	 * Test that the generated message validates per the xsd schema.
	 */
	public function testRequestMessageValidates()
	{
		$xsd = Mage::getModuleDir('', 'EbayEnterprise_Eb2cCore') . DS . 'xsd' . DS . 'Address-Validation-Service-1.0.xsd';
		$request = $this->_createRequest();
		$message = $request->getMessage();
		$this->assertTrue($message->schemaValidate($xsd));
	}

	/**
	 * Create a EbayEnterprise_Address_Model_Validation_Request to test against.
	 * Ensures that all of the necessary setup of the object is done.
	 */
	protected function _createRequest()
	{
		return Mage::getModel('ebayenterprise_address/validation_request')
			->setAddress($this->_createAddressStub());
	}
}