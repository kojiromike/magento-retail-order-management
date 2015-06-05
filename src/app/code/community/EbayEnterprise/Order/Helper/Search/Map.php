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

use eBayEnterprise\RetailOrderManagement\Payload\Customer\IOrderSummary;

class EbayEnterprise_Order_Helper_Search_Map
{
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $_coreHelper;

    public function __construct()
    {
        $this->_coreHelper = Mage::helper('eb2ccore');
    }

    /**
     * Get a unique value.
     *
     * @return string
     */
    public function getUniqueValue()
    {
        return uniqid('OCS_');
    }

    /**
     * Get a string value.
     *
     * @param  IOrderSummary
     * @param  string
     * @return mixed
     */
    public function getStringValue(IOrderSummary $summary, $getter)
    {
        return $summary->$getter();
    }

    /**
     * Get a date/time value.
     *
     * @param  IOrderSummary
     * @param  string
     * @return string | null
     */
    public function getDatetimeValue(IOrderSummary $summary, $getter)
    {
        $value = $this->getStringValue($summary, $getter);
        return ($value instanceof DateTime) ? $value->format('c') : null;
    }

    /**
     * Get a float value.
     *
     * @param  IOrderSummary
     * @param  string
     * @return mixed
     */
    public function getFloatValue(IOrderSummary $summary, $getter)
    {
        return (float) $this->getStringValue($summary, $getter);
    }

    /**
     * Get a boolean value.
     *
     * @param  IOrderSummary
     * @param  string
     * @return mixed
     */
    public function getBooleanValue(IOrderSummary $summary, $getter)
    {
        return $this->_coreHelper->parseBool($this->getStringValue($summary, $getter));
    }
}
