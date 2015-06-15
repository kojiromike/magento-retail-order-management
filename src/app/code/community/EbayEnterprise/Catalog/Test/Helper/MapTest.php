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

class EbayEnterprise_Catalog_Test_Helper_MapTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * @var EbayEnterprise_Dom_Document
     */
    protected $_doc;

    public function setUp()
    {
        parent::setUp();
        $this->_doc = Mage::helper('eb2ccore')->getNewDomDocument();

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
    }
    public function tearDown()
    {
        parent::tearDown();
        $this->_doc = null;
    }
    /**
     * Test extractStringValue method for the following expectations
     * Expectation 1: this test is expected to call the EbayEnterprise_Eb2cCore_Helper_Map::extractStringValue method with a known
     *                DOMNodeList object the method is then expected to return a string value extract from the
     *                DOMNodeList object
     */
    public function testExtractStringValue()
    {
        $this->_doc->loadXML(
            '<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-2BCEC162</ClientItemId>
					</ItemId>
				</Item>
			</ItemMaster>'
        );
        $xpath = Mage::helper('eb2ccore')->getNewDomXPath($this->_doc);
        $this->assertSame(
            '45-2BCEC162',
            Mage::helper('ebayenterprise_catalog/map')->extractStringValue(
                $xpath->query('Item/ItemId/ClientItemId', $this->_doc->documentElement),
                Mage::getModel('catalog/product')
            )
        );
    }
    /**
     * Test extractBoolValue method for the following expectations
     * Expectation 1: this test is expected to call the EbayEnterprise_Eb2cCore_Helper_Map::extractBoolValue method with a known
     *                DOMNodeList object. The method is then expected to return a boolean value extract from the
     *                DOMNodeList object
     * Expectation 2: the EbayEnterprise_Eb2cCore_Helper_Data::parseBool method is expected to be given, the extract value
     *                from the DOMNodeList object object and return a boolean representative of the passed in string
     * @mock EbayEnterprise_Eb2cCore_Helper_Data::parseBool
     */
    public function testExtractBoolValue()
    {
        $this->_doc->loadXML(
            '<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<BaseAttributes>
						<IsDropShipped>true</IsDropShipped>
					</BaseAttributes>
				</Item>
			</ItemMaster>'
        );
        $xpath = Mage::helper('eb2ccore')->getNewDomXPath($this->_doc);

        $productHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
            ->disableOriginalConstructor()
            ->setMethods(array('parseBool'))
            ->getMock();
        $productHelperMock->expects($this->once())
            ->method('parseBool')
            ->will($this->returnValueMap(array(
                array('true', true)
            )));
        $this->replaceByMock('helper', 'eb2ccore', $productHelperMock);

        $this->assertSame(
            true,
            Mage::helper('ebayenterprise_catalog/map')->extractBoolValue(
                $xpath->query('Item/BaseAttributes/IsDropShipped', $this->_doc->documentElement),
                Mage::getModel('catalog/product')
            )
        );
    }
    /**
     * Test extractIntValue method for the following expectations
     * Expectation 1: this test is expected to call the EbayEnterprise_Eb2cCore_Helper_Map::extractIntValue method with a known
     *                DOMNodeList object the method is then expected to return a string value cast as an integer value
     *                DOMNodeList object
     */
    public function testExtractIntValue()
    {
        $this->_doc->loadXML(
            '<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ExtendedAttributes>
						<Buyer>
							<BuyerId>999</BuyerId>
						</Buyer>
					</ExtendedAttributes>
				</Item>
			</ItemMaster>'
        );
        $xpath = Mage::helper('eb2ccore')->getNewDomXPath($this->_doc);
        $this->assertSame(
            999,
            Mage::helper('ebayenterprise_catalog/map')->extractIntValue(
                $xpath->query('Item/ExtendedAttributes/Buyer/BuyerId', $this->_doc->documentElement),
                Mage::getModel('catalog/product')
            )
        );
    }
    /**
     * Test extractFloatValue method for the following expectations
     * Expectation 1: this test is expected to call the EbayEnterprise_Eb2cCore_Helper_Map::extractFloatValue method with a known
     *                DOMNodeList object the method is then expected to return a string value cast as an float value
     *                DOMNodeList object
     */
    public function testExtractFloatValue()
    {
        $this->_doc->loadXML(
            '<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ExtendedAttributes>
						<Price>3.98</Price>
					</ExtendedAttributes>
				</Item>
			</ItemMaster>'
        );
        $xpath = Mage::helper('eb2ccore')->getNewDomXPath($this->_doc);
        $this->assertSame(
            3.98,
            Mage::helper('ebayenterprise_catalog/map')->extractFloatValue(
                $xpath->query('Item/ExtendedAttributes/Price', $this->_doc->documentElement),
                Mage::getModel('catalog/product')
            )
        );
    }
    /**
     * return whatever got pass as parameter
     */
    public function testPassThrough()
    {
        $x = 'anything';
        $this->assertSame(
            $x,
            Mage::helper('ebayenterprise_catalog/map')->passThrough(
                $x,
                Mage::getModel('catalog/product')
            )
        );
    }
    /**
     * test summing the float values of a list of nodes
     */
    public function testExtractFloatSum()
    {
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML('<_><a>1.1</a><b>5.5</b><c>3.0</c></_>');
        $this->assertSame(
            9.6,
            Mage::helper('ebayenterprise_catalog/map')->extractFloatSum($doc->documentElement->childNodes)
        );
    }
    /**
     * Test getting a negative sum of amounts for use as a discount amount.
     */
    public function testDiscountSum()
    {
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML('<_><a>1.1</a><b>5.5</b><c>3.0</c></_>');
        $this->assertSame(
            -9.6,
            Mage::helper('ebayenterprise_catalog/map')->extractDiscountSum($doc->documentElement->childNodes)
        );
    }
    /**
     * Test extractStatusValue method for the following expectations
     * Expectation 1: this test is expected to call the EbayEnterprise_Catalog_Helper_Map::extractStatusValue method with a known
     *                DOMNodeList object the method is then expected to return a value for enabling magento product by first
     *                extracting the first value of the DOMNodeList and then checking if it is equal to 'active' and then returning
     *                the Mage_Catalog_Model_Product_Status::STATUS_ENABLED constant
     */
    public function testExtractStatusValueWhenActive()
    {
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML(
            '<Items>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<BaseAttributes>
						<ItemStatus>Active</ItemStatus>
					</BaseAttributes>
				</Item>
			</Items>'
        );
        $xpath = new DOMXPath($doc);
        $this->assertSame(
            Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            Mage::helper('ebayenterprise_catalog/map')->extractStatusValue(
                $xpath->query('Item/BaseAttributes/ItemStatus', $doc->documentElement),
                Mage::getModel('catalog/product')
            )
        );
    }
    /**
     * @see testExtractStatusValueWhenActive test, now testing when the extract string is not equal to 'active'
     */
    public function testExtractStatusValueWhenNotActive()
    {
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML(
            '<Items>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<BaseAttributes>
						<ItemStatus>Disabled</ItemStatus>
					</BaseAttributes>
				</Item>
			</Items>'
        );
        $xpath = new DOMXPath($doc);
        $this->assertSame(
            Mage_Catalog_Model_Product_Status::STATUS_DISABLED,
            Mage::helper('ebayenterprise_catalog/map')->extractStatusValue(
                $xpath->query('Item/BaseAttributes/ItemStatus', $doc->documentElement),
                Mage::getModel('catalog/product')
            )
        );
    }
    /**
     * Provide a possible node value from the feed and the Magento visibility it should map to.
     */
    public function provideNodeValueAndVisibility()
    {
        return [
            [1, Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH],
            [2, Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH],
            [3, Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH],
            [4, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH],
            ['Not Visible Individually', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH],
            ['Catalog', Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH],
            ['Search', Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH],
            ['Catalog, Search', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH],
            ['Bad Data', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH],
        ];
    }
    /**
     * Test extractVisibilityValue
     * GIVEN an expected int, WHEN passed to extractVisibilityValue THEN extractVisibilityValue returns the int
     * GIVEN an expected string, WHEN passed to extractVisibilityValue THEN extractVisibilityValue returns the
     * associated int
     * GIVEN an unexpected value, WHEN passed to extractVisibilityValue THEN extractVisibilityValue returns the product visibility
     * @param int|string
     * @param int
     * @param int
     * @dataProvider provideNodeValueAndVisibility
     */
    public function testExtractVisibilityValueWhenVisibilityBoth($nodeValue, $visibility, $productVisibility)
    {
        $nodes = $this->getMockBuilder('DOMNodeList')
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->getModelMock('catalog/product', ['getVisibility']);
        $product->expects($this->any())
            ->method('getVisibility')
            ->will($this->returnValue($productVisibility));
        $helper = $this->getHelperMock('eb2ccore/data', array('extractNodeVal'));
        $helper->expects($this->once())
            ->method('extractNodeVal')
            ->with($this->identicalTo($nodes))
            ->will($this->returnValue($nodeValue));

        $this->replaceByMock('helper', 'eb2ccore', $helper);

        $this->assertSame(
            $visibility,
            Mage::helper('ebayenterprise_catalog/map')->extractVisibilityValue($nodes, $product)
        );
    }
    /**
     * Test extracting product links. Should return a serialized array of
     * product links in the feed.
     */
    public function testExtractProductLinks()
    {
        $catalogId = 45;
        $helperMock = $this->getHelperMock('eb2ccore/data', array('getConfigModel'));
        $helperMock->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry(array(
                'catalogId' => $catalogId
            ))));
        $this->replaceByMock('helper', 'eb2ccore', $helperMock);

        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML(
            '<root>
				<ProductLink link_type="ES_Accessory" operation_type="Add">
					<LinkToUniqueId>45-12345</LinkToUniqueId>
				</ProductLink>
				<ProductLink link_type="ES_CrossSelling" operation_type="Delete">
					<LinkToUniqueId>23456</LinkToUniqueId>
				</ProductLink>
			</root>'
        );
        $nodes = $doc->getElementsByTagName('ProductLink');

        $links = array(
            array('link_type' => 'related', 'operation_type' => 'Add', 'link_to_unique_id' => '45-12345'),
            array('link_type' => 'crosssell', 'operation_type' => 'Delete', 'link_to_unique_id' => '45-23456'),
        );

        $map = $this->getHelperMock('ebayenterprise_catalog/map', array('_convertToMagentoLinkType'));
        $map->expects($this->exactly(2))
            ->method('_convertToMagentoLinkType')
            ->will($this->returnValueMap(array(
                array('ES_Accessory', 'related'),
                array('ES_CrossSelling', 'crosssell')
            )));

        $this->assertSame(
            serialize($links),
            $map->extractProductLinks(
                $nodes,
                Mage::getModel('catalog/product')
            )
        );
    }
    /**
     */
    public function testExtractProductLinksUnknownLink()
    {
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML('<root><ProductLink link_type="NO_CLUE_WHAT_THIS_IS" operation_type="Add"><LinkToUniqueId>45-23456</LinkToUniqueId></ProductLink></root>');
        $nodes = $doc->getElementsByTagName('ProductLink');

        $links = array();

        $map = $this->getHelperMock('ebayenterprise_catalog/map', array('_convertToMagentoLinkType'));
        $map->expects($this->once())
            ->method('_convertToMagentoLinkType')
            ->with($this->identicalTo('NO_CLUE_WHAT_THIS_IS'))
            ->will($this->throwException(new Mage_Core_Exception()));

        $this->assertSame(
            serialize($links),
            $map->extractProductLinks(
                $nodes,
                Mage::getModel('catalog/product')
            )
        );
    }
    /**
     * Test mapping related product link types through the config registry.
     */
    public function testConvertToMagentoLinkType()
    {
        $linkTypes = array('ES_Accessory' => 'related', 'ES_CrossSelling' => 'crosssell', 'ES_UpSelling' => 'upsell');

        $configRegistry = $this->getModelMockBuilder('eb2ccore/config_registry')
            ->disableOriginalConstructor()
            ->setMethods(array('getConfig'))
            ->getMock();
        $configRegistry->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValueMap(array(
                array('link_types_es_accessory', null, 'related'),
                array('link_types_es_crossselling', null, 'crosssell'),
                array('link_types_es_upselling', null, 'upsell'),
            )));

        $prodHelper = $this->getHelperMock('ebayenterprise_catalog/data', array('getConfigModel'));
        $prodHelper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($configRegistry));
        $this->replaceByMock('helper', 'ebayenterprise_catalog', $prodHelper);

        $helper = Mage::helper('ebayenterprise_catalog/map');
        foreach ($linkTypes as $ebcLink => $magentoLink) {
            $this->assertSame(
                $magentoLink,
                EcomDev_Utils_Reflection::invokeRestrictedMethod($helper, '_convertToMagentoLinkType', array($ebcLink))
            );
        }
    }
    /**
     * Test extractSkuValue method with the following expectations
     * Expectation 1: when this test invoked this method EbayEnterprise_Catalog_Helper_Map::extractSkuValue
     *                with a node list that the extract value doesn't have the catalog id, the called to
     *                the method EbayEnterprise_Catalog_Helper_Data::normalizeSku will prepend the catalog
     *                to the extract value
     */
    public function testExtractSkuValue()
    {
        $nodes = new DOMNodeList();
        $catalogId = '85';
        $sku = '847499';
        $result = $catalogId . '-' . $sku;

        $coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
            ->disableOriginalConstructor()
            ->setMethods(array('extractNodeVal', 'getConfigModel'))
            ->getMock();
        $coreHelperMock->expects($this->once())
            ->method('extractNodeVal')
            ->with($this->identicalTo($nodes))
            ->will($this->returnValue($sku));
        $coreHelperMock->expects($this->once())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry(array(
                'catalogId' => $catalogId
            ))));
        $this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

        $this->assertSame($result, Mage::helper('ebayenterprise_catalog/map')->extractSkuValue($nodes));
    }
    /**
     * Test extractProductTypeValue method for the following expectations
     * Expectation 1: when this test invoked the method EbayEnterprise_Catalog_Helper_Map::extractProductTypeValue with
     *                a DOMNodeList object and a Mage_Catalog_Model_Product object it will extract the type value
     *                and then call the mocked method EbayEnterprise_Catalog_Helper_Map::_isValidProductType method
     *                to make sure the value extracted match with the know type of magento product type
     * Expectation 2: the given product object set type id will be set with the extract method and also the
     *                the product object set type instance will be set with the return value of the
     *                static call to the product factory
     */
    public function testExtractProductTypeValue()
    {
        $value = 'configurable';
        $nodes = new DOMNodeList();

        $coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
            ->disableOriginalConstructor()
            ->setMethods(array('extractNodeVal'))
            ->getMock();
        $coreHelperMock->expects($this->once())
            ->method('extractNodeVal')
            ->with($this->identicalTo($nodes))
            ->will($this->returnValue($value));
        $this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

        $product = $this->getModelMockBuilder('catalog/product')
            ->disableOriginalConstructor()
            ->setMethods(array('setTypeId', 'setTypeInstance'))
            ->getMock();
        $product->expects($this->once())
            ->method('setTypeId')
            ->with($this->identicalTo($value))
            ->will($this->returnSelf());
        $product->expects($this->once())
            ->method('setTypeInstance')
            ->with($this->isInstanceOf('Mage_Catalog_Model_Product_Type_Abstract'), $this->identicalTo(true))
            ->will($this->returnSelf());

        $mapMock = $this->getHelperMockBuilder('ebayenterprise_catalog/map')
            ->disableOriginalConstructor()
            ->setMethods(array('_isValidProductType'))
            ->getMock();
        $mapMock->expects($this->once())
            ->method('_isValidProductType')
            ->with($this->identicalTo($value))
            ->will($this->returnValue(true));

        $this->assertSame($value, $mapMock->extractProductTypeValue($nodes, $product));
    }
    /**
     * Test _isValidProductType method for the following expectations
     * Expectation 1: when this test invoked the method EbayEnterprise_Catalog_Helper_Map::_isValidProductType
     *                with a given value it will check if the value in the six possible Magento product type
     *                it will return true if value match otherwise false
     */
    public function testIsValidProductType()
    {
        $testData = array(
            array('expect' => true, 'value' => 'simple'),
            array('expect' => false, 'value' => 'wrong'),
        );

        $map = Mage::helper('ebayenterprise_catalog/map');

        foreach ($testData as $data) {
            $this->assertSame($data['expect'], EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $map,
                '_isValidProductType',
                array($data['value'])
            ));
        }
    }
    /**
     * Test extractHtsCodesValue method for the following expectations
     * Expectation 1: this test will invoke the method EbayEnterprise_Catalog_Helper_Map::extractHtsCodesValue given
     *                a DOMNodeList object, the test then expect the nodelist object to be loop through
     *                and build an array containing key extract data and the return value would be  serialize string
     *                of such array.
     */
    public function testExtractHtsCodesValue()
    {
        $data = serialize(array(
            array('mfn_duty_rate' => '10', 'destination_country' => 'AU', 'restricted' => 'N', 'hts_code' => '6114.2'),
            array('mfn_duty_rate' => '12', 'destination_country' => 'AT', 'restricted' => 'N', 'hts_code' => '6114.20')
        ));

        $doc = Mage::helper('eb2ccore')->getNewDomDocument();

        $doc->loadXML(
            '<root>
				<htscodes>
					<HTSCode mfn_duty_rate="10" destination_country="AU" restricted="N">6114.2</HTSCode>
					<HTSCode mfn_duty_rate="12" destination_country="AT" restricted="N">6114.20</HTSCode>
				</htscodes>
			</root>'
        );

        $xpath = new DOMXPath($doc);

        $this->assertSame($data, Mage::helper('ebayenterprise_catalog/map')->extractHtsCodesValue($xpath->query(
            'htscodes/HTSCode',
            $doc->documentElement
        )));
    }
    /**
     * Test EbayEnterprise_Catalog_Helper_Map::extractAttributeSetValue when
     * encountered an Attribute Set that doesn't exist in magento it will use the
     * one that it currently set to.
     */
    public function testExtractAttributeSetValue()
    {
        $attributeSetId = 4;
        $nonExistsAttribute = null;
        $attributeSetName = 'ROM';

        $helper = $this->getHelperMock('ebayenterprise_catalog/data', array('getAttributeSetIdByName'));
        $helper->expects($this->once())
            ->method('getAttributeSetIdByName')
            ->with($this->identicalTo($attributeSetName))
            ->will($this->returnValue($nonExistsAttribute));
        $this->replaceByMock('helper', 'ebayenterprise_catalog', $helper);

        $product = Mage::getModel('catalog/product', array('attribute_set_id' => $attributeSetId));

        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML(
            "<root>
				<Item>
					<CustomAttributes>
						<Attribute name='AttributeSet'>
							<Value>$attributeSetName</Value>
						</Attribute>
					</CustomAttributes>
				</Item>
			</root>"
        );

        $xpath = Mage::helper('eb2ccore')->getNewDomXPath($doc);
        $nodes = $xpath->query(
            'Item/CustomAttributes/Attribute[@name="AttributeSet"]/Value',
            $doc->documentElement
        );
        $this->assertSame(
            $attributeSetId,
            Mage::helper('ebayenterprise_catalog/map')->extractAttributeSetValue($nodes, $product)
        );
    }
    /**
     * @see self::testExtractAttributeSetValue but this time we are testing that
     * when a known attribute set is found it will return the knowned attribute set
     * id in magento.
     */
    public function testExtractAttributeSetValueKnowAttributeSet()
    {
        $attributeSetId = 4;
        $knownAttributeSetId = 5;
        $attributeSetName = 'Luma';

        $helper = $this->getHelperMock('ebayenterprise_catalog/data', array('getAttributeSetIdByName'));
        $helper->expects($this->once())
            ->method('getAttributeSetIdByName')
            ->with($this->identicalTo($attributeSetName))
            ->will($this->returnValue($knownAttributeSetId));
        $this->replaceByMock('helper', 'ebayenterprise_catalog', $helper);

        $product = Mage::getModel('catalog/product', array('attribute_set_id' => $attributeSetId));

        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML(
            "<root>
				<Item>
					<CustomAttributes>
						<Attribute name='AttributeSet'>
							<Value>$attributeSetName</Value>
						</Attribute>
					</CustomAttributes>
				</Item>
			</root>"
        );

        $xpath = Mage::helper('eb2ccore')->getNewDomXPath($doc);
        $nodes = $xpath->query(
            'Item/CustomAttributes/Attribute[@name="AttributeSet"]/Value',
            $doc->documentElement
        );
        $this->assertSame(
            $knownAttributeSetId,
            Mage::helper('ebayenterprise_catalog/map')->extractAttributeSetValue($nodes, $product)
        );
    }
}
