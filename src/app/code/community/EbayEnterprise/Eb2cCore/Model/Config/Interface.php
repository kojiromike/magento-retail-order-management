<?php
interface EbayEnterprise_Eb2cCore_Model_Config_Interface {
	/**
	 * Indicates this model knows about the given config key
	 *
	 * @param $configKey
	 * @return bool
	 */
	public function hasKey($configKey);

	/**
	 * Return the config path for a known key
	 *
	 * @param $configKey
	 * @return string
	 */
	public function getPathForKey($configKey);
}
