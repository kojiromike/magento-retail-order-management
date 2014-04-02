<?php
class EbayEnterprise_Eb2cProduct_Helper_Pim
{
	const DEFAULT_OPERATION_TYPE = 'Change';
	const MAX_SKU_LENGTH         = 15;
	const STRING_LIMIT           = 4000;
	/**
	 * return a cdata node from a given string value.
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument             $doc
	 * @return DOMNode|null
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getValueAsDefault($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		return $doc->createElement('Value', $attrValue);
	}
	/**
	 * call self::createStringNode passing it string truncate to on self::STRING_LIMIT and pass the given DOMDocument
	 * which will either return DOMNode object or a null value.
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument         $doc
	 * @return DOMNode|null
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function passString($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		return $this->createStringNode(substr($attrValue, 0, self::STRING_LIMIT), $doc);
	}
	/**
	 * De-normalized a given sku by calling EbayEnterprise_Eb2cCore_Helper_Data::denormalizeSku method and then calling
	 * the self::createStringNode method given the de-normalize sku and the given DOMDocument object in which
	 * will return a DOMNode object
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument             $doc
	 * @return DOMNode|null
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function passSKU($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		$catalogId = Mage::helper('eb2cproduct')->getConfigModel()->catalogId;
		$sku       = Mage::helper('eb2ccore')->denormalizeSku($attrValue, $catalogId);
		if (strlen($sku) > self::MAX_SKU_LENGTH) {
			throw new EbayEnterprise_Eb2cProduct_Model_Pim_Product_Validation_Exception(
				sprintf('%s SKU \'%s\' Exceeds max length.', __FUNCTION__, $sku)
			);
		}
		return $this->createStringNode($sku, $doc);
	}
	/**
	 * round the attrValue to two decimal point by calling the method Mage_Core_Model_Store::roundPrice given the attrValue
	 * which will return a rounded attrValue, than pass this attrValue to the method self::createTextNode as first parameter
	 * and the given DOMDocument object as second parameter which will return a DOMNode object
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument         $doc
	 * @return DOMNode|null
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function passPrice($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		return $this->createTextNode(Mage::getModel('core/store')->roundPrice($attrValue), $doc);
	}
	/**
	 * Call the Self::createDecimal method given the attrValue which will return a decimal value if the attriValue is numeric
	 * otherwise will return null if it null pass it to self::createTextNode will also return node but if return an actual
	 * decimal value a DOMNode object will be returned
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument         $doc
	 * @return DOMNode|null
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function passDecimal($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		return $this->createTextNode($this->createDecimal($attrValue), $doc);
	}
	/**
	 * the self::createDateTime method is called given the attrValue if it return a valid date time value then the method
	 * self::createTextNode will return a DOMNode object when invoked with the attrValue and DOMDocument object, however
	 * if self::createDateTime method return null than the self::createTextNode will return null
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument         $doc
	 * @return DOMNode|null
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function passDate($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		return $this->createTextNode($this->createDateTime($attrValue), $doc);
	}
	/**
	 * the method self::createInteger will be called given an attrValue it will return an integer value if the attrValue string
	 * is numeric other null. when it return an integer value this value is then pass to the method self::createTextNode method
	 * along with the given DOMDocument object in which will return a DOMNode object, but if a null is given to the method
	 * self:createTextNode it will return null
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument         $doc
	 * @return DOMNode|null
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function passInteger($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		return $this->createTextNode($this->createInteger($attrValue), $doc);
	}
	/**
	 * the method self::createBool will be invoked in this method given attrValue if the attribute value is the literal
	 * 'yes' the return value will be 'true' otherwise the return value will be 'false', this value will then passed to
	 * the method self::createTextNode then return a DOMNode object given the attrValue and DOMDocument object
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument         $doc
	 * @return DOMNode|null
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function passYesNoToBool($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		return $this->createTextNode($this->createBool($attrValue), $doc);
	}
	/**
	 * return a DOMAttr object containing the client id value
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument         $doc
	 * @return DOMAttr
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function passGsiClientId($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		$domAttribute = $this->_getDomAttr($doc, $attribute);
		$domAttribute->value = Mage::helper('eb2ccore/feed')->getClientId();
		return $domAttribute;
	}
	/**
	 * return DOMAttr object containing the default operation type value
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument         $doc
	 * @return DOMAttr
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function passOperationType($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		$domAttribute = $this->_getDomAttr($doc, $attribute);
		$domAttribute->value = self::DEFAULT_OPERATION_TYPE;
		return $domAttribute;
	}
	/**
	 * return a DOMAttr object containing catalog id value
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument         $doc
	 * @return DOMAttr
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function passCatalogId($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		$domAttribute = $this->_getDomAttr($doc, $attribute);
		$domAttribute->value = Mage::helper('eb2ccore/feed')->getCatalogId();
		return $domAttribute;
	}
	/**
	 * return a DOMAttr object containing store id value
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument         $doc
	 * @return DOMAttr
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function passStoreId($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		$domAttribute = $this->_getDomAttr($doc, $attribute);
		$domAttribute->value = Mage::helper('eb2ccore/feed')->getStoreId();
		return $domAttribute;
	}
	/**
	 * return a DOMNode object containing cost value.
	 * @param  string                              $attrValue
	 * @param  string                              $attribute
	 * @param  Mage_Catalog_Model_Product          $product
	 * @param  DOMDocument         $doc
	 * @return DOMNode|null
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function passUnitCost($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
	{
		$fragment = $doc->createDocumentFragment();
		/* With createElement, mapping to ExtendedAttributes/UnitCost, I end up with this:
    	 * <ExtendedAttributes>
		 *  <!-- ... other extended attributes ... -->
		 *   <UnitCost>
		 *		<UnitCost currency_code="USD"><![CDATA[200]]></UnitCost>
		 *	  </UnitCost>
    	 * </ExtendedAttributes>
		 * Which is to be expected, I'm create a 'UnitCost' here, AND mapping it to UnitCost.
		 * 
		 * Or, If I map to just ExtendedAttributes, I get 'extra' ExtendedAttributes:
		 * 
		 * <ExtendedAttributes>
		 *  <!-- All the other extended attributes are here -->
		 * </ExtendedAttributes>
		 * <ExtendedAttributes>
		 *	<UnitCost currency_code="USD"><![CDATA[200]]></UnitCost>
		 * </ExtendedAttributes>
		 */
		$unitCostNode = $doc->createElement('UnitCost', Mage::getModel('core/store')->roundPrice($attrValue));
//		$unitCostNode = $this->createStringNode(Mage::getModel('core/store')->roundPrice($attrValue), $doc);
		if (!$unitCostNode) {
			return null;
		}
		$currencyCodeAttr = $doc->createAttribute('currency_code');
		$currencyCodeAttr->value = 'USD';
		if (!$unitCostNode->appendChild($currencyCodeAttr)) {
			$x = $fragment->ownerDocument->saveXML();
		}
		$fragment->appendChild($unitCostNode);
		return $fragment;
	}
	/**
	 * given a DOMDocument and attribute name normalize the attribute create a DONAttr
	 * @param DOMDocument $doc
	 * @param string $nodeAttribute
	 * @return DOMAttr
	 */
	protected function _getDomAttr(DOMDocument $doc, $nodeAttribute)
	{
		return $doc->createAttribute(implode('_', array_filter(explode('_', $nodeAttribute))));
	}
	/**
	 * given a value if it is null return null otherwise a DOMNode
	 * @param string $value
	 * @return DOMNode | null
	 */
	public function createStringNode($value, DOMDocument $doc)
	{
		return is_null($value) ? null : $doc->createCDataSection($value);
	}
	/**
	 * given a value if it is null return null otherwise a DOMNode
	 * @param string $value
	 * @return DOMNode | null
	 */
	public function createTextNode($value, DOMDocument $doc)
	{
		return is_null($value) ? null : $doc->createTextNode($value);
	}
	/**
	 * given a string representing date time if the string is not is not empty return and integer date time
	 * @param string $value
	 * @return string | null
	 */
	public function createDateTime($value)
	{
		return !empty($value)? date('c', strtotime($value)) : null;
	}
	/**
	 * given a string representing integer value if the string is a numeric value cast it as integer otherwise return null
	 * @param string $value
	 * @return int | null
	 */
	public function createInteger($value)
	{
		return is_numeric($value)? (int) $value : null;
	}
	/**
	 * given a string representing decimal value if the string is a numeric value cast it as float otherwise return null
	 * @param string $value
	 * @return int | null
	 */
	public function createDecimal($value)
	{
		return is_numeric($value)? (float) $value : null;
	}
	/**
	 * given a string if it is 'yes' return 'true' otherwise 'false'
	 * @param string $value
	 * @return string
	 */
	public function createBool($value)
	{
		return (strtolower($value) === 'yes')? 'true' : 'false';
	}
}
