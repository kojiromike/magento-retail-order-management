<?php
/**
 */
class TrueAction_Eb2c_Tax_Test_Overrides_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * @test
	 */
	public function testRewrite()
	{
		$hlpr = Mage::helper('tax');
		$this->assertSame(
			'TrueAction_Eb2c_Tax_Overrides_Helper_Data',
			get_class($hlpr)
		);
	}

	/**
	 * @test
	 */
	public function testNamespaceUri()
	{
		$this->assertSame(
			'http://api.gsicommerce.com/schema/checkout/1.0',
			Mage::helper('tax')->getNamespaceUri()
		);
	}

	/**
	 * @test
	 */
	public function testSendRequest()
	{
		$this->markTestIncomplete('need to update for changes to core');
		$request = $this->getModelMock('eb2ctax/request', array('getDocument'));
		$request->expects($this->any())
			->method('getDocument')
			->will($this->returnValue(new TrueAction_Dom_Document()));
		$coreHelper = $this->getHelperMock('eb2ccore/data', array('callApi', 'apiUri'));
		$coreHelper->expects($this->any())
			->method('callApi')
			->will($this->returnValue(''));
		$coreHelper->expects($this->any())
			->method('apiUri')
			->will($this->returnValue('https://som.u.ri'));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelper);
		$response = Mage::helper('tax')->sendRequest($request);
		$this->assertFalse($response->isValid());
	}

	/**
	 * @test
	 */
	public function testGetVatInclusivePricingFlag()
	{
		$val = Mage::helper('tax')->getVatInclusivePricingFlag();
		$this->assertFalse($val);
	}

	/**
	 * @test
	 * @loadFixture vatInclusivePricingEnabled.yaml
	 */
	public function testGetVatInclusivePricingFlagEnabled()
	{
		$val = Mage::helper('tax')->getVatInclusivePricingFlag();
		$this->assertTrue($val);
	}
}