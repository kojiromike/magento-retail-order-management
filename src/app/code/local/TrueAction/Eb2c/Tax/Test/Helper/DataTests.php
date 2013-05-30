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
			'TrueAction_Eb2c_Tax_Helper_Data',
			get_class($hlpr)
		);
	}
}