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
 * Session storage for inventory related data.
 */
class EbayEnterprise_Inventory_Model_Session extends Mage_Core_Model_Session_Abstract
{
    /**
     * Initialize the session.
     */
    protected function _construct()
    {
        $this->init('ebayenterprise_inventory');
    }

    /**
     * Get quantity response results stored from the last
     * quantity request. Will not return any results that have expired.
     *
     * @return EbayEnterprise_Inventory_Model_Quantity_Results
     */
    public function getQuantityResults()
    {
        $results = $this->getData('quantity_results');
        return $results && !$results->isExpired() ? $results : null;
    }

    /**
     * @param EbayEnterprise_Inventory_Model_Quantity_Results
     * @return self
     */
    public function setQuantityResults($quantityResults)
    {
        return $this->setData('quantity_results', $quantityResults);
    }
}
