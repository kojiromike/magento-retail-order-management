<?php

/**
 * Single point of access for retrieving configuration values.
 * Looks up configuration paths and values via configuration models registered with the helper.
 */
class TrueAction_Eb2c_Core_Helper_Config extends Mage_Core_Helper_Abstract
{

	/**
	 * The default value to use when looking up config values.
	 * @var null|string|bool|int|Mage_Core_Model_Store
	 */
	protected $_store = null;
	/**
	 * Array of registered config models used to associate config paths with keys.
	 * @var TrueAction_Eb2c_Core_Model_Config_Interface[]
	 */
	protected $_configModels = array();

	/**
	 * Add a new config settings model to the collection and update the list of known config paths
	 * @param TrueAction_Eb2c_Core_Model_Config_Interface $configModel
	 * @return TrueAction_Eb2c_Core_Helper_Config $this
	 */
	public function addConfigModel(TrueAction_Eb2c_Core_Model_Config_Interface $configModel) {
		array_unshift($this->_configModels, $configModel);
		return $this;
	}

	/**
	 * Set a default store to use when getting config values
	 * @param null|string|bool|int|Mage_Core_Model_Store $store
	 * @return TrueAction_Eb2c_Core_Helper_Config $this
	 */
	public function setStore($store)
	{
		$this->_store = $store;
		return $this;
	}

	/**
	 * Get the default store
	 * @return null|string|bool|int|Mage_Core_Model_Store
	 */
	public function getStore()
	{
		return $this->_store;
	}

	/**
	 * Search through registered config models for one that knows about the key
	 * and get the actual config path from it for the lookup.
	 * @param string $configKey
	 * @param null|string|bool|int|Mage_Core_Model_Store $store
	 * @param boolean $asFlag
	 * @return string|boolean
	 * @throws Exception Raised if the config path is not found.
	 */
	protected function _getStoreConfigValue($configKey, $store, $asFlag)
	{
		foreach ($this->_configModels as $configModel) {
			if ($configModel->hasKey($configKey)) {
				$configMethod = $asFlag ? 'getStoreConfigFlag' : 'getStoreConfig';
				return Mage::$configMethod($configModel->getPathForKey($configKey), $store);
			}
		}
		Mage::throwException('Configuration path specified by ' . $key . ' was not found.');
	}

	/**
	 * Get the configuration value represented by the given configKey
	 * @param string $configKey
	 * @param null|string|bool|int|Mage_Core_Model_Store $store
	 * @return string
	 */
	public function getConfigFlag($configKey, $store = null)
	{
		// if a value is given store, use it, even if it is null/false/empty string/whatever
		$store = count(func_get_args()) > 1 ? $store : $this->getStore();
		return $this->_getStoreConfigValue($configKey, $store, true);
	}

	/**
	 * Get the configuration flag value represented by the given configKey
	 * @param string $configKey
	 * @param null|string|bool|int|Mage_Core_Model_Store $store
	 * @return boolean
	 */
	public function getConfig($configKey, $store = null)
	{
		// if a value is given store, use it, even if it is null/false/empty string/whatever
		$store = count(func_get_args()) > 1 ? $store : $this->getStore();
		return $this->_getStoreConfigValue($configKey, $store, false);
	}

	/**
	 * Magic "get" method. Will convert the method name to a config key
	 * and use it to retrieve a config value.
	 * @param null|string|bool|int|Mage_Core_Model_Store $store
	 * @return string
	 */
	// get.+($store);

	/**
	 * Magic "is" method.
	 * Will convert the method name to a config key and use
	 * it to get a config flag.
	 * @param null|string|bool|int|Mage_Core_Model_Store $store
	 * @return boolean
	 */
	// is.+($store)

	/**
	 * Convert the magic method name, minus the "get"/"is" to a
	 * potential config key.
	 * Changes CamelCase to underscore_words
	 * @param string $name
	 * @return string
	 */
	protected function _magicNameToConfigKey($name)
	{
		return strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $name));
	}

	/**
	 * Catch any unknown function methods calls to try to magically convert "get" methods to retrieve config values.
	 * @param string $name The called method
	 * @param array $args Any arguments passed to the function
	 * @return string|boolean
	 * @throws Exception if the method name is not a "get" method or the found config key does not map to a know path
	 */
	public function __call($name, $args)
	{
		// check if the method $name starts with "is" or "get" and capture which one
		$matches = array();
		preg_match("/^(?:is|get)/", $name, $matches);
		// "get" or "is" method type
		$type = $matches[0];
		if ($type) {
			$configKey = $this->_magicNameToConfigKey(substr($name, strlen($type)));
			$store = (isset($args[0])) ? $args[0] : $this->getStore();
			try {
				// retrieve the actual config value, passing the $isFlag arg as true if the method name started with 'is'
				return $this->_getStoreConfigValue($configKey, $store, ($type === 'is'));
			} catch (Exception $e) {
				Mage::log($e->getMessage(), Zend_Log::WARN);
			}
		}
		Mage::throwException('Invalid method ' . get_class($this) . '::' . $name . '(' . print_r($args, 1) . ')');
	}

}