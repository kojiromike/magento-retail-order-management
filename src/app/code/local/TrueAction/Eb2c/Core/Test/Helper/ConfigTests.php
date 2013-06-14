<?php

class Config_Stub implements TrueAction_Eb2c_Core_Model_Config_Interface
{

	public function hasKey($key)
	{
		return $key === "catalog_id" || $key === "api_key" || $key === "test_mode";
	}

	public function getPathForKey($key)
	{
		$paths = array("catalog_id" => "eb2c/core/catalog_id", "api_key" => "eb2c/core/api_key", "test_mode" => "eb2c/core/test_mode");
		return $paths[$key];
	}

}

class Alt_Config_Stub implements TrueAction_Eb2c_Core_Model_Config_Interface
{

	public function hasKey($key)
	{
		return $key === "catalog_id" || $key === "another_setting";
	}

	public function getPathForKey($key)
	{
		$paths = array("catalog_id" => "eb2c/another/catalog_id", "another_setting" => "eb2c/another/module/setting");
		return $paths[$key];
	}

}

class TrueAction_Eb2c_Core_Test_Helper_ConfigTest extends EcomDev_PHPUnit_Test_Case
{

	/**
	 * @test
	 * @loadFixture configData
	 */
	public function testGetConfig()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->addConfigModel(new Config_Stub());

		// ensure a value is returned
		$this->assertNotNull($config->getConfig('catalog_id'));

		// when no store id is set, should use whatever the default is
		$this->assertSame($config->getConfig('catalog_id'), Mage::getStoreConfig('eb2c/core/catalog_id'));

		// when explicitly passing a storeId, should return value for that store
		$this->assertSame($config->getConfig('api_key', 2), Mage::getStoreConfig('eb2c/core/api_key', 2));

		// when store id is set on config object, will use that value for the store id
		$config->setStore(2);
		$this->assertSame($config->getConfig('catalog_id'), Mage::getStoreConfig('eb2c/core/catalog_id', 2));

		// can still use an explicit store id which should override the one set on the store
		$this->assertSame($config->getConfig('api_key', 3), Mage::getStoreConfig('eb2c/core/api_key', 3));

		// can even explicitly set store id to null
		$this->assertSame($config->getConfig('catalog_id', null), Mage::getStoreConfig('eb2c/core/catalog_id'));

		// indicate the config is a "flag" to explicitly return a boolean value
		$this->assertSame($config->getConfigFlag('test_mode', 1), Mage::getStoreConfigFlag('eb2c/core/test_mode', 1));
	}

	/**
	 * @test
	 * @loadFixture configData
	 */
	public function testGetConfigMagic()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->addConfigModel(new Config_Stub());

		// ensure a value is returned
		$this->assertNotNull($config->getCatalogId());

		// when no store id is set, should use whatever the default is
		$this->assertSame($config->getCatalogId(), Mage::getStoreConfig('eb2c/core/catalog_id'));

		// when explicitly passing a storeId, should return value for that store
		$this->assertSame($config->getApiKey(2), Mage::getStoreConfig('eb2c/core/api_key', 2));

		// when store id is set on config object, will use that value for the store id
		$config->setStore(2);
		$this->assertSame($config->getCatalogId(), Mage::getStoreConfig('eb2c/core/catalog_id', 2));

		// can even explicitly set store id to null
		$this->assertSame($config->getCatalogId(null), Mage::getStoreConfig('eb2c/core/catalog_id'));

		// can still use an explicit store id which should override the one set on the store
		$this->assertSame($config->getApiKey(3), Mage::getStoreConfig('eb2c/core/api_key', 3));

		// magic "is" methods will explicitly return the value as a boolean
		$this->assertSame($config->isTestMode(), Mage::getStoreConfigFlag('eb2c/core/test_mode', 2));
		// can override store when using the magic 'is' methods
		$this->assertSame($config->isTestMode(1), Mage::getStoreConfigFlag('eb2c/core/test_mode', 1));
	}

	/**
	 * @test
	 * @loadFixture configData
	 */
	public function testMagicMultiConfig()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->addConfigModel(new Config_Stub())
			->addConfigModel(new Alt_Config_Stub());

		// should get some config value for both of these
		// this will come from the first Config_Stub
		$this->assertNotNull($config->getApiKey());
		// this will come from teh second Alt_Config_Stub
		$this->assertNotNull($config->getAnotherSetting());

		// when no store id is set, should use whatever the default is
		$this->assertSame($config->getApiKey(), Mage::getStoreConfig('eb2c/core/api_key'));

		// should be able to get config added by either settings model
		$this->assertSame($config->getAnotherSetting(), Mage::getStoreConfig('eb2c/another/module/setting'));

		// keys can collide...last added are used
		// this path is in the first config model added and will not be used
		$this->assertNotSame($config->getCatalogId(), Mage::getStoreConfig('eb2c/core/catalog_id'));
		// this is in the second config model and will be used
		$this->assertSame($config->getCatalogId(), Mage::getStoreConfig('eb2c/another/catalog_id'));
	}

	/**
	 * If getConfig is called and the key is not found, an exception should be raised.
	 *
	 * @test
	 * @expectedException Exception
	 */
	public function testConfigNotFoundExceptions()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->getConfig('nonexistent_config');
	}

	/**
	 * An exception should be thrown if getConfigFlag is called and the key is not found.
	 *
	 * @test
	 * @expectedException Exception
	 */
	public function testConfigFlagNotFoundExceptions()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->getConfigFlag('nonexistent_config');
	}

	/**
	 * Calling a magic getter that doesn't match a config key should throw an error.
	 *
	 * @test
	 * @expectedException Exception
	 */
	public function testMagicConfigNotFoundExceptions()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->getNonexistentConfig();
	}

	/**
	 * Calling magic 'is' method that doesn't return a config key should throw an error.
	 *
	 * @test
	 * @expectedException Exception
	 */
	public function testMagicIsNotFoundException()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->isNonexistentConfig();
	}

	/**
	 * __call should only catch methods starting with "get", rest should raise exceptions
	 *
	 * @test
	 * @expectedException Exception
	 */
	public function testUnknownMagicMethod()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->notRealMethod();
	}

	/**
	 * @test
	 * @loadFixture configData
	 */
	public function testMagicPropConfig()
	{
		$config = Mage::helper('eb2ccore/config');
		$config->addConfigModel(new Config_Stub())
			->addConfigModel(new Alt_Config_Stub());

		// should get some config value for both of these
		// this will come from the first Config_Stub
		$this->assertNotNull($config->apiKey);
		// this will come from the second Alt_Config_Stub
		$this->assertNotNull($config->anotherSetting);

		// when no store id is set, should use whatever the default is
		$this->assertSame($config->apiKey, Mage::getStoreConfig('eb2c/core/api_key'));

		// should be able to get config added by either settings model
		$this->assertSame($config->anotherSetting, Mage::getStoreConfig('eb2c/another/module/setting'));

		// keys can collide...last added are used
		// this path is in the first config model added and will not be used
		$this->assertNotSame($config->catalogId, Mage::getStoreConfig('eb2c/core/catalog_id'));
		// this is in the second config model and will be used
		$this->assertSame($config->catalogId, Mage::getStoreConfig('eb2c/another/catalog_id'));
	}
}

