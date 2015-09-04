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


class EbayEnterprise_Eb2cCustomerService_Model_Observer
{
    // (GET) Parameter passing the token from Epiphany
    const EPIPHANY_TOKEN_PARAM = 'token';
    const EPIPHANY_ENTRYPOINT_ACTION = '/admin/csrlogin';
    /** @var EbayEnterprise_Eb2cCustomerService_Helper_Data */
    protected $_helper;

    public function __construct()
    {
        $this->_helper = Mage::helper('eb2ccsr');
    }

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

    /**
     * Supply the dashboard rep id to the order create request
     * if applicable.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     * @codeCoverageIgnore
     */
    public function handleEbayEnterpriseOrderCreateBeforeAttach(Varien_Event_Observer $observer)
    {
        /** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCreateRequest */
        $payload = $observer->getEvent()->getPayload();
        // Need to use current store context to determine if admin placed order
        $store = Mage::app()->getStore();
        $repId = $this->_helper->getDashboardRepId($store);
        $payload->setDashboardRepId($repId);
    }
}
