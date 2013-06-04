<?php
/**
 * Mage_Sales_Quote_Item extended for Eb2c.
 *
 */
class TrueAction_Eb2c_Core_Model_Quote_Item extends Mage_Sales_Model_Quote_Item implements TrueAction_Eb2c_Core_Model_Item_Interface
{
	/**
	 * Return a dummy value to prove configuration, code and implementation skeleton are working.
	 */
	public function getBySku()
	{
		return 'All this does is prove this framework exists and can implement the Interface\n';
	}

	/**
	 * Validates Date Range Order - 'from' precedes 'to'. Uses strtotime() for comparison.
	 *
	 * @param from string xsd:dateTime (ISO 8601)
	 * @param to string xsd:dateTime (ISO 8601)
	 */
	private function _validateDateRangeOrder($from, $to)
	{
		$fromTime = strtotime($from);
		$toTime = strtotime($to);
		if( $fromTime > $toTime ) {
			return false;
		}
		return true;
	}

	/**
	 * Sets and validates the Delivery Window for an item.
	 *
	 * @param dateFrom string xsd:dateTime (ISO 8601)
	 * @param dateTo string xsd:dateTime (ISO 8601)
	 */
	public function setDeliveryWindow($dateFrom, $dateTo)
	{
		if( $this->_validateDateRangeOrder($dateFrom, $dateTo) ) {
			// Do the setting here.
			return true;
		}
		return false;
	}


	/**
	 * Sets and validates the Shipping Window for an item. 
	 *
	 * @param dateFrom string xsd:dateTime (ISO 8601)
	 * @param dateTo string xsd:dateTime (ISO 8601)
	 */
	public function setShippingWindow($dateFrom, $dateTo)
	{
		if( $this->_validateDateRangeOrder($dateFrom, $dateTo) ) {
			// Do the setting here.
			return true;
		}
		return false;
	}


	/**
	 * Retrieves a Delivery Window, returned as an array of 'from' and 'to'.
	 *
	 * @return array('from', 'to') dates in ISO 8601 format
	 */
	public function getDeliveryWindow()
	{
		// TODO: Return actual values.
		return array( 'from'=>'', 'to'=>'' );
	}

	/**
	 * Retrieves a Shipping Window, returned as an array of 'from' and 'to'.
	 *
	 * @return array('from', 'to') dates in ISO 8601 format
	 */
	public function getShippingWindow()
	{
		// TODO: Return actual values.
		return array( 'from'=>'', 'to'=>'' );
	}
}
