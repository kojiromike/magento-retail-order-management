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

/**
 * @codeCoverageIgnore
 */
class EbayEnterprise_Eb2cOrder_OrderController extends Mage_Sales_Controller_Abstract
{
	const CANCEL_FAIL_MESSAGE = 'EbayEnterprise_Order_Cancel_Fail_Message';
	const CANCEL_SUCCESS_MESSAGE = 'EbayEnterprise_Order_Cancel_Success_Message';

	/** @var EbayEnterprise_Order_Helper_Data */
	protected $_orderHelper;

	protected function _construct()
	{
		parent::_construct();
		$this->_orderHelper = Mage::helper('ebayenterprise_order');
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

	/**
	 * Handle canceling ROM order via ROM order cancel service.
	 */
	public function romCancelAction()
	{
		/** @var Mage_Sales_Model_Order */
		$order = $this->_getRomOrderToBeCanceled();
		/** @var EbayEnterprise_Order_Model_ICancel */
		$cancel = $this->_getRomOrderCancelModel($order);
		/** @var Mage_Core_Model_Session */
		$session = $this->_getRomCancelSession();
		/** @var string */
		$redirectUrl = $this->_getRefererUrl();
		try {
			$cancel->process();
		} catch(Exception $e) {
			$this->_handleRomCancelException($e, $session, $redirectUrl);
			return;
		}
		$this->_handleRomCancelResponse($order, $session, $redirectUrl);
	}

	/**
	 * Load the sales/order to be canceled.
	 *
	 * @return Mage_Sales_Model_Order
	 */
	protected function _getRomOrderToBeCanceled()
	{
		return Mage::getModel('sales/order')
			->loadByIncrementId($this->getRequest()->getParam('order_id'));
	}

	/**
	 * Get the ROM order cancel model.
	 *
	 * @param  Mage_Sales_Model_Order
	 * @return EbayEnterprise_Order_Model_ICancel
	 */
	protected function _getRomOrderCancelModel(Mage_Sales_Model_Order $order)
	{
		return Mage::getModel('ebayenterprise_order/cancel', array('order' => $order));
	}

	/**
	 * Get core session for ROM cancel action.
	 *
	 * @return Mage_Core_Model_Session
	 */
	protected function _getRomCancelSession()
	{
		return Mage::getSingleton('core/session');
	}

	/**
	 * Redirect and notify the customer of a successful or a fail
	 * cancel order action.
	 *
	 * @param  Mage_Sales_Model_Order
	 * @param  Mage_Core_Model_Session
	 * @param  string
	 * @return self
	 */
	protected function _handleRomCancelResponse(
		Mage_Sales_Model_Order $order,
		Mage_Core_Model_Session $session,
		$redirectUrl
	)
	{
		$incrementId = $order->getIncrementId();
		if ($order->getState() === Mage_Sales_Model_Order::STATE_CANCELED) {
			$session->addSuccess(sprintf($this->_orderHelper->__(static::CANCEL_SUCCESS_MESSAGE), $incrementId));
		} else {
			$session->addError(sprintf($this->_orderHelper->__(static::CANCEL_FAIL_MESSAGE), $incrementId));
		}
		$this->_redirectUrl($redirectUrl);
		return $this;
	}

	/**
	 * Redirect and notify the customer of an order could not be canceled.
	 *
	 * @param  Exception
	 * @param  Mage_Core_Model_Session
	 * @param  string
	 * @return self
	 */
	protected function _handleRomCancelException(
		Exception $e,
		Mage_Core_Model_Session $session,
		$redirectUrl
	)
	{
		$session->addError($this->_orderHelper->__($e->getMessage()));
		$this->_redirectUrl($redirectUrl);
		return $this;
	}
}
