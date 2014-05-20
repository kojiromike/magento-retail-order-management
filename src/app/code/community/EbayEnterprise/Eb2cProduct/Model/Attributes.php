<?php
class EbayEnterprise_Eb2cProduct_Model_Attributes
{
	// configuration path strings
	const ATTRIBUTES_CONFIG = 'eb2cproduct/attributes';
	const ATTRIBUTE_BASE_DATA = 'eb2cproduct/attributes/base_data';

	/**
	 * the attributes configuration
	 * @var Mage_Core_Model_Config
	 */
	protected $_defaultAttributesConfig = null;

	/**
	 * array of attribute data records
	 * @var array
	 */
	protected $_attributeData = array();

	/**
	 * list of entity types to attach attributes to.
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

	protected $_valueFunctionMap = array(
		'is_global'           => '_formatScope',
		'default_value_yesno' => '_formatBoolean',
		'is_unique'           => '_formatBoolean',
		'default_value_date'  => '_formatDate',
	);

	protected static $_scopeMap = array(
		'website' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
		'store'   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	);

	/**
	 * return a list of entity type id's the attributes should be added to.
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
	 * populate and get an array of attribute data records.
	 * @return array
	 */
	public function getAttributesData()
	{
		$config  = $this->_loadDefaultAttributesConfig();
		foreach ($config->getNode('default')->children() as $attrCode => $attrConfig) {
			if (!isset($this->_attributeData[$attrCode])) {
				try {
						$this->_attributeData[$attrCode] = $this->_makeAttributeRecord($attrConfig);
				} catch (EbayEnterprise_Eb2cProduct_Model_Attributes_Exception $e) {
					Mage::helper('ebayenterprise_magelog')
						->logWarn("Error processing config for attribute '%s':\n%s", array($attrCode, $e));
				}
			}
		}
		return $this->_attributeData;
	}

	/**
	 * return true if the attribute set is an entity type that
	 * should have the attributes applied.
	 * @param  int  $typeId
	 * @return boolean
	 */
	protected function _isValidEntityType($typeId)
	{
		return in_array((int) $typeId, $this->_getTargetEntityTypeIds(), true);
	}

	/**
	 * convert the frontend label into an an array.
	 * @param  Varien_SimpleXml_Element $data
	 * @return array
	 */
	protected function _formatFrontendLabel($data)
	{
		return array((string) $data, '', '', '', '');
	}

	/**
	 * get the attribute model's field name associated with the config field name.
	 * @param  string $fieldName
	 * @return string
	 */
	protected function _getMappedFieldName($fieldName)
	{
		return isset($this->_fieldNameMap[$fieldName]) ? $this->_fieldNameMap[$fieldName] : $fieldName;
	}

	/**
	 * get the associated value for the scope string
	 * @param  Varien_SimpleXml_Element $data
	 * @return int
	 */
	protected function _formatScope($data)
	{
		$scopeStr = strtolower((string) $data);
		if (!isset(self::$_scopeMap[$scopeStr])) {
			// @codeCoverageIgnoreStart
			throw new EbayEnterprise_Eb2cProduct_Model_Attributes_Exception(
				'Invalid scope value "' . $scopeStr . '"'
			);
		}
		// @codeCoverageIgnoreEnd
		return self::$_scopeMap[$scopeStr];
	}

	/**
	 * convert the ',' delimited list into an array.
	 * @param  Varien_SimpleXml_Element $data
	 * @return int
	 */
	protected function _formatArray($data)
	{
		return explode(',', (string) $data);
	}

	/**
	 * @see  http://php.net/manual/en/function.is-bool.php
	 * @param Varien_SimpleXml_Element
	 * @return 1 if the string in $data is interpretable as true.
	 *         0 otherwise
	 */
	protected function _formatBoolean($data)
	{
		return in_array(
			strtolower((string) $data),
			array('true', '1', 'on', 'yes', 'y'),
			true
		) ? 1 : 0;
	}

	/**
	 * convert data from the config to a form that can be set to the specified
	 * field on the attribute model.
	 * @param  string                   $fieldName
	 * @param  Varien_SimpleXml_Element $data
	 * @return string
	 */
	protected function _getMappedFieldValue($fieldName, Varien_SimpleXml_Element $data)
	{
		$value = (string) $data;
		if (isset($this->_valueFunctionMap[$fieldName])) {
			$funcName = $this->_valueFunctionMap[$fieldName];
			if (!method_exists($this, $funcName)) {
				// @codeCoverageIgnoreStart
				throw new EbayEnterprise_Eb2cProduct_Model_Attributes_Exception(
					"invalid value-function map. $funcName is not a member of " . get_class($this)
				);
			}
			// @codeCoverageIgnoreEnd
			$value = $this->$funcName($data);
		}
		return $value;
	}

	/**
	 * get the name of the default value field for based on the frontend type.
	 * @param  string $frontendType
	 * @return string default value field name.
	 */
	protected function _getDefaultValueFieldName($frontendType)
	{
		switch (strtolower($frontendType)) {
			case 'boolean': return 'default_value_yesno';
			case 'date': return 'default_value_date';
			case 'select':
			case 'multiselect': return 'option'; // No $fieldName here.
			default: return 'default_value_text';
		}
	}

	/**
	 * return an array of the default attribute codes.
	 * optionally, the list can be filtered to only include codes whose group is $groupFilter.
	 * @param string $groupFilter
	 * @return array
	 */
	public function getDefaultAttributesCodeList($groupFilter=null)
	{
		Mage::helper('ebayenterprise_magelog')
			->logDebug("[%s] getDefaultAttributesCodeList called with %s", array(__CLASS__, $groupFilter));
		$result = array();
		// load the attributes from the config.
		$config = $this->_loadDefaultAttributesConfig();
		// loop through the attributes and return the list of attribute names as an array.
		$default = $config->getNode('default');
		foreach ($default->children() as $code => $node) {
			if (!$groupFilter) {
				$result[] = $code;
			} elseif ($node->group && $groupFilter === (string) $node->group) {
				$result[] = $code;
			}
		}
		// TODO: perhaps store it in a cache?
		return $result;
	}

	/**
	 * get an array to initialize an attribute model with data extracted from the config.
	 * @param  Varien_SimpleXml_Element $fieldCfg
	 * @return Mage_Catalog_Model_Eav_Entity_Attribute
	 */
	protected function _makeAttributeRecord(Varien_SimpleXml_Element $fieldCfg)
	{
		$record = $this->_getInitialData();
		foreach ($fieldCfg->children() as $cfgField => $data) {
			if ($cfgField === 'default') {
				// @hack: Code style checker doesn't like underscores. That's right,
				// but sometimes we have to deal with data that has underscores.
				$underScoreInputType = 'input_type';
				$inputType = (string) $fieldCfg->$underScoreInputType;
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
	 * load our attribute configuration into a mage config object.
	 * @return Mage_Core_Model_Config_Base
	 */
	protected function _loadDefaultAttributesConfig()
	{
		if (!$this->_defaultAttributesConfig) {
			$config = Mage::getModel('core/config')
				->setXml(Mage::getConfig()->getNode(self::ATTRIBUTES_CONFIG));
			$this->_defaultAttributesConfig = $config;
		}
		return $this->_defaultAttributesConfig;
	}

	/**
	 * get an array containing the defaults for an attribute.
	 * @return array
	 */
	protected function _getInitialData()
	{
		return Mage::helper('eb2ccore')->getConfigData(self::ATTRIBUTE_BASE_DATA);
	}
}
