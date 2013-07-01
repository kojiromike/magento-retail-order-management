<?php

class TrueAction_Eb2c_Address_Block_Address_Renderer
	extends Mage_Customer_Block_Address_Renderer_Default
{

	protected function _construct()
	{
		parent::_construct();
		$this->initType();
	}

	/**
	 * Set the default for the renderer type.
	 * @return TrueAction_Eb2c_AddressFrontend_Block_Address_Renderer - $this
	 */
	public function initType()
	{
		$format = Mage::helper('eb2ccore/config')
			->addConfigModel(Mage::getSingleton('eb2caddress/config'))
			->addressFormat;
		$type = new Varien_Object();
		$type->setCode('address_verification')
			->setTitle('Address Verification Candidate')
			->setDefaultFormat($format)
			->setHtmlEscape(true);
		$this->setType($type);
		return $this;
	}

}
