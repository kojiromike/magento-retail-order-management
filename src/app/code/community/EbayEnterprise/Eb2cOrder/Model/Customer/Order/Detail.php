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

class EbayEnterprise_Eb2cOrder_Model_Customer_Order_Detail
{
	const ORDER_MAPPING = 'eb2corder/detail_mapping/order';
	const ADDRESS_DATA_MAPPING = 'eb2corder/detail_mapping/address_data';
	const ORDER_ITEM_MAPPING = 'eb2corder/detail_mapping/order_item';
	const PAYMENT_INFO_MAPPING = 'eb2corder/detail_mapping/payment_info_data';
	const PAYMENT_METHOD_MAPPING = 'eb2corder/detail_mapping/payment_methods';
	const STATUS_MAPPING = 'eb2corder/detail_mapping/status';
	const SHIPMENT_INFO_MAPPING = 'eb2corder/detail_mapping/shipment_info_data';

	// @var mixed magento store id
	protected $_store;

	// @var EbayEnterprise_Eb2cCore_Model_Xml_Mapper
	protected $_orderData;

	// @var Varien_Data_Collection addresses from the response
	protected $_addresses;

	// @var EbayEnterprise_Eb2cCore_Model_Xml_Mapper shipping address information from the response
	protected $_shippingAddress;

	// @var EbayEnterprise_Eb2cCore_Model_Xml_Mapper billing address information from the response
	protected $_billingAddress;

	// @var Varien_Data_Collection item data from the response
	protected $_items;

	// @var Varien_Data_Collection payment data from the response
	protected $_payments;

	// @var Varien_Data_Collection shipment data from the response
	protected $_shipments;

	public function __construct()
	{
		$this->_items = new Varien_Data_Collection();
		$this->_payments = new Varien_Data_Collection();
		$this->_addresses = new Varien_Data_Collection();
		$this->_shipments = new Varien_Data_Collection();
	}

	/**
	 * @see $_orderData
	 * @return EbayEnterprise_Eb2cCore_Model_Xml_Mapper
	 */
	public function getOrder()
	{
		return $this->_orderData;
	}

	/**
	 * @see $_shippingAddress
	 * @return EbayEnterprise_Eb2cCore_Model_Xml_Mapper
	 */
	public function getShippingAddress()
	{
		return $this->_shippingAddress;
	}

	/**
	 * @see $_shippingAddress
	 * @return EbayEnterprise_Eb2cCore_Model_Xml_Mapper
	 */
	public function  getBillingAddress()
	{
		return $this->_billingAddress;
	}
	/**
	 * @see $_items
	 * @return Varien_Data_Collection
	 */
	public function getItems()
	{
		return $this->_items;
	}
	/**
	 * @see $_payments
	 * @return Varien_Data_Collection
	 */
	public function getPayments()
	{
		return $this->_payments;
	}
	/**
	 * @see $_shipments
	 * @return Varien_Data_Collection
	 */
	public function getShipments()
	{
		return $this->_shipments;
	}

	/**
	 * Customer Order Detail from eb2c, when orderId parameter is passed the request to eb2c is filter
	 * by customerOrderId instead of querying eb2c by the customer id to get all order relating to this customer
	 * @param string $orderId the magento order increment id to query eb2c with
	 * @return string the eb2c response text
	 */
	public function requestOrderDetail($orderId)
	{
		$cfg = Mage::helper('eb2corder')->getConfig();
		// make request to eb2c for Customer OrderSummary
		return Mage::getModel('eb2ccore/api')->request(
			$this->buildOrderDetailRequest($orderId),
			$cfg->xsdFileDetail,
			Mage::helper('eb2ccore')->getApiUri($cfg->apiService, $cfg->apiDetailOperation)
		);
	}

	/**
	 * Build OrderDetail request.
	 * @param string $orderId the order id to query eb2c with
	 * @return DOMDocument The XML document to be sent as request to eb2c.
	 */
	public function buildOrderDetailRequest($orderId)
	{
		$coreHelper = Mage::helper('eb2ccore')->getNewDomDocument();
		$domDocument = new EbayEnterprise_Dom_Document('');
		$domDocument->addElement(
			'OrderDetailRequest',
			null,
			Mage::helper('eb2corder')->getConfig()->apiXmlNs
		)->firstChild->createChild('CustomerOrderId', (string) $orderId);
		return $domDocument;
	}

