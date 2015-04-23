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

class EbayEnterprise_Eb2cTax_Model_Validation_Orderitem
{
	const ORDER_ITEM_XPATH = '//a:ShipGroup/a:Items/a:OrderItem';

	/** @var EbayEnterprise_Dom_Document */
	protected $_requestDoc;
	/** @var EbayEnterprise_Dom_Document */
	protected $_responseDoc;
	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_helper;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_context;
	/** @var array */
	protected $_orderItemXPathMap = [
		'item_id' => 'string(./a:ItemId)',
		'line_number' => 'string(./@lineNumber)',
		'quantity' => 'string(./a:Quantity)',
		'unit_price' => 'string(./a:Pricing/a:Merchandise/a:UnitPrice)',
		'shipping' => [
			'amount' => 'string(./a:Pricing/a:Shipping/a:Amount)',
		],
		'item_desc' => 'string(./a:ItemDesc)',
		'hts_code' => 'string(./a:HTSCode)',
		'merchandise' => [
			'amount' => 'string(./a:Pricing/a:Merchandise/a:Amount)',
		],
	];

	/**
	 * @param array $initParams Must have this key:
	 *                          - 'request_doc' => EbayEnterprise_Dom_Document
	 *                          - 'response_doc' => EbayEnterprise_Dom_Document
	 *                          - 'helper' => EbayEnterprise_Eb2cCore_Helper_Data
	 *                          - 'logger' => EbayEnterprise_MageLog_Helper_Data
	 *                          - 'context' => EbayEnterprise_MageLog_Helper_Context
	 */
	public function __construct(array $initParams=[])
	{
		list($this->_requestDoc, $this->_responseDoc, $this->_helper, $this->_logger, $this->_context) = $this->_checkTypes(
			$initParams['request_doc'],
			$initParams['response_doc'],
			$this->_nullCoalesce($initParams, 'helper', Mage::helper('eb2ccore')),
			$this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
			$this->_nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context'))
		);
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param  EbayEnterprise_Dom_Document
	 * @param  EbayEnterprise_Dom_Document
	 * @param  EbayEnterprise_Eb2cCore_Helper_Data
	 * @param  EbayEnterprise_MageLog_Helper_Data
	 * @param  EbayEnterprise_MageLog_Helper_Context
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_Dom_Document $requestDoc,
		EbayEnterprise_Dom_Document $responseDoc,
		EbayEnterprise_Eb2cCore_Helper_Data $helper,
		EbayEnterprise_MageLog_Helper_Data $logger,
		EbayEnterprise_MageLog_Helper_Context $context
	) {
		return [$requestDoc, $responseDoc, $helper, $logger, $context];
	}

	/**
	 * Return the value at field in array if it exists. Otherwise, use the default value.
	 *
	 * @param  array
	 * @param  string | int $field Valid array key
	 * @param  mixed
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}

	/**
	 * Extract Tax Request data from payload, extract Tax Response data
	 * from payload and then compare them against each other, if all is
	 * match exactly return true otherwise return false.
	 *
	 * @return bool
	 */
	public function validate()
	{
		$requestData = $this->_extractTaxOrderItems($this->_requestDoc);
		$responseData = $this->_extractTaxOrderItems($this->_responseDoc);
		return $this->_isMatch($requestData, $responseData);
	}

