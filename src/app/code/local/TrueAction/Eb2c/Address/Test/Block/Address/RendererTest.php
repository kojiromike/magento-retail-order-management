<?php

class TrueAction_Eb2c_Address_Test_Block_Address_RendererTest
	extends EcomDev_PHPUnit_Test_Case
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
			->setMethods(array('__get', 'addConfigModel'))
			->getMock();
		$mockConfig = array(
			array('addressFormat', '{{mock_config}} address {{format}}'),
		);
		$mock->expects($this->any())
			->method('__get')
			->will($this->returnValueMap($mockConfig));
		// make sure chaining works when adding config models
		$mock->expects($this->any())
			->method('addConfigModel')
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2ccore/config_registry', $mock);
	}

	public function testInitType()
	{
		$config = Mage::getModel('eb2ccore/config_registry');
		$renderer = new TrueAction_Eb2c_Address_Block_Address_Renderer();
		$type = $renderer->getType();
		// should have a magic "type" data element which is a Varien_Object
		$this->assertTrue($type instanceof Varien_Object);
		// renderer type should have a magic 'default_format' data element which should
		// be the config template from config
		$this->assertSame($type->getDefaultFormat(), $config->addressFormat);
		// renderer type should have magic 'html_escape' data element which should be set to true
		$this->assertTrue($type->getHtmlEscape());
	}

}