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

class EbayEnterprise_Address_Test_Block_Address_RendererTest extends EcomDev_PHPUnit_Test_Case
{
    public function setUp()
    {
        $this->_mockConfig();
    }

    /**
     * Mock out the config helper.
     */
    protected function _mockConfig()
    {
        $mock = $this->getModelMockBuilder('eb2ccore/config_registry')
            ->disableOriginalConstructor()
            ->setMethods(array('__get', 'addConfigModel', 'getConfig'))
            ->getMock();
        $mockConfig = array(
            array('addressFormat', '{{mock_config}} address {{format}}'),
        );
        $mock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap($mockConfig));
        $mock->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue('{{mock_config}} address {{format}}'));
        // make sure chaining works when adding config models
        $mock->expects($this->any())
            ->method('addConfigModel')
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'eb2ccore/config_registry', $mock);
    }

    /**
     * Test the setup of the rendered "type" data
     */
    public function testInitType()
    {
        $config = Mage::getModel('eb2ccore/config_registry');
        $renderer = new EbayEnterprise_Address_Block_Address_Renderer();
        $renderer->initType($config->addressFormat);
        $type = $renderer->getType();
        $this->assertInstanceOf(
            'Varien_Object',
            $type,
            'should have a magic "type" data element which is a Varien_Object'
        );
        $this->assertSame(
            $type->getDefaultFormat(),
            $config->addressFormat,
            'renderer type should have a magic "default_format" data element which should be the config template from config'
        );
        $this->assertTrue(
            $type->getHtmlEscape(),
            'renderer type should have magic "html_escape" data element which should be set to true'
        );
    }
}
