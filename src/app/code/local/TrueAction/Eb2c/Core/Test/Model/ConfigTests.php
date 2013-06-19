<?php

/**
 * Test the abstract config model which does the majority of work implementing
 * the config model interface required by the TrueAction_Eb2c_Core_Helper_Config
 */
class TrueAction_Eb2c_Core_Test_Model_ConfigTests extends EcomDev_PHPUnit_Test_Case
{

	protected function _createConfigStub()
	{
		$stub = $this->getMock('TrueAction_Eb2c_Core_Model_Config_Abstract');

		$keyMap = array(array('catalog_id', true), array('foo_bar_baz', false));
		$stub->expects($this->any())
			->method('hasKey')
			->will($this->returnValueMap($keyMap));

		$pathMap = array(array('catalog_id', 'eb2c/core/catalog_id'));
		$stub->expects($this->any())
			->method('getPathForKey')
			->will($this->returnValueMap($pathMap));

		return $stub;
	}

	/**
	 * Test determining if a config model knows about a key
	 * @test
	 */
	public function testConfigModelHasKey()
	{
		$configModel = $this->_createConfigStub();
		$this->assertTrue($configModel->hasKey('catalog_id'));
		$this->assertFalse($configModel->hasKey('foo_bar_baz'));
	}

	/**
	 * Test getting path for a known key
	 * @test
	 */
	public function testConfigModelGetPath()
	{
		$configModel = $this->_createConfigStub();
		$this->assertSame($configModel->getPathForKey('catalog_id'), 'eb2c/core/catalog_id');
	}

}
