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
			$this->_helper = Mage::helper('eb2cinventory');
		}
		return $this->_helper;
	}

	/**
	 * Allocating all items brand new quote from eb2c.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to allocate inventory items in eb2c for
	 *
	 * @return string the eb2c response to the request.
	 */
	public function allocateQuoteItems($quote)
	{
		$allocationResponseMessage = '';
		try{
			// build request
			$allocationRequestMessage = $this->buildAllocationRequestMessage($quote);

			// make request to eb2c for quote items allocation
			$allocationResponseMessage = $this->_getHelper()->getCoreHelper()->callApi(
				$allocationRequestMessage,
				$this->_getHelper()->getOperationUri('allocate_inventory')
			);
		}catch(Exception $e){
			Mage::logException($e);
		}

		return $allocationResponseMessage;
	}

	/**
	 * Build  Allocation request.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XML from
	 *
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildAllocationRequestMessage($quote)
	{
		$domDocument = $this->_getHelper()->getDomDocument();
		$allocationRequestMessage = $domDocument->addElement('AllocationRequestMessage', null, $this->_getHelper()->getXmlNs())->firstChild;
		$allocationRequestMessage->setAttribute('requestId', $this->_getHelper()->getRequestId($quote->getEntityId()));
		$allocationRequestMessage->setAttribute('reservationId', $this->_getHelper()->getReservationId($quote->getEntityId()));
		if ($quote) {
			foreach($quote->getAllAddresses() as $addresses){
				if ($addresses){
					foreach ($addresses->getAllItems() as $item) {
						try{
							// creating quoteItem element
							$quoteItem = $allocationRequestMessage->createChild(
								'OrderItem',
								null,
								array('lineId' => $item->getId(), 'itemId' => $item->getSku())
							);

							// add quantity
							$quoteItem->createChild(
								'Quantity',
								(string) $item->getQty() // integer value doesn't get added only string
							);

							$shippingAddress = $quote->getShippingAddress();
							// creating shipping details
							$shipmentDetails = $quoteItem->createChild(
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

							// add ship to address Line 1
							$shipToAddress->createChild(
								'Line1',
								$shippingAddress->getStreet(1),
								null
							);

							// add ship to address City
							$shipToAddress->createChild(
								'City',
								$shippingAddress->getCity(),
								null
							);

							// add ship to address MainDivision
							$shipToAddress->createChild(
								'MainDivision',
								$shippingAddress->getRegion(),
								null
							);

							// add ship to address CountryCode
							$shipToAddress->createChild(
								'CountryCode',
								$shippingAddress->getCountryId(),
								null
							);

							// add ship to address PostalCode
							$shipToAddress->createChild(
								'PostalCode',
								$shippingAddress->getPostcode(),
								null
							);
						}catch(Exception $e){
							Mage::logException($e);
						}
					}
				}
			}
		}
		return $domDocument;
	}

	/**
	 * Parse allocation response XML.
	 *
	 * @param string $allocationResponseMessage the XML response from eb2c
	 *
	 * @return array, an associative array of response data
	 */
	public function parseResponse($allocationResponseMessage)
	{
		$allocationData = array();
		if (trim($allocationResponseMessage) !== '') {
			$doc = $this->_getHelper()->getDomDocument();

			// load response string XML from eb2c
			$doc->loadXML($allocationResponseMessage);
			$i = 0;
			$allocationResponse = $doc->getElementsByTagName('AllocationResponse');
			$allocationMessage = $doc->getElementsByTagName('AllocationResponseMessage');
			foreach($allocationResponse as $response) {
				$allocationData[] = array(
					'lineId' => $response->getAttribute('lineId'),
					'itemId' => $response->getAttribute('itemId'),
					'qty' => (int) $allocationResponse->item($i)->nodeValue,
					'reservation_id' => $allocationMessage->item(0)->getAttribute('reservationId'),
					'reservation_expires' => Mage::getModel('core/date')->date('Y-m-d H:i:s')
				);
				$i++;
			}
		}

		return $allocationData;
	}

	/**
	 * update quote with allocation response data.
	 *
	 * @param Mage_Sales_Model_Order $quote the quote we use to get allocation response from eb2c
	 * @param string $allocationData, a parse associative array of eb2c response
	 *
	 * @return array, error results of item that cannot be allocated
	 */
	public function processAllocation($quote, $allocationData)
	{
		$allocationResult = array();

		foreach ($allocationData as $data) {
			foreach ($quote->getAllItems() as $item) {
				// find the item in the quote
				if ((int) $item->getItemId() === (int) $data['lineId']) {
					// update quote with eb2c data.
					$result = $this->_updateQuoteWithEb2cAllocation($item, $data);
					if (trim($result) !== '') {
						$allocationResult[] = $result;
					}
				}
			}
		}

		return $allocationResult;
	}

	/**
	 * update quote with allocation response data.
	 *
	 * @param Mage_Sales_Model_Quote_Item $quoteItem the item to be updated with eb2c data
	 * @param array $quoteData the data from eb2c for the quote item
	 *
	 * @return string, the allocation error message for that particular inventory
	 */
	protected function _updateQuoteWithEb2cAllocation($quoteItem, $quoteData)
	{
		$results = '';
		// Set the message allocation failure
		if ($quoteData['qty'] > 0 && $quoteItem->getQty() > $quoteData['qty']) {
			$results = 'Sorry, we only have ' . $quoteData['qty'] . ' of item "' . $quoteItem->getSku() . '" in stock.';
		} elseif ($quoteData['qty'] <= 0) {
			$results = 'Sorry, item "' . $quoteItem->getSku() . '" out of stock.';
		}

		// get quote from quote-item
		$quote = $quoteItem->getQuote();

		// save reservation data to inventory detail
		$quoteItem->setEb2cReservationId($quoteData['reservation_id'])
			->setEb2cReservationExpires($quoteData['reservation_expires'])
			->setEb2cQtyReserved($quoteData['qty'])
			->save();

		$quote->save();

		return $results;
	}

	/**
	 * Rolling back allocation request.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XMLfrom
	 *
	 * @return string, the string xml message
	 */
	public function rollbackAllocation($quote)
	{
		// remove last allocations data from quote item
		$this->_emptyQuoteItemAllocationDataOnRollback($quote);

		$rollbackAllocationResponseMessage = '';
		try{
			// build request
			$rollbackAllocationRequestMessage = $this->buildRollbackAllocationRequestMessage($quote);

			// make request to eb2c for inventory rollback allocation
			$rollbackAllocationResponseMessage = $this->_getHelper()->getCoreHelper()->callApi(
				$rollbackAllocationRequestMessage,
				$this->_getHelper()->getOperationUri('rollback_allocation')
			);
		}catch(Exception $e){
			Mage::logException($e);
		}

		return $rollbackAllocationResponseMessage;
	}

	/**
	 * emptying any allocation data from the quote item on rollback.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XMLfrom
	 *
	 * @return void
	 */
	protected function _emptyQuoteItemAllocationDataOnRollback($quote)
	{
		// update remove allocation data from all quote item
		foreach ($quote->getAllItems() as $item) {
			$item->setEb2cReservationId(null)
				->setEb2cReservationExpires(null)
				->setEb2cQtyReserved(null)
				->save();
		}
	}

	/**
	 * checking if any quote item has allocation data.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to search all quote items for allocation data
	 *
	 * @return bool, true when allocation data is found on the quote item else false
	 */
	public function isRollback($quote)
	{
		$isRollback = false;
		// search all quote items for allocation data
		foreach ($quote->getAllItems() as $item) {
			if ($item->getQty() !== $item->getEb2cQtyReserved()) {
				$isRollback = true;
				break;
			}
		}
		return $isRollback;
	}

	/**
	 * Build  Rollback Allocation request.
	 *
	 * @param Mage_Sales_Model_Quote $quote, the quote to generate request XML from
	 *
	 * @return DOMDocument The XML document, to be sent as request to eb2c.
	 */
	public function buildRollbackAllocationRequestMessage($quote)
	{
		$domDocument = $this->_getHelper()->getDomDocument();
		$rollbackAllocationRequestMessage = $domDocument->addElement('RollbackAllocationRequestMessage', null, $this->_getHelper()->getXmlNs())->firstChild;
		$rollbackAllocationRequestMessage->setAttribute('requestId', $this->_getHelper()->getRequestId($quote->getEntityId()));
		$rollbackAllocationRequestMessage->setAttribute('reservationId', $this->_getHelper()->getReservationId($quote->getEntityId()));

		return $domDocument;
	}
}
