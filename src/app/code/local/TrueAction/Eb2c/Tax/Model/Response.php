<?php
/**
 * reads the response from the TaxDutyRequest.
 */
class TrueAction_Eb2c_Tax_Model_Response extends Mage_Core_Model_Abstract
{
	/**
	 * the dom document object for the response
	 * @var TrueAction_Dom_Document
	 */
	protected $_doc = null;

	/**
	 * result objects parsed from the response
	 * @var array
	 */
	protected $_responseItems = array();

	/**
	 * skus of OrderItem elements that passed validation
	 * @var array(string)
	 */
	protected $_validSkus = array();

	/**
	 * is the response valid
	 * @var boolean
	 */
	protected $_isValid   = false;

	/**
	 * namespace uri of the root element.
	 * @var string
	 */
	protected $_namespaceUri   = '';

	protected function _construct()
	{
		$this->_doc = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$this->_doc->preserveWhiteSpace = false;
		if ($this->hasXml()) {
			$this->_doc->loadXML($this->getXml());
			$this->_namespaceUri =  $this->_doc->documentElement->namespaceURI;
			$this->_extractResults();
		}
	}

	/**
	 * get the result records of the request
	 * @return array(TrueAction_Eb2c_Tax_Model_Tax)
	 */
	public function getResponseItems()
	{
		return $this->_responseItems;
	}

	/**
	 * @see self::$_isValid
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->_isValid;
	}

	/**
	 * get the address using the value from the ref attribute.
	 * @param  string $idRef
	 * @return Mage_Sales_Model_Quote_Address
	 */
	protected function _getAddress($idRef)
	{
		$address = Mage::getModel('sales/quote_address')->load($idRef);
		if (!$address->getId())
		{
			$message = "Address referenced by '$idRef' could not be verified in the original request";
			Mage::log($message, Zend_Log::DEBUG);
			$address = null;
		}
		return $address;
	}

	/**
	 * generate tax quote records with data extracted from the response.
	 */
	protected function _extractResults()
	{
		$xpath = new DOMXPath($this->_doc);
		// namespace variable
		$xpath->registerNamespace('a', $this->_namespaceUri);
		$root = $this->_doc->documentElement;
		$mailingAddresses = $xpath->query(
			'/a:Shipping/a:Destinations/a:MailingAddress',
			$root
		);
		$shipGroups = $xpath->query(
			'a:Shipping/a:ShipGroups/a:ShipGroup',
			$root
		);
		foreach ($shipGroups as $shipGroup) {
			$address = $this->_getAddress($shipGroup->getAttribute('id'));
			if (!is_null($address)) {
				return;
			}
			$responseSkus = array();
			// foreach item
			$items = $xpath->query('//a:Items/a:OrderItem', $shipGroup);
			foreach ($items as $item) {
				$orderItem = Mage::getModel('eb2ctax/response_orderitem', array(
					'node' => $item,
					'namespace_uri' => $this->_namespaceUri
				));
				if ($orderItem->isValid()) {
					$itemKey = $orderItem->getSku();
					$this->_responseItems[$itemKey] = $orderItem;
				}
			}
		}
		// foreach destination
		// verify data
	}

	/**
	 */
	{
		$xpath = new DOMXPath($this->_doc);
		$xpath->registerNamespace('a', $this->_namespaceUri);

		}
	}
}