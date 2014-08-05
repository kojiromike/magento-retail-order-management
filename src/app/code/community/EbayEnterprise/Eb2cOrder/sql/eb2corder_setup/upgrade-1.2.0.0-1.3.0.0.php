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
 * Installing 'unsubmitted' status associated with a state 'new' for the
 * purpose of flagging when an order fail to submit successfully to the ROM API.
 * The initial status and state of the order will be 'unsubmitted' and 'new', respectively.
 * When the ROM API return success the order status will be set to 'pending'
 * while it state remained unchanged as 'new'. However,
 * when the ROM API fail due to some transient failure, the status will be set
 * to 'unsubmitted'. When order retry run it will look for order with state
 * 'new' and status 'unsubmitted'.
 */

Mage::log(sprintf('[%s] Upgrading Eb2cOrder 1.2.0.0 -> 1.3.0.0', get_class($this)), Zend_Log::INFO);

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$status = 'unsubmitted';
$label = 'Unsubmitted';
$isDefault = 1;

$statusFields = array('status', 'label');
$statusValues = array($status, $label);
$statusTbl = $installer->getTable('sales/order_status');
$conn->insertArray($statusTbl, $statusFields, array(array_combine($statusFields, $statusValues)));

$stateFields = array('status', 'state', 'is_default');
$stateValues = array($status, Mage_Sales_Model_Order::STATE_NEW, $isDefault);
$stateTbl = $installer->getTable('sales/order_status_state');
$conn->insertArray($stateTbl, $stateFields, array(array_combine($stateFields, $stateValues)));

$installer->endSetup();
