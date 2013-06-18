<?php

/**
 * Test the abstract config model which does the majority of work implementing
 * the config model interface required by the TrueAction_Eb2c_Core_Helper_Config
 */
class TrueAction_Eb2c_Core_Test_Model_ConfigTests extends EcomDev_PHPUnit_Test_Case
{

	/**
	 * Test determining if a config model knows about a key
	 * @test
	 */
	public function testConfigModelHasKey()
	{
		$configModel = new Concrete_Config_Model_Stub();
		$this->assertTrue($configModel->hasKey('catalog_id'));
		$this->assertFalse($configModel->hasKey('foo_bar_baz'));
	}

	/**
	 * Test getting path for a known key
	 * @test
	 */
	public function testConfigModelGetPath()
	{
		$configModel = new Concrete_Config_Model_Stub();
		$this->assertSame($configModel->getPathForKey('catalog_id'), 'eb2c/core/catalog_id');
	}

}

/**
 * Simple implementation of the abstract class for testing.
 */
class Concrete_Config_Model_Stub extends TrueAction_Eb2c_Core_Model_Config_Abstract
{
	protected $_configPaths = array(
		"catalog_id" => "eb2c/core/catalog_id",
	);
}
