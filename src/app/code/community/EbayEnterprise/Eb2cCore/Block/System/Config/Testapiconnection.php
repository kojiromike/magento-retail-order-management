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

/**
 * Largely a clone of the Adminhtml Validate VAT block - slight changes to use
 * the proper templates and URLs.
 * @see Mage_Adminhtml_Block_Customer_System_Config_Validatevat
 * @codeCoverageIgnore Copy of Magento - no significant changes to add useful tests for
 */
class EbayEnterprise_Eb2cCore_Block_System_Config_Testapiconnection extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Make sure the template gets set appropriately
     * @return self
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('eb2ccore/system/config/testapiconnection.phtml');
        }
        return $this;
    }

    /**
     * Unset some non-related element parameters
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(array(
            'button_label' => Mage::helper('eb2ccore')->__($originalData['button_label']),
            'html_id' => $element->getHtmlId(),
            'ajax_url' => $this->getUrl('*/exchange_system_config_validate/validateapi', array('_current' => true))
        ));

        return $this->_toHtml();
    }
}
