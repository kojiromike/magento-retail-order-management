<?php
Mage::log(sprintf('[%s] Upgrading Eb2cOrder 1.0.0.22 -> 1.1.0.0', __CLASS__), Zend_Log::INFO);
$installer = new Mage_Sales_Model_Resource_Setup('core_setup');
$installer->startSetup();
try{
	$installer->addAttribute('order', 'eb2c_order_create_request', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'visible' => true,
		'required' => false,
	));
} catch (Exception $e) {
	Mage::logException($e);
}
$installer->endSetup();
