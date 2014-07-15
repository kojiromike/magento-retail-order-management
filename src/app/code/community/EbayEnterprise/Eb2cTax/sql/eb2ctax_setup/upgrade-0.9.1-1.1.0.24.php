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