	/**
	 * Use the passed in collection of request object to validate against the passed in
	 * collection of response object.
	 *
	 * @param  Varien_Data_Collection
	 * @param  Varien_Data_Collection
	 * @return bool
	 */
	protected function _isMatch(Varien_Data_Collection $requestCollection, Varien_Data_Collection $responseCollection)
	{
		foreach ($requestCollection as $request) {
			$sku = $request->getItemId();
			$response = $responseCollection->getItemByColumnValue('item_id', $sku);
			if (!$response) {
				$this->_logSkuNotFoundInResponse($sku);
				return false;
			} elseif (!$this->_isItemMatch($request, $response, $this->_orderItemXPathMap)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Validate the data from the passed request against the passed in response using the
	 * passed in map of order item keys, use recursion to validate sub-mapped field.
	 *
	 * @param  Varien_Object
	 * @param  Varien_Object
	 * @param  array
	 * @param  bool
	 * @return bool
	 */
	protected function _isItemMatch(Varien_Object $request, Varien_Object $response, array $map, $result=true)
	{
		if ($result) {
			foreach ($map as $key => $expression) {
				$requestValue = $this->_getVarienValue($key, $request);
				$responseValue = $this->_getVarienValue($key, $response);
				if (is_array($expression)) {
					$result = $this->_isItemMatch($requestValue, $responseValue, $expression, $result);
				} elseif(!$this->_isItemValueMatch($requestValue, $responseValue)) {
					$this->_logMissingDataInResponse($key, $requestValue, $expression);
					$result = false;
				}
			}
		}
		return $result;
	}

	/**
	 * @param  string
	 * @param  Varien_Object
	 * @return string | null | Varien_Object
	 */
	protected function _getVarienValue($key, Varien_Object $object)
	{
		return $object->getData($key);
	}

	/**
	 * @param  string | null | Varien_Object
	 * @param  string | null | Varien_Object
	 * @return bool
	 */
	protected function _isItemValueMatch($requestValue, $responseValue)
	{
		return ($requestValue === $responseValue);
	}

	/**
	 * @param  EbayEnterprise_Dom_Document
	 * @return Varien_Data_Collection
	 */
	protected function _extractTaxOrderItems(EbayEnterprise_Dom_Document $doc)
	{
		$xpath = $this->_getDocDomXPath($doc);
		$collection = $this->_helper->getNewVarienDataCollection();
		foreach ($this->_queryXPath($xpath, static::ORDER_ITEM_XPATH) as $orderItem) {
			$collection->addItem($this->_extractItem($orderItem, $xpath, $this->_orderItemXPathMap));
		}
		return $collection;
	}

	/**
	 * @param  EbayEnterprise_Dom_Document
	 * @param  DOMNode
	 * @param  array
	 * @return Varien_Object
	 */
	protected function _extractItem(DOMNode $item, DOMXPath $xpath, array $map)
	{
		return new Varien_Object($this->_extractData($item, $xpath, $map));
	}

	/**
	 * @param  EbayEnterprise_Dom_Document
	 * @param  DOMNode
	 * @param  array
	 * @return array
	 */
	protected function _extractData(DOMNode $item, DOMXPath $xpath, array $map)
	{
		$extractData = [];
		foreach ($map as $key => $expression) {
			if (is_array($expression)) {
				$extractData[$key] = $this->_extractItem($item, $xpath, $expression);
			} else {
				$extractData[$key] = $this->_evaluateXPath($xpath, $item, $expression);
			}
		}
		return $extractData;
	}

	/**
	 * @param  DOMXPath
	 * @return DOMNodeList
	 */
	protected function _queryXPath(DOMXPath $xpath, $expression)
	{
		return $xpath->query($expression);
	}

	/**
	 * @param  DOMXPath
	 * @param  DOMNode
	 * @return DOMNodeList
	 */
	protected function _evaluateXPath(DOMXPath $xpath, DOMNode $node, $expression)
	{
		return $xpath->evaluate($expression, $node);
	}

	/**
	 * @param  EbayEnterprise_Dom_Document
	 * @return DOMXPath
	 */
	protected function _getDocDomXPath(EbayEnterprise_Dom_Document $doc)
	{
		$xpath = $this->_helper->getNewDomXPath($doc);
		$xpath->registerNamespace('a', $doc->documentElement->namespaceURI);
		return $xpath;
	}

	/**
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return self
	 */
	protected function _logMissingDataInResponse($itemKey, $keyValue, $xpathExpr)
	{
		$logData = ['item_key' => $itemKey, 'key_value' => $keyValue, 'xpath_expression' => $xpathExpr];
		$logMessage = '{item_key} "{key_value}" not found in the response for {xpath_expression}';
		$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
		return $this;
	}

	/**
	 * @param  string
	 * @return self
	 */
	protected function _logSkuNotFoundInResponse($sku)
	{
		$logMessage = 'SKU "{sku}" not found in the response';
		$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, ['sku' => $sku]));
		return $this;
	}
}
