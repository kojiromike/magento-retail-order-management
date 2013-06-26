<?php
/**
 * replacement for the default magento tax helper.
 */
class TrueAction_Eb2c_Tax_Overrides_Helper_Data extends Mage_Tax_Helper_Data
{
	protected $_service             = 'taxes';
	protected $_operation           = 'quote';
	protected $_responseFormat      = 'xml';

	protected $_coreHelper          = null;

	public function __construct()
	{
		$this->_coreHelper = Mage::helper('eb2ccore');
	}

	/**
	 * send a request and return the response
	 * @param  TrueAction_Eb2c_Tax_Model_Request $request
	 * @return TrueAction_Eb2c_Tax_Model_Response
	 */
	public function sendRequest(TrueAction_Eb2c_Tax_Model_Request $request, $store = null)
	{
		$response = $this->_coreHelper->callApi(
			$request->getDocument(),
			$this->_coreHelper->apiUri(
				$this->_service,
				$this->_operation,
				array(),
				$this->_responseFormat
			),
			$store
		);
		return Mage::getModel('eb2ctax/response', array(
			'xml' => $response,
			'request' => $request
		));
	}

	/**
	 * get the default namespace
	 * @param  Mage_Core_Model_Store $store
	 * @return int
	 */
	public function getNamespaceUri($store = null)
	{
		return Mage::getStoreConfig('eb2ctax/api/namspace_uri', $store);
	}
}