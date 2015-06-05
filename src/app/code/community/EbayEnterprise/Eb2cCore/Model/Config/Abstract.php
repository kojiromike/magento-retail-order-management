<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class EbayEnterprise_Eb2cCore_Model_Config_Abstract implements EbayEnterprise_Eb2cCore_Model_Config_Interface
{
    /**
     * Associative array of configKey => configPath
     * @var array
     */
    protected $_configPaths;

    /**
     * Determines if this config model knows about the given key
     * @param string $configKey
     * @return bool
     */
    public function hasKey($configKey)
    {
        return isset($this->_configPaths[$configKey]);
    }

    /**
     * Get the config path for the given known key
     * @param string $configKey
     * @return string
     */
    public function getPathForKey($configKey)
    {
        return $this->_configPaths[$configKey];
    }
}
