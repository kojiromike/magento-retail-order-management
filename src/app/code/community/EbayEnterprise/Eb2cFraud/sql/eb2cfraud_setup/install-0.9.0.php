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
 * Add fields to quote/ order for JSC 41st Parameter and associated orderContext fields
 *
 * @var Mage_Sales_Model_Resource_Setup $installer
 */
$installer = $this;
$installer->startSetup();
$entities = array('order', 'quote');

$typeTextOptions = array (
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'visible' => true,
    'required' => false,
);

$fraudAttributes = array(
    array( 'name' => 'char_set',        'options' => $typeTextOptions),
    array( 'name' => 'content_types',   'options' => $typeTextOptions),
    array( 'name' => 'encoding',        'options' => $typeTextOptions),
    array( 'name' => 'host_name',       'options' => $typeTextOptions),
    array( 'name' => 'ip_address',      'options' => $typeTextOptions),
    array( 'name' => 'javascript_data',     'options' => $typeTextOptions),
    array( 'name' => 'language',        'options' => $typeTextOptions),
    array( 'name' => 'referrer',        'options' => $typeTextOptions),
    array( 'name' => 'session_id',      'options' => $typeTextOptions),
    array( 'name' => 'user_agent',      'options' => $typeTextOptions),
);

$pfx = 'eb2c_fraud_';

foreach ($entities as $entity) {
    foreach ($fraudAttributes as $a) {
        $installer->addAttribute($entity, $pfx . $a['name'], $a['options']);
    }
}

$installer->endSetup();
