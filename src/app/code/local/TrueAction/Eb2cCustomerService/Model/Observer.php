<?php

class TrueAction_Eb2cCustomerService_Model_Observer
{
	// (GET) Parameter passing the token from Epiphany
	const EPIPHANY_TOKEN_PARAM = 'token';
	const EPIPHANY_ENTRYPOINT_ACTION = '/admin/csrlogin';
	/**
	 * Check for Epiphany Token Authentication. If the original path, before being
	 * forwarded for standard Magento auth system,
	 * @param  Varien_Event_Observer $observer
	 * @return self
	 */
	public function preDispatchTokenLogin($observer)
	{
		$request = $observer->getEvent()->getControllerAction()->getRequest();
		if (strpos($request->getOriginalPathInfo(), static::EPIPHANY_ENTRYPOINT_ACTION) !== 0) {
			return $this;
		}
		$session = Mage::getSingleton('admin/session');
		// Whenever the token action is hit, always end any existing sessions
		// before starting new one - don't allow a failed token auth to resume
		// control of an existing session.
		if ($session->isLoggedIn()) {
			$session->setUser($session->getUser()->unsetData());
		}
		$session->loginCSRWithToken(
			$request->getParam(static::EPIPHANY_TOKEN_PARAM, ''),
			$request
		);
		return $this;
	}
}
