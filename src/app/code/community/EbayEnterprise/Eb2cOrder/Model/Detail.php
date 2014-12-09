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
 * This class is responsible for making order detail request to ROM OMS to get order
 * detail data for a loaded passed in sales/order object. The entry point of this
 * class is via the injectOrderDetail() method which take a sales/order object.
 * The increment id from the loaded sales/order object is used to send the order
 * detail request. The return response xml is passed into the _parseResponse()
 * method along with the loaded sales/order object which then pass along to specific methods
 * to extract and inject the extracted data to specific section of the loaded sales/order object.
 */
class EbayEnterprise_Eb2cOrder_Model_Detail
{
	/**
	 * hold config path for when the api return a 400 response because the order was not found in OMS
	 */
	const API_STATUS_HANDLER_CONFIG_PATH = 'eb2corder/api_status_handler';
	const ORDER_DETAIL_NOT_FOUND_EXCEPTION = 'Order "%s" was not found.';
	/**
	 * replacing sales/order data with OMS data to a passed in sales/order object.
	 * Take the data in the sales/order object and add it to a clone eb2corder/detail_order
	 * object.
	 * @param Mage_Sales_Model_Order $order
	 * @return EbayEnterprise_Eb2cOrder_Model_Detail_Order
	 */
	public function injectOrderDetail(Mage_Sales_Model_Order $order)
	{
		$cloneOrder = Mage::getModel('eb2corder/detail_order', $order->getData());
		$this->_parseResponse(
			$this->_requestOrderDetail($order->getRealOrderId()), $cloneOrder
		);
		return $cloneOrder;
	}
	/**
	 * Customer Order Detail from eb2c, when orderId parameter is passed the request to eb2c is filter
	 * by customerOrderId instead of querying eb2c by the customer id to get all order relating to this customer
	 * @param string $orderId the magento order increment id to query eb2c with
	 * @return string the eb2c response text
	 * @throws EbayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound
	 */
	protected function _requestOrderDetail($orderId)
	{
		$detailHelper = Mage::helper('eb2corder/detail');
		// look for a cached response for this order id
		$cachedResponse = $detailHelper->getCachedOrderDetailResponse($orderId);
		if ($cachedResponse) {
			return $cachedResponse;
		}

		$cfg = Mage::helper('eb2corder')->getConfigModel();
		// make request to eb2c for Customer OrderDetail
		$response = Mage::getModel('eb2ccore/api')
			->setStatusHandlerPath(static::API_STATUS_HANDLER_CONFIG_PATH)
			->request(
				$this->_buildOrderDetailRequest($orderId),
				$cfg->xsdFileDetail,
				Mage::helper('eb2ccore')->getApiUri($cfg->apiService, $cfg->apiDetailOperation)
			);
		if (empty($response)) {
			throw new EbayEnterprise_Eb2cOrder_Exception_Order_Detail_Notfound(
				sprintf(static::ORDER_DETAIL_NOT_FOUND_EXCEPTION, $orderId)
			);
		}
		$detailHelper->updateOrderDetailResponseCache($orderId, $response);
		return $response;
	}
	/**
	 * Build OrderDetail request.
	 * @param string $orderId the order id to query eb2c with
	 * @return DOMDocument The XML document to be sent as request to eb2c.
	 */
	protected function _buildOrderDetailRequest($orderId)
	{
		$domDocument = Mage::helper('eb2ccore')->getNewDomDocument();
		$domDocument->addElement(
			'OrderDetailRequest',
			null,
			Mage::helper('eb2corder')->getConfigModel()->apiXmlNs
		)->firstChild->createChild('CustomerOrderId', (string) $orderId);
		return $domDocument;
	}
	/**
	 * Parse customer order detail reply xml.
	 * @param string $orderDetailReply the xml response from eb2c
	 * @param EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder
	 * @return self
	 */
	protected function _parseResponse($orderDetailReply, EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder)
	{
		if (trim($orderDetailReply) !== '') {
			$cfg = Mage::helper('eb2corder')->getConfigModel();
			$coreHlpr = Mage::helper('eb2ccore');
			$doc = $coreHlpr->getNewDomDocument();
			$doc->loadXML($orderDetailReply);
			$xpath = $coreHlpr->getNewDomXPath($doc);
			$xpath->registerNamespace('a', $cfg->apiXmlNs);
			$nodeList = $xpath->query('a:Order', $doc->documentElement);
			if ($nodeList->length) {
				$this->_injectOrder($xpath, $nodeList, $cloneOrder)
					->_injectPayments($xpath, $cloneOrder)
					->_injectItems($xpath, $cloneOrder)
					->_injectAddresses($xpath, $cloneOrder)
					->_setBillingAddress($xpath, $cloneOrder)
					->_determineShippingAddress($xpath, $cloneOrder)
					->_injectShipments($xpath, $cloneOrder)
					->_clearInvoice($cloneOrder);

			}
		}
		return $this;
	}
	/**
	 * Injecting OMS order detail data into Magento sales/order object.
	 * @param  DOMXpath $xpath
	 * @param  DOMNodeList $nodeList
	 * @param  EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder
	 * @return self
	 */
	protected function _injectOrder(DOMXpath $xpath, DOMNodeList $nodeList, EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder)
	{
		$cloneOrder->addData(Mage::helper('eb2ccore')->extractXmlToArray(
			$nodeList->item(0),
			Mage::helper('eb2corder')->getConfigModel()->detailOrderMapping,
			$xpath
		));
		return $this;
	}
	/**
	 * Injecting OMS order detail address data into Magento sales/order_address object.
	 * @param  DOMXpath $xpath
	 * @param  EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder
	 * @return self
	 */
	protected function _injectAddresses(DOMXpath $xpath, EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder)
	{
		// clear the loaded order object address collection
		$cloneOrder->getAddressesCollection()->clear();
		$nodeList = $xpath->query('//a:Shipping/a:Destinations/*');
		foreach ($nodeList as $addressElement) {
			$addressData = Mage::helper('eb2ccore')->extractXmlToArray(
				$addressElement,
				Mage::helper('eb2corder')->getConfigModel()->detailAddressMapping,
				$xpath
			);
			if (!empty($addressData) && isset($addressData['id'])) {
				$cloneOrder->getAddressesCollection()->addItem(Mage::getModel('eb2corder/detail_address', $addressData)->setOrder($cloneOrder));
			}
		}

		return $this;
	}
	/**
	 * Injecting OMS order detail item level data into Magento sales/order_item object.
	 * @param  DOMXpath $xpath
	 * @param  EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder
	 * @return self
	 */
	protected function _injectItems(DOMXpath $xpath, EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder)
	{
		// clear empty the loaded order object item collection
		$cloneOrder->getItemsCollection()->clear();
		$items = $xpath->query('//a:OrderItem');
		foreach ($items as $item) {
			$itemData = Mage::helper('eb2ccore')->extractXmlToArray(
				$item,
				Mage::helper('eb2corder')->getConfigModel()->detailOrderItemMapping,
				$xpath
			);
			$cloneOrder->getItemsCollection()->addItem(Mage::getModel('eb2corder/detail_item', $itemData)->setOrder($cloneOrder));
		}
		return $this;
	}
	/**
	 * Injecting OMS order detail payment data into Magento sales/order payment.
	 * @param  DOMXpath $xpath
	 * @param  EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder
	 * @return self
	 */
	protected function _injectPayments(DOMXpath $xpath, EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder)
	{
		// clear the loaded order payment collection
		$cloneOrder->getPaymentsCollection()->clear();
		$payments = $xpath->query('//a:Payment/a:Payments/*');
		foreach ($payments as $payment) {
			$paymentData = Mage::helper('eb2ccore')->extractXmlToArray(
				$payment,
				Mage::helper('eb2corder')->getConfigModel()->detailPaymentInfoMapping,
				$xpath
			);
			$paymentData['payment_type_name'] = $payment->nodeName;
			// I'm passing the order object here so that EbayEnterprise_Eb2cOrder_Model_Detail_Payment::_construct can call
			// the Mage_Sales_Model_Order_Payment::setOrder method to set the order in the class property. This is being done
			// this way so that the gift card can be set on the order, see EbayEnterprise_Eb2cOrder_Model_Detail_Payment::_updatePayments.
			$paymentData['order'] = $cloneOrder;
			$cloneOrder->getPaymentsCollection()->addItem(Mage::getModel('eb2corder/detail_payment', $paymentData));
		}
		return $this;
	}
	/**
	 * setup a billing address
	 * @param DOMXpath $xpath
	 * @param EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder
	 * @return self
	 */
	protected function _setBillingAddress(DOMXpath $xpath, EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder)
	{
		$bANode = $xpath->query('//a:Payment/a:BillingAddress');
		if ($bANode->length) {
			$destinationId = $bANode->item(0)->getAttribute('ref');
			$billingAddress = $cloneOrder->getAddressesCollection()->getItemById($destinationId);
			if ($billingAddress) {
				$billingAddress->setAddressType(Mage_Customer_Model_Address_Abstract::TYPE_BILLING);
				Mage::helper('ebayenterprise_magelog')->logInfo(
					'[%s] set billing address (id="%")',
					array(__METHOD__, $destinationId)
				);
			}
		}
		return $this;
	}
	/**
	 * set the shipping address for the order.
	 * @param  DOMXpath $xpath
	 * @param  EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder
	 * @return self
	 */
	protected function _determineShippingAddress(DOMXpath $xpath, EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder)
	{
		Mage::helper('ebayenterprise_magelog')->logInfo(
			'[%s] determining shipping address',
			array(__METHOD__)
		);
		$shipGroups = $xpath->query('//a:ShipGroup');
		foreach ($shipGroups as $shipGroup) {
			$chargeType = $shipGroup->getAttribute('chargeType');
			$destinationId = $xpath->query('a:DestinationTarget/@ref', $shipGroup)->item(0)->nodeValue;
			$shippingAddress = $cloneOrder->getAddressesCollection()->getItemById($destinationId);
			if ($shippingAddress) {
				// skip the billing address
				if ($shippingAddress->getAddressType() === Mage_Customer_Model_Address_Abstract::TYPE_BILLING) {
					continue;
				}
				$shippingAddress->addData(array(
					'address_type' => Mage_Customer_Model_Address_Abstract::TYPE_SHIPPING,
					'charge_type' => $chargeType,
				));
				Mage::helper('ebayenterprise_magelog')->logInfo(
					'[%s] shipping address (id="%s") %s',
					array(__METHOD__, $destinationId, $shippingAddress->isEmpty() ? 'not found' : 'found')
				);
			}
		}
		return $this;
	}
	/**
	 * Injecting OMS order detail shipment data into Magento sales/order loaded object.
	 * @param  DOMXpath $xpath
	 * @param  EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder
	 * @return self
	 */
	protected function _injectShipments(DOMXpath $xpath, EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder)
	{
		// clear loaded order object payment collection
		$cloneOrder->getShipmentsCollection()->clear();
		foreach ($xpath->query('//a:Shipping/a:Shipments/a:Shipment') as $shipment) {
			$shipmentData = Mage::helper('eb2ccore')->extractXmlToArray(
				$shipment,
				Mage::helper('eb2corder')->getConfigModel()->detailShipmentInfoMapping,
				$xpath
			);
			if (isset($shipmentData['increment_id']) && trim($shipmentData['increment_id'])) {
				$shipmentData['order'] = $cloneOrder;
				$cloneOrder->addShipment(Mage::getModel('eb2corder/detail_shipment', $shipmentData));
			}
		}
		return $this;
	}
	/**
	 * clearing the Magento sales/order_invoice collection in order to disable
	 * it from showing in the front-end order detail interface.
	 * @param  EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder
	 * @return self
	 */
	protected function _clearInvoice(EbayEnterprise_Eb2cOrder_Model_Detail_Order $cloneOrder)
	{
		// clear loaded order object invoice collection
		$cloneOrder->getInvoiceCollection()->clear();
		return $this;
	}
}
