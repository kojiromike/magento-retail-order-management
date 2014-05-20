<?php
interface EbayEnterprise_Eb2cCore_Helper_Interface
{
	/**
	 * Form the Configuration Registry model for your module, and return it
	 * @param mixed $store optional but can be null, a store entity id or store object
	 * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
	 */
	public function getConfigModel($store=null);
}
