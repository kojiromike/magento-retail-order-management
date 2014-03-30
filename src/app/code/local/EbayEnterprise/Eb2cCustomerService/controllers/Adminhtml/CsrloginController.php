<?php

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
