<?php
/**
 */
class TrueAction_Taxes_Test_Helper_DataTests extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @test
	 * */
	public function testRewrite()
	{
		$hlpr = Mage::helper('tax');
		$this->assertSame(
			get_class(Mage::helper('taxes')),
			get_class($hlpr)
		);
	}
}