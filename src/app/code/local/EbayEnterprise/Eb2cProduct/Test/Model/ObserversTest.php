<?php
class EbayEnterprise_Eb2cProduct_Test_Model_ObserversTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * @test
 	 * _attributeStringToArray process manually entered data from a config.xml. Want to test that 
	 * this method can correctly parse good data, and be somewhat robust against incorrect data.
	 */
	public function testAttributeStringToArray()
	{
		$observer = Mage::getModel('eb2cproduct/observers');
		$fn = $this->_reflectMethod($observer, '_attributeStringToArray');

		// valid string, should get 3 elements back
		$rc = $fn->invoke($observer, 'a,b,c,');
		$this->assertEquals(3, count($rc));

		// Pass in null, should get null back
		$rc = $fn->invoke($observer, null);
		$this->assertEquals(null, $rc);

		// Pass in no separators, should get that string at element 0
		$rc = $fn->invoke($observer, 'abc');
		$this->assertEquals('abc', $rc[0]);

		// Pass in empty string, should get null back
		$rc = $fn->invoke($observer, '');
		$this->assertEquals(null, $rc);
	}
}
