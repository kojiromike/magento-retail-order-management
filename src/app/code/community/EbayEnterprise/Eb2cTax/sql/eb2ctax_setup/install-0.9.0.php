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

Mage::log(sprintf('[%s] Installing Eb2cTax 0.9.0', get_class($this)), Zend_Log::DEBUG);

/** @var Mage_Core_Model_Resource_Setup $installer */
/** @var Varien_Db_Adapter_Interface $conn */
$installer = $this;
$installer->startSetup();
$conn->dropTable($installer->getTable('eb2ctax/response_quote'));
$table = $conn
	->newTable($installer->getTable('eb2ctax/response_quote'))
	->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned' => true,
		'identity' => true,
		'nullable' => false,
		'primary'  => true,
	), 'TaxQuote id')
	->addColumn('quote_item_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Quote Item Id')
	->addColumn('type', Varien_Db_Ddl_Table::TYPE_INTEGER, 1, array(
		'unsigned' => true,
	), 'TaxQuote type')
	->addColumn('code', Varien_Db_Ddl_Table::TYPE_TEXT, 128, array(
		'nullable' => true,
	), 'Code')
	->addColumn('situs', Varien_Db_Ddl_Table::TYPE_TEXT, 128, array(
		'nullable' => true,
	), 'Situs')
	->addColumn('effective_rate', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(20, 6), array(), 'Effective Rate')
	->addColumn('taxable_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(20, 6), array(), 'Taxable Amount')
	->addColumn('calculated_tax', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(20, 6), array(), 'Calculated Tax')
	->addColumn('quote_address_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(), 'Quote Address Item Id');

$conn->createTable($table);
$installer->endSetup();
