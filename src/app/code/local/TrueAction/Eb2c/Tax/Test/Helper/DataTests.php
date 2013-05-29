<?php
/**
 */
class TrueAction_Eb2c_Tax_Test_Helper_DataTests extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @test
	 * */
	public function testRewrite()
	{
		$hlpr = Mage::helper('tax');
		$this->assertSame(
			get_class(Mage::helper('tax')),
			get_class($hlpr)
		);
	}
}