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

class EbayEnterprise_Eb2cOrder_Model_Detail_Address
	extends Mage_Sales_Model_Order_Address
{
	protected function _construct()
	{
		parent::_construct();
		$this->setIdFieldName('id');
		$this->setStreet(implode("\n", array_filter(array(
			$this->getData('street1'),
			$this->getData('street2'),
			$this->getData('street3'),
			$this->getData('street4')
		))));
		$this->setName(implode(' ', array_filter(array(
			$this->getFirstname(),
			$this->getLastname()
		))));
	}
}
