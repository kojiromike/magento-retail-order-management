<?php
Mage::log(sprintf('[ %s ] Upgrade Eb2cTax %s', get_class($this), basename(__FILE__)), Zend_Log::DEBUG);

$installer = $this;
$installer->startSetup();

$taxTable = $installer->getTable('eb2ctax/response_quote');
$installer->getConnection()
	->addColumn($taxTable, 'tax_header_error', array(
		'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
		'nullable' => true,
		'default' => false,
		'comment' => 'Tax Header Error (true when Any CalculationError Received)',
	));
$installer->endSetup();
