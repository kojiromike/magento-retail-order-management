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

class EbayEnterprise_Eb2cProduct_Model_Attributes
{
	// configuration path strings
	const ATTRIBUTES_CONFIG = 'eb2cproduct/attributes';
	const ATTRIBUTE_BASE_DATA = 'base_data';

	/**
	 * the attributes configuration
	 * @var array
	 */
	protected $_defaultAttributesConfig = array();

	/**
	 * array of attribute data records
	 * @var array
	 */
	protected $_attributeRecords = array();

	/**
	 * list of entity types to attach attributes to
	 * @var array
	 */
	protected $_entityTypes = array('catalog/product');

	/**
	 * mapping of config field name to model field name
	 * @var array
	 */
	protected $_fieldNameMap = array(
		'scope'         => 'is_global',
		'unique'        => 'is_unique',
		'input_type'    => 'frontend_input',
		'product_types' => 'apply_to',
		'label'         => 'frontend_label',
	);

	/**
	 * mapping of attribute model fields to functions needed to convert
	 * data from the configuration
	 * @var array
	 */
	protected $_valueFunctionMap = array(
		'is_global'           => '_formatScope',
		'default_value_yesno' => '_formatBoolean',
		'is_unique'           => '_formatBoolean',
		'default_value_date'  => '_formatDate',
	);

	/**
	 * mapping of configuration scope strings to the magento scope enumerations
	 * @var array
	 */
	protected static $_scopeMap = array(
		'website' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
		'store'   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	);

	/**
	 * return a list of entity type id's the attributes should be added to
	 * @return array
	 */
	public function getTargetEntityTypeIds()
	{
		$result = array();
		foreach ($this->_entityTypes as $entityType) {
			$result[] = (int) Mage::getModel($entityType)->getResource()->getTypeId();
		}
		return $result;
	}

	/**
	 * populate and get an array of attribute data records
	 * @return array
	 */
	public function getAttributesData()
	{
		$config  = $this->_loadDefaultAttributesConfig();
		foreach ($config['default'] as $attrCode => $attrConfig) {
			if (!isset($this->_attributeRecords[$attrCode])) {
				try {
						$this->_attributeRecords[$attrCode] = $this->_makeAttributeRecord($attrConfig);
				} catch (EbayEnterprise_Eb2cProduct_Model_Attributes_Exception $e) {
					Mage::helper('ebayenterprise_magelog')
						->logWarn("Error processing config for attribute '%s':\n%s", array($attrCode, $e));
				}
			}
		}
		return $this->_attributeRecords;
	}

	/**
	 * return true if the configured attributes should be applied to
	 * an entity type by the entity type id
	 * @param  int  $typeId
	 * @return boolean
	 */
	protected function _isValidEntityType($typeId)
	{
		return in_array((int) $typeId, $this->_getTargetEntityTypeIds(), true);
	}

	/**
	 * get an array suitable for saving an attribute model's frontend label
	 * @param  string $data
	 * @return array
	 */
	protected function _formatFrontendLabel($data)
	{
		return array($data, '', '', '', '');
	}

	/**
	 * get the field name in the attribute model for a field in the configuration
	 * @param  string $fieldName configured field name
	 * @return string
	 */
	protected function _getMappedFieldName($fieldName)
	{
		return isset($this->_fieldNameMap[$fieldName]) ? $this->_fieldNameMap[$fieldName] : $fieldName;
	}

	/**
	 * convert the scope string to the associated magento attribute scope value
	 * @param  string $data
	 * @throws EbayEnterprise_Eb2cProduct_Model_Attributes_Exception an invalid scope string is given
	 * @return int
	 */
	protected function _formatScope($data)
	{
		$scopeStr = strtolower((string) $data);
		if (!isset(self::$_scopeMap[$scopeStr])) {
			throw new EbayEnterprise_Eb2cProduct_Model_Attributes_Exception(
				'Invalid scope value "' . $scopeStr . '"'
			);
		}
		return self::$_scopeMap[$scopeStr];
	}

