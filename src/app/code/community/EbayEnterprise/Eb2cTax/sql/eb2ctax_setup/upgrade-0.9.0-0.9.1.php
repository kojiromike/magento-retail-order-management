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

$installer = $this;
$installer->startSetup();

$taxTable = $installer->getTable('eb2ctax/response_quote');
$installer->getConnection()
	->addColumn($taxTable, 'tax_type', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'TaxType reported by the tax service',
	));
$installer->getConnection()
	->addColumn($taxTable, 'taxability', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'Taxability of the item',
	));
$installer->getConnection()
	->addColumn($taxTable, 'jurisdiction', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'TaxQuote Jurisdiction',
	));
$installer->getConnection()
	->addColumn($taxTable, 'jurisdiction_id', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'TaxQuote Jurisdiction Id',
	));
$installer->getConnection()
	->addColumn($taxTable, 'jurisdiction_level', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'TaxQuote Jurisdiction Level',
	));
$installer->getConnection()
	->addColumn($taxTable, 'imposition', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'TaxQuote Imposition',
	));
$installer->getConnection()
	->addColumn($taxTable, 'imposition_type', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 128,
		'nullable' => true,
		'comment' => 'TaxQuote Imposition type',
	));
$installer->endSetup();
