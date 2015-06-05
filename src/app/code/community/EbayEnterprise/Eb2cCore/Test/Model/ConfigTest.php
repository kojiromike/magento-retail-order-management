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

/**
 * Test the abstract config model which does the majority of work implementing
 * the config model interface required by the EbayEnterprise_Eb2cCore_Helper_Config
 */
class EbayEnterprise_Eb2cCore_Test_Model_ConfigTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * A config model knows about a key.
     */
    public function testConfigModelHasKey()
    {
        $configModel = new ConfigStub();
        $this->assertTrue($configModel->hasKey('catalog_id'));
        $this->assertFalse($configModel->hasKey('foo_bar_baz'));
    }

    /**
     * A config model can get the correct path for a known key.
     */
    public function testConfigModelGetPath()
    {
        $configModel = new ConfigStub();
        $this->assertSame($configModel->getPathForKey('catalog_id'), 'eb2c/core/catalog_id');
    }
}

/**
 * Simple implementation of the config abstract model.
 * Used to test the concrete implementations in the abstract class.
 *
 * @codeCoverageIgnore
 */
class ConfigStub extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
    protected $_configPaths = array('catalog_id' => 'eb2c/core/catalog_id');
}
