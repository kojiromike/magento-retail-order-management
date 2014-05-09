<?php

class EbayEnterprise_Eb2cOrder_Overrides_Model_Eav_Entity_Type
	extends Mage_Eav_Model_Entity_Type
{
	/**
	 * Prepend Exchange client order id prefix.
	 *
	 * @param int $storeId
	 * @return string
	 */
	public function fetchNewIncrementId($storeId=null)
	{
		$incrementId = trim(parent::fetchNewIncrementId($storeId));
		$cfg = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));
		$ebcPrefix = trim($cfg->clientOrderIdPrefix);
		if ($ebcPrefix !== '' && $incrementId !== '') {
			$incrementId = $ebcPrefix . substr($incrementId, 1, strlen($incrementId)-1);
		}

		return $incrementId;
	}
}
