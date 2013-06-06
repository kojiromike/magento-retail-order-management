<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Model_Details extends Mage_Core_Model_Abstract
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
	 * Get the inventory details for all items in this quote from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to get eb2c inventory details on
	 *
	 * @return string the eb2c response to the request.
	 */
	public function getInventoryDetails($quote)
	{
		$inventoryDetailsResponseMessage = '';
		try{
			// build request
			$inventoryDetailsRequestMessage = $this->buildInventoryDetailsRequestMessage($quote);

			// make request to eb2c for inventory details
			$inventoryDetailsResponseMessage = $this->_getHelper()->getCoreHelper()->apiCall(
				$inventoryDetailsRequestMessage,
				$this->_getHelper()->getInventoryDetailsUri()
			);
		}catch(Exception $e){
			Mage::logException($e);
		}

		return $inventoryDetailsResponseMessage;
	}

	/**
	 * Build Inventory Details request.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote to generate request xm from
	 *
	 * @return DOMDocument The xml document, to be sent as request to eb2c.
	 */
	public function buildInventoryDetailsRequestMessage($quote)
	{
		$domDocument = $this->_getHelper()->getDomDocument();
		$inventoryDetailsRequestMessage = $domDocument->addElement('InventoryDetailsRequestMessage', null, $this->_getHelper()->getXmlNs())->firstChild;
		if ($quote) {
			foreach($quote->getAllItems() as $item){
				try{
					// creating orderItem element
					$orderItem = $inventoryDetailsRequestMessage->createChild(
						'OrderItem',
						null,
						array('lineId' => $item->getId(), 'itemId' => $item->getSku())
					);

					// add quanity
					$orderItem->createChild(
						'Quantity',
						(string) $item->getQty() // integer value doesn't get added only string
					);

					$shippingAddress = $quote->getShippingAddress();
					// creating shipping details
					$shipmentDetails = $orderItem->createChild(
						'ShipmentDetails',
						null
					);

					// add shipment method
					$shipmentDetails->createChild(
						'ShippingMethod',
						$shippingAddress->getShippingMethod()
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
	 * update quote with inventory details reponse data.
	 *
	 * @param Mage_Sales_Model_Quote $quote the quote we use to get inventory details from eb2c
	 * @param string $inventoryDetailsResponseMessage the xml reponse from eb2c
	 *
	 * @return void
	 */
	public function processInventoryDetails($quote, $inventoryDetailsResponseMessage)
	{
		if (trim($inventoryDetailsResponseMessage) !== '') {
			$doc = $this->_getHelper()->getDomDocument();

			// load response string xml from eb2c
			$doc->loadXML($inventoryDetailsResponseMessage);
			$i = 0;
			$inventoryDetails = $doc->getElementsByTagName('InventoryDetails');
			foreach($inventoryDetails as $response) {
				// Todo:
			}
		}
	}
}
