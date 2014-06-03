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


class EbayEnterprise_Eb2cCustomerService_Adminhtml_CsrloginController
	extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Simply provide an entry point for Epiphany logins. If the user is logged
	 * in during preDispatch events, the user will automatically be redirected
	 * to the configured startup page. If not logged in, the auth system will
	 * prevent this action from ever being hit.
	 * @return null
	 * @codeCoverageIgnore Due to auth/login side effects, this controller action should never be called
	 */
	public function indexAction()
	{
		// if for some reason this action is actually hit, just redirect to the
		// admin index.
		$this->_redirect('*');
	}

}