	/**
	 * Parse customer order detail reply xml.
	 * @param string $orderDetailReply the xml response from eb2c
	 * @return self
	 */
	public function parseResponse($orderDetailReply)
	{
		if (trim($orderDetailReply) !== '') {
			$coreHlpr = Mage::helper('eb2ccore');
			$doc = $coreHlpr->getNewDomDocument();
			$doc->loadXML($orderDetailReply);
			$xpath = new DOMXPath($doc);
			$xpath->registerNamespace('a', Mage::helper('eb2corder')->getConfig()->apiXmlNs);
			$nodeList = $xpath->query('a:Order', $doc->documentElement);
			if ($nodeList->length) {
				$this->_orderData = Mage::getModel('eb2ccore/xml_mapper')
					->loadXml(
						$nodeList->item(0),
						Mage::getStoreConfig(self::ORDER_MAPPING),
						$xpath
					);
				$this->_extractPayments($xpath);
				$this->_extractItems($xpath);
				$this->_extractAddresses($xpath);
				$this->_setBillingAddress($xpath);
				$this->_determineShippingAddress($xpath);
				$this->_extractShipments($xpath);
			}
		}
		return $this;
	}
	/**
	 * extract address data from the resposne
	 * @param  DOMXpath $xpath
	 * @return self
	 */
	protected function _extractAddresses(DOMXpath $xpath)
	{
		$nodeList = $xpath->query('//a:Shipping/a:Destinations/*');
		foreach ($nodeList as $addressElement) {
			$addressData = Mage::getModel('eb2ccore/xml_mapper')
				->loadXml(
					$addressElement,
					Mage::getStoreConfig(self::ADDRESS_DATA_MAPPING),
					$xpath
				);
			$addressData->setStreet(implode("\n", array_filter(array(
				$addressData->getStreet1(),
				$addressData->getStreet2(),
				$addressData->getStreet3(),
				$addressData->getStreet4()
			))));
			$addressData->setName(implode(' ', array_filter(array(
				$addressData->getFirstname(),
				$addressData->getLastname()
			))));
			$this->_addresses->addItem($addressData);
		}
	}
	/**
	 * collect item level data
	 * @param  DOMXpath $xpath
	 * @return self
	 */
	protected function _extractItems(DOMXpath $xpath)
	{
		$items = $xpath->query('//a:OrderItem');
		foreach ($items as $item) {
			$itemData = Mage::getModel('eb2ccore/xml_mapper')
				->loadXml(
					$item,
					Mage::getStoreConfig(self::ORDER_ITEM_MAPPING),
					$xpath
				);
			$this->_setupOptions($itemData);
			$this->_items->addItem($itemData);
		}
		return $this;
	}
	/**
	 * collect payment data
	 * @param  DOMXpath $xpath
	 * @return self
	 */
	protected function _extractPayments(DOMXpath $xpath)
	{
		$payments = $xpath->query('//a:Payment/a:Payments/*');
		foreach ($payments as $payment) {
			$paymentData = Mage::getModel('eb2ccore/xml_mapper')
				->loadXml(
					$payment,
					Mage::getStoreConfig(self::PAYMENT_INFO_MAPPING),
					$xpath
				);
			$paymentData->setPaymentTypeName($payment->nodeName);
			$this->_payments->addItem($paymentData);
		}
		return $this;
	}

	/**
	 * setup a billing address
	 *
	 * @param DOMXpath $xpath
	 * @return self
	 */
	protected function _setBillingAddress(DOMXpath $xpath)
	{
		$billingAddress = $xpath->query('//a:Payment/a:BillingAddress');
		if ($billingAddress->length) {
			$destinationId = $billingAddress->item(0)->getAttribute('ref');
			$this->_billingAddress = $this->_addresses->getItemById($destinationId);
			$this->_billingAddress->setAddressType(Mage_Customer_Model_Address_Abstract::TYPE_BILLING);
			Mage::log(
				"set billing address (id=\"$destinationId\")"
			);
		}
		return $this;
	}
	/**
	 * set the shipping address for the order.
	 * @param  DOMXpath $xpath
	 * @return self
	 */
	protected function _determineShippingAddress(DOMXpath $xpath)
	{
		Mage::log('determining shipping address');
		$shipGroups = $xpath->query('//a:ShipGroup');
		foreach ($shipGroups as $shipGroup) {
			$chargeType = $shipGroup->getAttribute('chargeType');
			$destinationId = $xpath->query('a:DestinationTarget/@ref', $shipGroup)->item(0)->nodeValue;
			// skip the billing address
			if ($this->_billingAddress->getId() == $destinationId) {
				continue;
			}
			$this->_shippingAddress = $this->_addresses->getItemById($destinationId);
			$this->_shippingAddress->addData(array(
				'address_type' => Mage_Customer_Model_Address_Abstract::TYPE_SHIPPING,
				'charge_type' => $chargeType,
			));
			Mage::log(
				"shipping address (id=\"$destinationId\") " .
				$this->_shippingAddress->isEmpty() ? 'found' : 'not found'
			);
		}
		return $this;
	}
	/**
	 * collect shipment data
	 * @param  DOMXpath $xpath
	 * @return self
	 */
	protected function _extractShipments(DOMXpath $xpath)
	{
		foreach ($xpath->query('//a:Shipping/a:Shipments/a:Shipment') as $shipment) {
			$shipmentData = Mage::getModel('eb2ccore/xml_mapper')->loadXml(
				$shipment,
				Mage::getStoreConfig(self::SHIPMENT_INFO_MAPPING),
				$xpath
			);
			if (trim($shipmentData->getIncrementId())) {
				$this->_addShipmentItems($shipmentData);
				$this->_shipments->addItem($shipmentData);
			}
		}
		return $this;
	}
	/**
	 * add item to shipment
	 * @param EbayEnterprise_Eb2cCore_Model_Xml_Mapper $shipmentData
	 * @return self
	 */
	protected function _addShipmentItems(EbayEnterprise_Eb2cCore_Model_Xml_Mapper $shipmentData)
	{
		$shipmentItems = new Varien_Data_Collection();
		$items = $this->getItems();
		foreach ($shipmentData->getShippedItemIds() as $itemRefId) {
			$item = $items->getItemByColumnValue('ref_id', $itemRefId);
			if ($item && $item->getSku()) {
				$data = array_merge($item->getData(), array('qty' => $item->getQtyShipped()));
				$shipmentItems->addItem(Mage::getModel('sales/order_shipment_item', $data));
			}
		}
		$shipmentData->addData(array('all_items' => $shipmentItems));
		return $this;
	}

	/**
	 * serialize the extracted product options array
	 * @param  EbayEnterprise_Eb2cCore_Model_Xml_Mapper $itemData
	 * @return self
	 */
	protected function _setupOptions(EbayEnterprise_Eb2cCore_Model_Xml_Mapper $itemData)
	{
		$itemData->setProductOptions(serialize(array('11', 'sdf')));// $itemData->getProductOptions()));
		return $this;
	}
}