	/**
	 * convert a ',' delimited string to an array
	 * @param  string $data
	 * @return int
	 */
	protected function _formatArray($data)
	{
		return explode(',', $data);
	}

	/**
	 * convert a string to be suitable for magento boolean-type fields
	 * @see  http://php.net/manual/en/function.is-bool.php
	 * @param string
	 * @return true if the string in $data is interpretable as true
	 *         false otherwise
	 */
	protected function _formatBoolean($data)
	{
		return in_array(
			strtolower($data),
			array('true', '1', 'on', 'yes', 'y'),
			true
		);
	}

	/**
	 * convert data from the config to a form suitable for the specified
	 * field in the attribute model
	 * @param  string $fieldName
	 * @param  string $value
	 * @throws EbayEnterprise_Eb2cProduct_Model_Attributes_Exception if a mapped method doesn't exist
	 * @return string
	 */
	protected function _getMappedFieldValue($fieldName, $value)
	{
		if (isset($this->_valueFunctionMap[$fieldName])) {
			$funcName = $this->_valueFunctionMap[$fieldName];
			if (!method_exists($this, $funcName)) {
				throw new EbayEnterprise_Eb2cProduct_Model_Attributes_Exception(
					"invalid value-function map. $funcName is not a member of " . get_class($this)
				);
			}
			$value = $this->$funcName($value);
		}
		return $value;
	}

	/**
	 * get the name of the "default value" field for the frontend type
	 * @param  string $frontendType
	 * @return string default value field name
	 */
	protected function _getDefaultValueFieldName($frontendType)
	{
		switch (strtolower($frontendType)) {
			case 'boolean': return 'default_value_yesno';
			case 'date': return 'default_value_date';
			case 'select':
			case 'multiselect': return 'option';
			default: return 'default_value_text';
		}
	}

	/**
	 * return an array of the default attribute codes
	 * optionally, the list can be filtered to only include codes whose group is $groupFilter
	 * @param string $groupFilter
	 * @return array
	 */
	public function getDefaultAttributesCodeList($groupFilter=null)
	{
		Mage::helper('ebayenterprise_magelog')
			->logDebug("[%s] getDefaultAttributesCodeList called with %s", array(__CLASS__, $groupFilter));
		$result = array();
		$config = $this->_loadDefaultAttributesConfig();
		foreach ($config['default'] as $code => $data) {
			if (!$groupFilter || isset($data['group']) && $groupFilter === $data['group']) {
				$result[] = $code;
			}
		}
		return $result;
	}

	/**
	 * get an attribute model initializer array containing data extracted from the config.
	 * @param  array $fieldCfg
	 * @return array
	 */
	protected function _makeAttributeRecord(array $fieldCfg)
	{
		$record = $this->_getInitialData();
		foreach ($fieldCfg as $cfgField => $data) {
			if ($cfgField === 'default') {
				$inputType = $fieldCfg['input_type'];
				$fieldName = $this->_getDefaultValueFieldName($inputType);
			} else {
				$fieldName = $this->_getMappedFieldName($cfgField);
			}
			$value              = $this->_getMappedFieldValue($fieldName, $data);
			$record[$fieldName] = $value;
		}
		return $record;
	}

	/**
	 * load and store the configuration for the attributes
	 * @return array
	 */
	protected function _loadDefaultAttributesConfig()
	{
		if (!$this->_defaultAttributesConfig) {
			$this->_defaultAttributesConfig = Mage::getConfig()->getNode(self::ATTRIBUTES_CONFIG, 'default')
				->asCanonicalArray();
		}
		return $this->_defaultAttributesConfig;
	}

	/**
	 * get an array containing the defaults for an attribute.
	 * @return array
	 */
	protected function _getInitialData()
	{
		$config = $this->_loadDefaultAttributesConfig();
		return $config[self::ATTRIBUTE_BASE_DATA];
	}
}
