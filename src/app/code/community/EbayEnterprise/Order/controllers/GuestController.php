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

require_once 'Mage/Sales/controllers/GuestController.php';

class EbayEnterprise_Order_GuestController extends Mage_Sales_GuestController
{
	/** @var EbayEnterprise_Order_Helper_Data */
	protected $_orderHelper;
	/** @var EbayEnterprise_Order_Helper_Factory */
	protected $_orderFactory;

	protected function _construct()
	{
		parent::_construct();
		$this->_orderHelper = Mage::helper('ebayenterprise_order');
		$this->_orderFactory = Mage::helper('ebayenterprise_order/factory');
	}

	/**
	 * load the order with the order id from the request and
	 * bypass loading a shipment instance from the db.
	 */
	public function printOrderShipmentAction()
	{
		if ($this->_loadValidOrder() &&
				$this->_canViewOrder(Mage::registry('current_order')) &&
				$this->getRequest()->getParam('shipment_id')
		) {
			$this->loadLayout('print');
			$this->renderLayout();
		} else {
		  $this->_redirect('sales/guest/form');
		}
	}
	/**
	 * Redirect to romview after validating that correct information has actually been answered
	 */
	public function viewAction()
	{
		Mage::unregister('rom_order');
		/** @var Zend_Controller_Request_Http */
		$request = $this->getRequest();
		$orderId = $request->getPost('oar_order_id');
		$orderEmail = $request->getPost('oar_email');
		$orderZip = $request->getPost('oar_zip');
		$orderLastname = $request->getPost('oar_billing_lastname');
		/** @var Mage_Core_Model_Session */
		$session = $this->_orderFactory->getCoreSessionModel();

		// Clearing out messages
		$session->getMessages(true);
		/** @var EbayEnterprise_Order_Model_Detail */
		$detailApi = $this->_orderFactory->getNewRomOrderDetailModel($orderId);
		try {
			/** @var EbayEnterprise_Order_Model_Detail_Order $romOrderObject */
			$romOrderObject = $detailApi->process();
		} catch(EbayEnterprise_Order_Exception_Order_Detail_Notfound_Exception $e) {
			$session->addError($e->getMessage());
			$this->_redirect('sales/guest/form');
			return;
		}
		if ($this->_hasValidOrderResult($romOrderObject, $orderEmail, $orderZip, $orderLastname)) {
			$this->_redirect('sales/order/romguestview', ['order_id' => $orderId]);
		} else {
			$session->addError($this->_orderHelper->__('Order not found.'));
			$this->_redirect('sales/guest/form');
		}
		return;
	}

	/**
	 * Check whether we have a valid order result.
	 * @param  EbayEnterprise_Order_Model_Detail_Process_IResponse
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return bool
	 */
	protected function _hasValidOrderResult(EbayEnterprise_Order_Model_Detail_Process_IResponse $romOrderObject, $orderEmail, $orderZip, $orderLastname)
	{
		$billingAddress = $romOrderObject->getBillingAddress();
		return (
			$billingAddress && $orderLastname === $billingAddress->getLastname()
			&& (
				($orderZip && $orderZip === $billingAddress->getPostalCode())
				|| ($orderEmail	&& $orderEmail === $romOrderObject->getCustomerEmail())
			)
		);
	}
}
