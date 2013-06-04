<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Model_Quantity extends Mage_Core_Model_Abstract
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
	 * take a quantity request, reserve quantity in eb2c
	 *
	 * @return boolean true/false
	 */
	public function requestQuantity($qty=0, $product, $sku)
	{
		$isReserved = 1; // this is to simulate out of stock reponse from eb2c
		if ($qty > 0) {
			// connect to eb2c
			// check if request $qty is less than what's in eb2c
			// if request $qty is less, then proceed to reserve it
			// if request $qty greater than, alert the user the $qty requested is greater in what's in stock.
		}
		return $isReserved;
	}

	/**
	 * take an array of quote item id and product sku id
	 *
	 * return Dom Document of the QuantityRequestMessage request
	 */
	public function buildQuantityRequestMessage($items)
	{
		$domDocument = $this->_getHelper()->getDomDocument();
		$quantityRequestMessage = $domDocument->addElement('QuantityRequestMessage', null, $this->_getHelper()->getXmlNs())->firstChild;
		if ($items) {
			foreach ($items as $item) {
				try{
					$quantityRequestMessage->createChild(
						'QuantityRequest',
						null,
						array('lineId' => $item['id'], 'itemId' => $item['sku'])
					);
				}catch(Exception $e){
					Mage::logException($e);
				}
			}
		}
		return $domDocument;
	}

}
