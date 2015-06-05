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

class EbayEnterprise_Catalog_Helper_Itemmaster extends EbayEnterprise_Catalog_Helper_Pim
{
    // Max length of the SupplierPartNumber element in the export feed.
    const SUPPLIER_PART_NUMBER_MAX_LENGTH = 35;
    // Max length of the SupplierPrefix element in the export feed.
    const DROP_SHIP_SUPPLIER_PREFIX_MAX_LENGTH = 15;

    /**
     * Get a collection of color attribute options within the scope of a given
     * store view.
     * @param  int $storeId Store view context to get options in
     * @return Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection
     */
    protected function _getColorAttributeOptionsCollection($storeId)
    {
        return Mage::getResourceModel('eav/entity_attribute_option_collection')
            ->setAttributeFilter(
                Mage::getSingleton('eav/config')->getAttribute(
                    Mage_Catalog_Model_Product::ENTITY,
                    'color'
                )->getId()
            )
            ->setStoreFilter($storeId);
    }
    /**
     * Constructs Hierarchy Node
     * Items without Hierarchy can't be processed, so if we're empty we must throw an Exception
     * will return a DOMNode object
     * @param  string                              $attrValue
     * @param  string                              $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  DOMDocument             $doc
     * @throws EbayEnterprise_Catalog_Model_Pim_Product_Validation_Exception
     * @return DOMNode
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passHierarchy($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        $deptNumber     = $product->getHierarchyDeptNumber();
        $deptSubNumber  = $product->getHierarchySubdeptNumber();
        $classNumber    = $product->getHierarchyClassNumber();
        $subClassNumber = $product->getHierarchySubclassNumber();

        if (empty($deptNumber) || empty($deptSubNumber) || empty($classNumber) || empty($subClassNumber)) {
            throw new EbayEnterprise_Catalog_Model_Pim_Product_Validation_Exception(
                sprintf(
                    '%s SKU \'%s\' Missing Required Hierarchy fields.(%s/%s/%s/%s)',
                    __FUNCTION__,
                    $product->getSku(),
                    $deptNumber,
                    $deptSubNumber,
                    $classNumber,
                    $subClassNumber
                )
            );
        }

        $fragment = $doc->createDocumentFragment();
        $fragment->appendChild($doc->createElement('DeptNumber', $deptNumber));
        $fragment->appendChild($doc->createElement('DeptDescription', $product->getHierarchyDeptDescription()));
        $fragment->appendChild($doc->createElement('SubDeptNumber', $deptSubNumber));
        $fragment->appendChild($doc->createElement('SubDeptDescription', $product->getHierarchySubdeptDescription()));
        $fragment->appendChild($doc->createElement('ClassNumber', $classNumber));
        $fragment->appendChild($doc->createElement('ClassDescription', $product->getHierarchyClassDescription()));
        $fragment->appendChild($doc->createElement('SubClass', $subClassNumber));
        $fragment->appendChild($doc->createElement('SubClassDescription', $product->getHierarchySubclassDescription()));

        return $fragment;
    }
    /**
     * Translate a sku into Style, dependent upon the product type
     * which will return DOMNode object
     * @param  string                              $attrValue
     * @param  string                              $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  DOMDocument         $doc
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passStyle($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        $sourceProduct = $this->_getStyleSourceProduct($product);
        if ($sourceProduct) {
            $fragment = $doc->createDocumentFragment();
            $styleId = $fragment->appendChild($doc->createElement('StyleID'));
            $styleId->appendChild($this->passSKU($sourceProduct->getSku(), 'sku', $sourceProduct, $doc));
            $fragment->appendChild($doc->createElement('StyleDescription', $sourceProduct->getName()));
            return $fragment;
        }
        return null;
    }
    /**
     * Translate a hierarchy_subclass_number into SubClass
     * which will return DOMNode object
     * @param  string                              $attrValue
     * @param  string                              $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  DOMDocument         $doc
     * @return DOMNode
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passSubClass($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        $value = (int)0;
        if (!empty($attrValue)) {
            $value = $attrValue;
        }
        return $this->passString($value, $attribute, $product, $doc);
    }
    /**
     * Translate a status into ItemStatus
     * which will return DOMNode object
     * @param  string                              $attrValue
     * @param  string                              $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  DOMDocument         $doc
     * @return DOMNode|
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passItemStatus($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        $value = 'Active';
        if ($attrValue == Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
            $value = 'Inactive';
        }
        return $this->passString($value, $attribute, $product, $doc);
    }
    /**
     * Translate an item_type field.
     * which will return DOMNode object
     * @param  string                              $attrValue
     * @param  string                              $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  DOMDocument         $doc
     * @return DOMNode
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passItemType($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        $itemType = 'Merch';
        if (!empty($attrValue)) {
            $itemType = $attrValue;
        }
        return $this->passString($itemType, $attribute, $product, $doc);
    }
    /**
     * Translate a visibility into CatalogClass
     * which will return DOMNode object
     * @param  string                              $attrValue
     * @param  string                              $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  DOMDocument         $doc
     * @return DOMNode
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passCatalogClass($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        $value = 'regular';
        if ($attrValue == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
            $value = 'nosale';
        }
        return $this->passString($value, $attribute, $product, $doc);
    }
    /**
     * Places tax code into the BaseAttributes/TaxCode node
     * Items without TaxCode can't be processed, so if we're empty we must throw an Exception
     * will return a DOMNode object
     * @param  string                              $attrValue
     * @param  string                              $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  DOMDocument             $doc
     * @throws EbayEnterprise_Catalog_Model_Pim_Product_Validation_Exception
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passTaxCode($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        if (empty($attrValue)) {
            throw new EbayEnterprise_Catalog_Model_Pim_Product_Validation_Exception(
                sprintf('%s SKU \'%s\' Invalid/ Missing TaxCode.', __FUNCTION__, $product->getSku())
            );
        }
        return $this->passString($attrValue, $attribute, $product, $doc);
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
        // only allow numeric values to be included in the feed
        if (!is_numeric($attrValue)) {
            return null;
        }
        $fragment = $doc->createDocumentFragment();
        $unitCostNode = $doc->createElement('UnitCost', Mage::getModel('core/store')->roundPrice($attrValue));
        $currencyCodeAttr = $doc->createAttribute('currency_code');
        $currencyCodeAttr->value = Mage::app()->getStore()->getCurrentCurrencyCode();
        $unitCostNode->appendChild($currencyCodeAttr);
        $fragment->appendChild($unitCostNode);
        return $fragment;
    }
    /**
     * Get the color eav attribute option model used by the product.
     *
     * @param  Mage_Catalog_Model_Product $product
     * @return Mage_Eav_Model_Entity_Attribute_Option|null
     */
    private function _getColorOptionForProduct(Mage_Catalog_Model_Product $product)
    {
        $colorValue = $product->getColor();
        return $colorValue
            ? $this->_getColorAttributeOptionsCollection($product->getStoreId())->getItemById($colorValue)
            : null;
    }
    /**
     * return a DOMNode object containing Color Code/ Description nodes
     * @param  string                              $attrValue
     * @param  string                              $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  DOMDocument         $doc
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passColorCode($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        $colorOption = $this->_getColorOptionForProduct($product);
        return $colorOption
            ? $this->passString($colorOption->getDefaultValue(), $attribute, $product, $doc)
            : null;
    }
    /**
     * return a DOMNode object containing Color Code/ Description nodes
     * @param  string                              $attrValue
     * @param  string                              $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  DOMDocument         $doc
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passColorDescription($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        $colorOption = $this->_getColorOptionForProduct($product);
        return $colorOption
            ? $this->passString($colorOption->getValue(), $attribute, $product, $doc)
            : null;
    }
    /**
     * return a DOMNode object containing an ItemURL value.
     * @param  string                              $attrValue
     * @param  string                              $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  DOMDocument         $doc
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passItemURL($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        $fragment = $doc->createDocumentFragment();
        $itemUrlNode = $doc->createElement('ItemURL', $product->getProductUrl());
        $itemUrlTypeAttr = $doc->createAttribute('type');
        $itemUrlTypeAttr->value = 'webstore';
        $itemUrlNode->appendChild($itemUrlTypeAttr);
        $fragment->appendChild($itemUrlNode);
        return $fragment;
    }
    /**
     * Translate an inventory_manage_stock field.
     * which will return DOMNode object
     * @param  string                              $attrValue
     * @param  string                              $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  DOMDocument $doc
     * @return DOMNode
     */
    public function passSalesClass($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        $enum = empty($attrValue) ? 'stock' :
            Mage::getSingleton('eav/entity_attribute')
                ->loadByCode($product::ENTITY, $attribute)
                ->getSource()->getOptionText($attrValue);
        return $this->passString($enum, $attribute, $product, $doc);
    }
    /**
     * Translate a Gift Card
     * which will return DOMNode object, or null if this isn't a GiftCard
     * @param  string                              $attrValue
     * @param  string                              $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  DOMDocument         $doc
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passGiftCard($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        $fragment = $doc->createDocumentFragment();
        if ($product->getTypeId() == Enterprise_GiftCard_Model_Catalog_Product_Type_Giftcard::TYPE_GIFTCARD) {
            $fragment->appendChild($doc->createElement('GiftCardFacing', $product->getName()));
            $fragment->appendChild($doc->createElement('GiftCardTenderCode', $product->getGiftCardTenderCode()));
        }
        // MaxGCAmount element must always be present but may be empty. This
        // "should" be optional but currently is not. Once it is, this element
        // should only be included for gift cards and when there is a value to include.
        $fragment->appendChild($doc->createElement('MaxGCAmount', $product->getOpenAmountMax()));
        return $fragment;
    }
    /**
     * Translate the drop ship supplier prefix attribute. Should only be
     * included if the attribute has a value. Value will be truncated to 15
     * characters.
     * @param  string
     * @param  string
     * @param  Mage_Catalog_Model_Product
     * @param  DOMDocument
     * @return DOMNode|null
     */
    public function passDropShipSupplierPrefix($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        return $this->passStringIf(substr($attrValue, 0, self::DROP_SHIP_SUPPLIER_PREFIX_MAX_LENGTH), $attribute, $product, $doc);
    }
    /**
     * Translate the supplier part number attribute. Should only be included if
     * the attribute has a value. Value will be truncated to 35 characters.
     * @param  string
     * @param  string
     * @param  Mage_Catalog_Model_Product
     * @param  DOMDocument
     * @return DOMNode|null
     */
    public function passSupplierPartNumber($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        return $this->passStringIf(substr($attrValue, 0, self::SUPPLIER_PART_NUMBER_MAX_LENGTH), $attribute, $product, $doc);
    }
    /**
     * Static value for subscription enabled. Currently unsupported. Always
     * returns false.
     *
     * To be removed when element is made optional in the XSD.
     *
     * @param  string
     * @param  string
     * @param  Mage_Catalog_Model_Product
     * @param  DOMDocument
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passSubscriptionEligible($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        return $this->passString('false', $attribute, $product, $doc);
    }
    /**
     * Static value for the subscription type. Currently unsupported. Always
     * returns 'None'.
     *
     * To be removed when element is made optional in the XSD.
     *
     * @param  string
     * @param  string
     * @param  Mage_Catalog_Model_Product
     * @param  DOMDocument
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passSubscriptionType($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        return $this->passString('None', $attribute, $product, $doc);
    }
}
