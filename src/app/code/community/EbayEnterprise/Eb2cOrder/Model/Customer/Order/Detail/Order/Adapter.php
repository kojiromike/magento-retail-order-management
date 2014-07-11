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

class EbayEnterprise_Eb2cOrder_Model_Customer_Order_Detail_Order_Adapter
	extends Mage_Sales_Model_Order
{
	const SHIPMENT_TRACKING_LABEL = 'Tracking Number';

	/**
	 * getting a collection of sales/order_shipment_tracking
	 * with data loaded from extracting order detail response
	 * @return Varien_Data_Collection
	 */
	public function getTracksCollection()
	{
		foreach ($this->getShipments() as $shipment) {
			return $shipment->getTracksCollection();
		}
		return parent::getTracksCollection();
	}
	/**
	 * override sales/order::loadByIncrmentId method
	 * to update the order with data from order detail response
	 * @param string $incrementId
	 * @return self
	 */
	public function loadByIncrementId($incrementId)
	{
		return $this->_updateFromDetailResponse($incrementId);
	}
	/**
	 * Fetching order detail with an increment id known
	 * to both Magento and OMS extract data using map from
	 * configuration and replace Magento data with OMS data for
	 * that order.
	 * @param string $incrementId
	 * @return self
	 */
	protected function _updateFromDetailResponse($incrementId)
	{
		$helper = Mage::helper('eb2corder');
		$orderDetail = $helper->fetchOrderDetail($incrementId);
		$this->load($incrementId, 'increment_id');
		// don't do anything more if the order detail request failed.
		if (!$orderDetail->getOrder() || !$this->getId()) {
			return $this;
		}
		$this->addData($orderDetail->getOrder()->getData());
		$this->getShippingAddress()->addData(
			$orderDetail->getShippingAddress()->getData()
		);
		$this->setShippingDescription($orderDetail->getShippingAddress()->getChargeType());
		$this->getBillingAddress()->addData(
			$orderDetail->getBillingAddress()->getData()
		);
		// update payment information
		$this->_updatePaymentsFromResponse($orderDetail);
		$detailItems = $orderDetail->getItems();
		$helper->emptyCollection($this->getItemsCollection());
		foreach ($detailItems as $detailItem) {
			$product = Mage::helper('eb2cproduct')->loadProductBySku($detailItem->getSku());
			$item = Mage::getModel('sales/order_item')
				->setData($detailItem->getData())
				->setProduct($product)
				->setProductId($product->getId());
			$this->_setupGiftMessage($item);
			$this->addItem($item);
		}

		// emptying out order invoice in order to remove invoice tab in order detail view
		$helper->emptyCollection($this->getInvoiceCollection());

		// update shipment information
		$this->_updateShipmentsFromResponse($orderDetail);
		return $this;
	}
	/**
	 * calculating grand total base on the extracted data
	 * from order detail request
	 * @return float
	 */
	public function getGrandTotal()
	{
		return $this->getSubtotal() +
			$this->getTaxAmount() +
			$this->getShippingAmount() -
			$this->getDiscountAmount();
	}
	/**
	 * disable the save method
	 * @return self
	 */
	public function save()
	{
		return $this;
	}
	/**
	 * get the payment method name for the payment type
	 * @param  string $paymentTypeName
	 * @return string
	 */
	protected function _getPaymentMethod($paymentTypeName)
	{
		$mappings = Mage::getStoreConfig(EbayEnterprise_Eb2cOrder_Model_Customer_Order_Detail::PAYMENT_METHOD_MAPPING);
		if (!isset($mappings[$paymentTypeName])) {
			throw new EbayEnterprise_Eb2cCore_Exception_Critical(
				"$paymentTypeName elements are not mapped to a valid Magento payment method."
			);
		}
		return $mappings[$paymentTypeName];
	}
	/**
	 * get update payment information
	 * @param  EbayEnterprise_Eb2cOrder_Model_Customer_Order_Detail $payments
	 * @return self
	 */
	protected function _updatePaymentsFromResponse(EbayEnterprise_Eb2cOrder_Model_Customer_Order_Detail $orderDetail)
	{
		foreach ($this->getPaymentsCollection()->getAllIds() as $id) {
			$this->getPaymentsCollection()->removeItemByKey($id);
		}
		$idCounter = 0;
		$cards = array();
		$payments = $orderDetail->getPayments();
		foreach ($payments as $paymentData) {
			$paymentInfo = Mage::getModel('eb2corder/customer_order_detail_order_payment_adapter')->addData($paymentData->getData())
				->setMethod($this->_getPaymentMethod($paymentData->getPaymentTypeName()));
			switch ($paymentData->getPaymentTypeName()) {
				case 'CreditCard':
					$paymentInfo->addData(array(
						'cc_last4' => substr($paymentData->getAccountUniqueId(), -4),
						'cc_type' => $paymentData->getTenderType(),
					));
					break;
				case 'StoredValueCard':
					$cards[] = array(
						'ba' => $paymentData->getAmount(),
						'a' => $paymentData->getAmount(),
						'c' => $paymentData->getAccountUniqueId(),
					);
					break;
			}
			$this->addPayment($paymentInfo);
		}
		Mage::helper('enterprise_giftcardaccount')->setCards($this, $cards);
		return $this;
	}

	/**
	 * setup the item with data needed to display gift messages.
	 * @param  Mage_Sales_Model_Order_Item $item
	 * @return self
	 */
	protected function _setupGiftMessage(Mage_Sales_Model_Order_Item $item)
	{
		if ($item->getGiftMessage() || ($item->getGiftMessageTo() && $item->getGiftMessageFrom())) {
			$item->setGiftMessageId(1)
				->setGiftMessageAvailable(true)
				->setGiftMessage(Mage::getModel('giftmessage/message', array(
					'message' => $item->getGiftMessage(),
					'sender' => $item->getMessageFrom(),
					'recipient' => $item->getMessageTo(),
				)));
		}
		return $this;
	}
	/**
	 * get update shipment information
	 * @param  EbayEnterprise_Eb2cOrder_Model_Customer_Order_Detail $shipment
	 * @return self
	 */
	protected function _updateShipmentsFromResponse(EbayEnterprise_Eb2cOrder_Model_Customer_Order_Detail $shipment)
	{
		$collection = Mage::helper('eb2corder')->emptyCollection($this->getShipmentsCollection());
		foreach ($shipment->getShipments() as $shipmentData) {
			$shipmentData->addData(array(
				'shipping_address' => Mage::getModel('sales/order_address', $shipment->getShippingAddress()->getData())
			));
			$collection->addItem(Mage::getModel('eb2corder/customer_order_detail_order_shipment_adapter')->addData($shipmentData->getData()));
		}
		$this->setShipments($collection);
		return $this;
	}
	/**
	 * get shipping tracking information
	 * @return self
	 */
	public function getTrackingInfo()
	{
		$data = array();
		foreach ($this->getShipments() as $shipment) {
			foreach ($shipment->getTracksCollection() as $tracking) {
				$data[$shipment->getIncrementId()][] = array(
					'title' => Mage::helper('eb2corder')->__(static::SHIPMENT_TRACKING_LABEL),
					'number' => $tracking->getNumber()
				);
			}
		}
		return $data;
	}
}
