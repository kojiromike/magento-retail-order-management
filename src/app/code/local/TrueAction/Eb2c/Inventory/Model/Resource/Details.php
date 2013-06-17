<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Inventory_Model_Resource_Details extends Mage_Sales_Model_Resource_Abstract
{
	/**
	 * Initialize table nad PK name
	 *
	 */
	protected function _construct()
	{
		$this->_init('eb2cinventory/details', 'detail_id');
	}

	/**
	 * Load inventory details by quote item id
	 *
	 * @throws Mage_Core_Exception
	 *
	 * @param TrueAction_Eb2c_Inventory_Model_Details $details
	 * @param int $itemId
	 * @param bool $testOnly
	 * @return TrueAction_Eb2c_Inventory_Model_Resource_Details
	 */
	public function loadByQuoteItemId(TrueAction_Eb2c_Inventory_Model_Details $details, $itemId, $testOnly=false)
	{
		$adapter = $this->_getReadAdapter();
		$select  = $adapter->select()
			->from($this->getMainTable(), array('details_batch_id'))
			->where("item_id = '" . (int) $itemId . "'");

		$detailId = $adapter->fetchOne($select);
		if ($detailId) {
			$this->load($details, $detailId);
		} else {
			$details->setData(array());
		}

		return $this;
	}
}
