<?php
class EbayEnterprise_Eb2cFraud_Block_Jsc extends Mage_Core_Block_Template
{
	const EB2C_FRAUD_COLLECTOR_PATH = 'default/eb2cfraud/collectors';
	protected $_template = 'eb2cfraud/jsc.phtml';
	/**
	 * Upon construction, get a single, random JavaScript collector to add either
	 * from an injected set of collectors or via the collectors configured in
	 * the jsc.xml config file. Add collector url, JS call and form field
	 * to populate to the block.
	 */
	protected function _construct()
	{
		parent::_construct();
		// 41st Parameter needs a new random value at every page load.
		$this->setCacheLifetime(0);
		$collectors = $this->getCollectors() ?: json_decode(
			Mage::getConfig()
				->loadModulesConfiguration('jsc.xml')
				->getNode(static::EB2C_FRAUD_COLLECTOR_PATH),
			true
		);
		$collector = $collectors[array_rand($collectors)];
		$this->addData(array(
			'collector_url' => Mage::helper('eb2cfraud')->getJscUrl() . '/' . $collector['filename'],
			'call' => sprintf(
				"%s('%s');", $collector['function'], $collector['formfield']
			),
			'field' => sprintf(
				'<input type="hidden" name="%1$s" id="%1$s" />', $collector['formfield']
			),
			'mapping_field' => sprintf(
				'<input type="hidden" name="%1$s" id="%1$s" value="%2$s" />',
				EbayEnterprise_Eb2cFraud_Helper_Data::JSC_FIELD_NAME, $collector['formfield']
			),
		));
	}
}
