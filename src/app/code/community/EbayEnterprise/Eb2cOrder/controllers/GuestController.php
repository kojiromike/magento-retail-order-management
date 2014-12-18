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

class EbayEnterprise_Eb2cOrder_GuestController extends Mage_Sales_GuestController
{
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
		$orderId       = $this->getRequest()->getPost('oar_order_id');
		$orderEmail    = $this->getRequest()->getPost('oar_email');
		$orderZip      = $this->getRequest()->getPost('oar_zip');
		$orderLastname = $this->getRequest()->getPost('oar_billing_lastname');

		Mage::getSingleton('core/session')->getMessages(true);
		$detailApi = Mage::getModel('eb2corder/detail');
		try {
			$romOrderObject = $detailApi->requestOrderDetail($orderId);
		} catch(EbayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound $e) {
			Mage::getSingleton('core/session')->addError($e->getMessage());
			$this->_redirect('sales/guest/form');
			return;
		}
		$billingAddress = $romOrderObject->getBillingAddress();
		if ($orderLastname === $billingAddress->getLastname()
			&& ((!empty($orderZip) && $orderZip === $billingAddress->getPostalCode())
				|| (!empty($orderEmail) && $orderEmail === $romOrderObject->getEmailAddress())))
		{
			$this->_redirect('sales/order/romguestview/order_id/'.$orderId);
		} else {
			Mage::getSingleton('core/session')->addError(Mage::helper('eb2corder')->__('Order not found.'));
			$this->_redirect('sales/guest/form');
		}
		return;
	}
}
