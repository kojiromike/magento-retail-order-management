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

class EbayEnterprise_Order_Model_Search_Process_Response_Collection
	extends Varien_Data_Collection
	implements EbayEnterprise_Order_Model_Search_Process_Response_ICollection
{
	/**
	 * @see EbayEnterprise_Order_Model_Search_Process_Response_ICollection::sort()
	 */
	public function sort()
	{
		usort($this->_items, [$this,'_sortOrdersMostRecentFirst']);
		return $this;
	}

	/**
	 * Sorting by most recent order first.
	 *
	 * @param  Varien_Object
	 * @param  Varien_Object
	 * @return bool
	 */
	protected function _sortOrdersMostRecentFirst(Varien_Object $a, Varien_Object $b)
	{
		return $a->getOrderDate() < $b->getOrderDate();
	}
}
