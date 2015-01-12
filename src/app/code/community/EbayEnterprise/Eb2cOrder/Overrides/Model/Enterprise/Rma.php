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

class EbayEnterprise_Eb2cOrder_Overrides_Model_Enterprise_Rma
	extends Enterprise_Rma_Model_Rma
{
	/**
	 * Sending email with RMA data
	 *
	 * @return Enterprise_Rma_Model_Rma
	 */
	public function sendNewRmaEmail()
	{
		if (Mage::helper('eb2corder')->getConfigModel()->transactionalEmailer === 'eb2c') {
			return $this;
		} else {
			return parent::sendNewRmaEmail();
		}
	}

	/**
	 * Sending authorizing email with RMA data
	 *
	 * @return Enterprise_Rma_Model_Rma
	 */
	public function sendAuthorizeEmail()
	{
		if (Mage::helper('eb2corder')->getConfigModel()->transactionalEmailer === 'eb2c') {
			return $this;
		} else {
			return parent::sendAuthorizeEmail();
		}
	}
}
