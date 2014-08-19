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
 * Upgrade to add eb2c status time stamp to order
 */
$installer = $this;
$installer->startSetup();
$typeTextOptions = array (
	'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
	'visible'  => true,
	'required' => false,
);

$typeTimestampOptions = array (
	'type'     => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
	'visible'  => true,
	'required' => false,
);

$eb2cStatusAttributes = array(
	array(
		'name' => 'status_applied',
		'options' => array(
			'type'     => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
			'visible'  => true,
			'required' => false,
			'comment'  => 'Timestamp when the status was applied, local Mage time',
		)
	), array(
		'name'    => 'status_timestamp',
		'options' => array(
			'type'     => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
			'visible'  => true,
			'required' => false,
			'comment'  => 'Timestamp from the remote system',
		)
	), array(
		'name'    => 'status_type',
		'options' => array (
			'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
			'visible'  => true,
			'required' => false,
			'comment'  => 'Text value of status from the remote system',
		)
	)
);

$pfx = 'eb2c_order_';
$table = $installer->getTable('sales/order');
foreach ($eb2cStatusAttributes as $a) {
	$columnName = $pfx . $a['name'];
	$installer
		->getConnection()
		->addColumn($table, $columnName, $a['options']);
}
$installer->endSetup();

