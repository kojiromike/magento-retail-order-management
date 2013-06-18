<?php

class TrueAction_Eb2c_Address_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{

	/**
	 * Generate a reusable Mage_Customer_Model_Address object
	 */
	protected function _generateAddressObject()
	{
		$address = Mage::getModel('customer/address');
		$address->setStreet("123 Main St\nSTE 6\nFoo\nBar");
		$address->setCity('Auburn');
		$address->setRegionId('51');
		$address->setCountryId('US');
		$address->setPostcode('13021');
		return $address;
	}

	protected function _generatePhysicalAddressElement()
	{
		$dom = new TrueAction_Dom_Document();
		$root = $dom->createElement('address');
		$root->addChild('Line1', '123 Main St')
			->addChild('Line2', 'STE 6')
			->addChild('Line3', 'Foo')
			->addChild('Line4', 'Bar')
			->addChild('City', 'Auburn')
			->addChild('MainDivision', 'PA')
			->addChild('CountryCode', 'US')
			->addChild('PostalCode', '13021');
		return $root;
	}

	public function testTextValueFromXmlPath()
	{
		$doc = new TrueAction_Dom_Document();
		$doc->addElement('root');
		$root = $doc->documentElement;
		$root->createChild('foo')->addChild('bar', 'one')->addChild('baz', 'two');
		$root->createChild('color', 'red');

		$multiValues = Mage::helper('eb2caddress')->getTextValueByXPath($root, 'foo/*');
		$singleValue = Mage::helper('eb2caddress')->getTextValueByXPath($root, 'color');
		$nullValue = Mage::helper('eb2caddress')->getTextValueByXPath($root, 'nope_not_here');
		$this->assertSame($multiValues, array('one', 'two'));
		$this->assertSame($singleValue, 'red');
		$this->assertNull($nullValue);
	}

	public function testAddressStreetLines()
	{
		$street = Mage::helper('eb2caddress')
			->physicalAddressStreet($this->_generatePhysicalAddressElement());
		$this->assertEquals($street, array('123 Main St', 'STE 6', 'Foo', 'Bar'));
	}

	public function testAddressCity()
	{
		$city = Mage::helper('eb2caddress')
			->physicalAddressCity($this->_generatePhysicalAddressElement());
		$this->assertSame($city, 'Auburn');
	}

	public function testAddressRegion()
	{
		$region = Mage::helper('eb2caddress')
			->physicalAddressRegionId($this->_generatePhysicalAddressElement());
		$this->assertSame($region, '51'); // 'PA' maps to region id '51' in Magento addreses
	}

	public function testAddressContry()
	{
		$country = Mage::helper('eb2caddress')
			->physicalAddressCountryId($this->_generatePhysicalAddressElement());
		$this->assertSame($country, 'US');
	}

	public function testAddressPostcode()
	{
		$postcode = Mage::helper('eb2caddress')
			->physicalAddressPostcode($this->_generatePhysicalAddressElement());
		$this->assertSame($postcode, '13021');
	}

	public function testAddressToXml()
	{
		$dom = new TrueAction_Dom_Document();
		$address = $this->_generateAddressObject();
		$addressFragment = Mage::helper('eb2caddress')
			->addressToPhysicalAddressXml($address, $dom);
		$fragmentNodes = $addressFragment->childNodes;
		$this->assertEquals($fragmentNodes->item(0)->nodeName, 'Line1');
		$this->assertEquals($fragmentNodes->item(0)->textContent, $address->getStreet1());
		$this->assertEquals($fragmentNodes->item(1)->nodeName, 'Line2');
		$this->assertEquals($fragmentNodes->item(1)->textContent, $address->getStreet2());
		$this->assertEquals($fragmentNodes->item(2)->nodeName, 'Line3');
		$this->assertEquals($fragmentNodes->item(2)->textContent, $address->getStreet3());
		$this->assertEquals($fragmentNodes->item(3)->nodeName, 'Line4');
		$this->assertEquals($fragmentNodes->item(3)->textContent, $address->getStreet4());
		$this->assertEquals($fragmentNodes->item(4)->nodeName, 'City');
		$this->assertEquals($fragmentNodes->item(4)->textContent, $address->getCity());
		$this->assertEquals($fragmentNodes->item(5)->nodeName, 'MainDivision');
		$this->assertEquals($fragmentNodes->item(5)->textContent, $address->getRegionCode());
		$this->assertEquals($fragmentNodes->item(6)->nodeName, 'CountryCode');
		$this->assertEquals($fragmentNodes->item(6)->textContent, $address->getCountry());
		$this->assertEquals($fragmentNodes->item(7)->nodeName, 'PostalCode');
		$this->assertEquals($fragmentNodes->item(7)->textContent, $address->getPostcode());
	}

	/**
	 * Test converting a DOM element representing an address to a proper Mage_Customer_Model_Address object
	 * @test
	 */
	public function testXmlToAddress()
	{
		$address = Mage::helper('eb2caddress')
			->physicalAddressXmlToAddress($this->_generatePhysicalAddressElement());
		$this->assertTrue($address instanceof Mage_Customer_Model_Address);
		$this->assertSame($address->getStreet1(), '123 Main St');
		$this->assertSame($address->getStreet2(), 'STE 6');
		$this->assertSame($address->getStreet3(), 'Foo');
		$this->assertSame($address->getStreet4(), 'Bar');
		$this->assertSame($address->getCity(), 'Auburn');
		$this->assertSame($address->getRegionCode(), 'PA');
		$this->assertSame($address->getCountry(), 'US');
		$this->assertSame($address->getPostcode(), '13021');
	}

}