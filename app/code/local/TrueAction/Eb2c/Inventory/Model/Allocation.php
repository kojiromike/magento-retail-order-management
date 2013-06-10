<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Model_Allocation extends Mage_Core_Model_Abstract
{
	protected $_helper;

	public function __construct()
	{
		$this->_helper = $this->_getHelper();
	}

	/**
	 * Get helper instantiated object.
	 *
	 * @return TrueAction_Eb2c_Inventory_Helper_Data
	 */
	protected function _getHelper()
	{
		if (!$this->_helper) {
			$this->_helper = Mage::helper('eb2c_inventory');
		}
		return $this->_helper;
	}

	/**
	 * Allocating all items brand new order from eb2c.
	 *
	 * @param Mage_Sales_Model_Order $order, the order to allocate iventory items in eb2c for
	 *
	 * @return string the eb2c response to the request.
	 */
	public function allocateOrderItems($order)
	{
		$allocationResponseMessage = '';
		try{
			// build request
			$allocationRequestMessage = $this->buildAllocationRequestMessage($order);

			// make request to eb2c for order items allocation
			$allocationResponseMessage = $this->_getHelper()->getCoreHelper()->apiCall(
				$allocationRequestMessage,
				$this->_getHelper()->getAllocationUri()
			);
		}catch(Exception $e){
			Mage::logException($e);
		}

		return $allocationResponseMessage;
	}

	/**
	 * Build  Allocation request.
	 *
	 * @param Mage_Sales_Model_Order $order, the order to generate request xm from
	 *
	 * @return DOMDocument The xml document, to be sent as request to eb2c.
	 */
	public function buildAllocationRequestMessage($order)
	{
		$domDocument = $this->_getHelper()->getDomDocument();
		$allocationRequestMessage = $domDocument->addElement('AllocationRequestMessage', null, $this->_getHelper()->getXmlNs())->firstChild;
		$allocationRequestMessage->setAttribute('requestId', $this->_getHelper()->getRequestId($order->getEntityId(), $order->getIncrementId()));
		$allocationRequestMessage->setAttribute('reservationId', $this->_getHelper()->getReservationId($order->getEntityId(), $order->getIncrementId()));
		if ($order) {
			foreach($order->getAllItems() as $item){
				try{
					// creating orderItem element
					$orderItem = $allocationRequestMessage->createChild(
						'OrderItem',
						null,
						array('lineId' => $item->getId(), 'itemId' => $item->getSku())
					);

					// add quanity
					$orderItem->createChild(
						'Quantity',
						(string) $item->getQtyOrdered() // integer value doesn't get added only string
					);

					$shippingAddress = $order->getShippingAddress();
					// creating shipping details
					$shipmentDetails = $orderItem->createChild(
						'ShipmentDetails',
						null
					);

					// add shipment method
					$shipmentDetails->createChild(
						'ShippingMethod',
						$order->getShippingMethod()
					);

					// add ship to address
					$shipToAddress = $shipmentDetails->createChild(
						'ShipToAddress',
						null
					);

					// add ship to address Line1
					$street = $shippingAddress->getStreet();
					$lineAddress = '';
					if(sizeof($street) > 0){
						$lineAddress = $street[0];
					}
					$shipToAddress->createChild(
						'Line1',
						$lineAddress
					);

					// add ship to address City
					$shipToAddress->createChild(
						'City',
						$shippingAddress->getCity()
					);

					// add ship to address MainDivision
					$shipToAddress->createChild(
						'MainDivision',
						$shippingAddress->getRegion()
					);

					// add ship to address CountryCode
					$shipToAddress->createChild(
						'CountryCode',
						$shippingAddress->getCountryId()
					);

					// add ship to address PostalCode
					$shipToAddress->createChild(
						'PostalCode',
						$shippingAddress->getPostcode()
					);
				}catch(Exception $e){
					Mage::logException($e);
				}
			}
		}
		return $domDocument;
	}

	/**
	 * update order with allocation reponse data.
	 *
	 * @param Mage_Sales_Model_Order $order the order we use to get allocation reqponse from eb2c
	 * @param string $allocationResponseMessage the xml reponse from eb2c
	 *
	 * @return void
	 */
	public function processAllocation($order, $allocationResponseMessage)
	{
		if (trim($allocationResponseMessage) !== '') {
			$doc = $this->_getHelper()->getDomDocument();

			// load response string xml from eb2c
			$doc->loadXML($allocationResponseMessage);
			$i = 0;
			$allocationResponse = $doc->getElementsByTagName('AllocationResponse');
			foreach($allocationResponse as $response) {
				$allocationData = array(
					'lineId' => $response->getAttribute('lineId'),
					'itemId' => $response->getAttribute('itemId'),
					'qty' => (int) $allocationResponse->item($i)->nodeValue
				);

				if ($orderItem = $order->getItemById($allocationData['lineId'])) {
					// update order with eb2c data.
					$this->_updateOrderWithEb2cAllocation($orderItem, $allocationData);
				}

				$i++;
			}
		}
	}

	/**
	 * update order with allocation reponse data.
	 *
	 * @param Mage_Sales_Model_Quote_Item $orderItem the item to be updated with eb2c data
	 * @param array $orderData the data from eb2c for the order item
	 *
	 * @return void
	 */
	protected function _updateOrderWithEb2cAllocation($orderItem, $orderData)
	{
		// To do: not yet completed
		// if allocation for this item is less then what the order customer requested
		// then update the order item with what eb2c allocated.
		if ($orderItem->getQtyOrdered() > $orderData['qty'] && $orderData['qty'] > 0) {
			// set order item to eb2c allocated qty

			// get order from order item

			// recalc totals

			// save the order

		} elseif ($orderData['qty'] <= 0) {
			// Item is out of stock in eb2c, remove it from the order
		}
	}
}
