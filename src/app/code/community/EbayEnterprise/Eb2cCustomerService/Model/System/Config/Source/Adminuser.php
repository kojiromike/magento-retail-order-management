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


class EbayEnterprise_Eb2cCustomerService_Model_System_Config_Source_Adminuser
{
	/**
	 * Get option array of all admin user accounts for use in a select menu.
	 * Uses the admin username for the label and user id for the value.
	 * @return array
	 */
	public function toOptionArray()
	{
		return array_map(
			array($this, '_userMap'),
			Mage::getResourceModel('admin/user_collection')
				->addFieldToSelect(array('username', 'user_id'))
				->getItems()
		);
	}
	/**
	 * Given a admin user, return a option map with the username as the label
	 * and user id as the value.
	 * @param  Mage_Admin_Model_User $user
	 * @return array
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 */
	private function _userMap(Mage_Admin_Model_User $user)
	{
		return array('label' => $user->getUsername(), 'value' => $user->getId());
	}
}
