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

class EbayEnterprise_Eb2cOrder_OrderController extends Mage_Sales_Controller_Abstract
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
			if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			  $this->_redirect('*/*/history');
			} else {
			  $this->_redirect('sales/guest/form');
			}
		}
	}

	/**
	 * Request details on the order you are given.
	 */
	public function _viewAction()
	{
		Mage::unregister('rom_order');
		$orderId = $this->getRequest()->getParam('order_id');
		Mage::getSingleton('core/session')->getMessages(true);
		$detailApi = Mage::getModel('eb2corder/detail');
		try {
			$romOrderObject = $detailApi->requestOrderDetail($orderId);
		} catch(EbayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound $e) {
			Mage::getSingleton('core/session')->addError($e->getMessage());
			if (Mage::getSingleton('customer/session')->isLoggedIn()) {
				$this->_redirect('*/*/history');
			} else {
				$this->_redirect('sales/guest/form');
			}
			return;
		}
		Mage::register('rom_order', $romOrderObject);
	}

	public function romViewAction()
	{
		$this->_viewAction();
		$this->loadLayout();
		$this->_initLayoutMessages('catalog/session');
		$navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');
		if ($navigationBlock) {
			$navigationBlock->setActive('sales/order/history');
		}
		$this->renderLayout();
	}

	public function romGuestViewAction()
	{
		$this->_viewAction();
		$this->loadLayout();
		$this->_initLayoutMessages('catalog/session');
		$this->renderLayout();
	}
}
