<?php
/**
 * The class TrueAction_Eb2c_Core_Test_Model_ItemTest exercises various methods/ instances of
 * 	TrueAction_Eb2c_Core Models.
 *
 */
class TrueAction_Eb2c_Core_Test_Model_ItemInterfaceTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @test
	 *
	 * Here I want to be sure that items created with the Mage factory will be appropriate
	 *	instances of the Item Classes I'm extending via the Eb2c Core Item Interface.
	 */
	public function testClassFactory()
	{
		$orderItemObject = Mage::getModel('sales_order/item');
		$this->assertInstanceOf('Mage_Sales_Model_Order_Item', $orderItemObject);
		$this->assertInstanceOf('TrueAction_Eb2c_Core_Model_Order_Item', $orderItemObject );

		$quoteItemObject = Mage::getModel('sales_quote/item');
		$this->assertInstanceOf('Mage_Sales_Model_Quote_Item', $quoteItemObject);
		$this->assertInstanceOf('TrueAction_Eb2c_Core_Model_Quote_Item', $quoteItemObject );

		$quoteAddressItemObject = Mage::getModel('sales_quote_address/item');
		$this->assertInstanceOf('Mage_Sales_Model_Quote_Address_Item', $quoteAddressItemObject);
		$this->assertInstanceOf('TrueAction_Eb2c_Core_Model_Quote_Address_Item', $quoteAddressItemObject );
	}
}
