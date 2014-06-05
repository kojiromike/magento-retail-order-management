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

class EbayEnterprise_Eb2cPayment_Model_Adminhtml_Comment
{
	const COMMENT_VERBIAGE = 'EbayEnterprise_Eb2cPayment_Admin_System_Config_PBridge_Comments';

	/**
	 * return the comment content
	 * @return string
	 */
	public function getCommentText()
	{
		return sprintf(Mage::helper('eb2cpayment')->__(self::COMMENT_VERBIAGE), $this->_getUrl());
	}

	/**
	 * return the payment method configuration section URL
	 * @return string
	 */
	protected function _getUrl()
	{
		return Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit', array('section' => 'payment'));
	}
}
