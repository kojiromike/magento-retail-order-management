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


class EbayEnterprise_Eb2cOrder_Overrides_Model_Eav_Entity_Type
	extends Mage_Eav_Model_Entity_Type
{
	/**
	 * Prepend Exchange client order id prefix.
	 *
	 * @param int $storeId
	 * @return string
	 */
	public function fetchNewIncrementId($storeId=null)
	{
		$incrementId = trim(parent::fetchNewIncrementId($storeId));
		$cfg = Mage::helper('eb2ccore')->getConfigModel();
		$ebcPrefix = trim($cfg->clientOrderIdPrefix);
		if ($ebcPrefix !== '' && $incrementId !== '') {
			$incrementId = $ebcPrefix . substr($incrementId, 1, strlen($incrementId)-1);
		}

		return $incrementId;
	}
}
