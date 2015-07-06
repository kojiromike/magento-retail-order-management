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
    /** @var EbayEnterprise_Dom_Document */
    protected $doc;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_Catalog_Helper_Map */
    protected $mapHelper;
    /** @var Mage_Catalog_Model_Product */
    protected $product;

    public function setUp()
    {
        parent::setUp();
        $this->coreHelper = Mage::helper('eb2ccore');
        $this->product = Mage::getModel('catalog/product');
        $this->doc = $this->coreHelper->getNewDomDocument();

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
        $this->mapHelper = Mage::helper('ebayenterprise_catalog/map');
    }
    public function tearDown()
    {
        parent::tearDown();
        $this->doc = null;
    }
    /**
     * Test extractStringValue method for the following expectations
     * Expectation 1: this test is expected to call the EbayEnterprise_Eb2cCore_Helper_Map::extractStringValue method with a known
     *                DOMNodeList object the method is then expected to return a string value extract from the
     *                DOMNodeList object
     */
    public function testExtractStringValue()
    {
        $this->doc->loadXML(
            '<ItemMaster>
                <Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
                    <ItemId>
                        <ClientItemId>45-2BCEC162</ClientItemId>
                    </ItemId>
                </Item>
            </ItemMaster>'
        );
        $xpath = $this->coreHelper->getNewDomXPath($this->doc);
        $this->assertSame(
            '45-2BCEC162',
            $this->mapHelper->extractStringValue(
                $xpath->query('Item/ItemId/ClientItemId', $this->doc->documentElement),
                $this->product
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
        $this->doc->loadXML(
            '<ItemMaster>
                <Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
                    <BaseAttributes>
                        <IsDropShipped>true</IsDropShipped>
                    </BaseAttributes>
                </Item>
            </ItemMaster>'
        );
        $xpath = $this->coreHelper->getNewDomXPath($this->doc);

        $coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
            ->disableOriginalConstructor()
            ->setMethods(['parseBool'])
            ->getMock();
        $coreHelperMock->expects($this->once())
            ->method('parseBool')
            ->will($this->returnValueMap([
                ['true', true]
            ]));

        /** @var EbayEnterprise_Catalog_Helper_Map $map */
        $map = $this->getHelperMock('ebayenterprise_catalog/map', ['foo'], false, [[
            'core_helper' => $coreHelperMock,
        ]]);
        $this->assertSame(
            true,
            $map->extractBoolValue(
                $xpath->query('Item/BaseAttributes/IsDropShipped', $this->doc->documentElement),
                $this->product
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
        $this->doc->loadXML(
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
        $xpath = $this->coreHelper->getNewDomXPath($this->doc);
        $this->assertSame(
            999,
            $this->mapHelper->extractIntValue(
                $xpath->query('Item/ExtendedAttributes/Buyer/BuyerId', $this->doc->documentElement),
                $this->product
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
        $this->doc->loadXML(
            '<ItemMaster>
                <Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
                    <ExtendedAttributes>
                        <Price>3.98</Price>
                    </ExtendedAttributes>
                </Item>
            </ItemMaster>'
        );
        $xpath = $this->coreHelper->getNewDomXPath($this->doc);
        $this->assertSame(
            3.98,
            $this->mapHelper->extractFloatValue(
                $xpath->query('Item/ExtendedAttributes/Price', $this->doc->documentElement),
                $this->product
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
            $this->mapHelper->passThrough(
                $x,
                $this->product
            )
        );
    }
    /**
     * test summing the float values of a list of nodes
     */
    public function testExtractFloatSum()
    {
        $doc = $this->coreHelper->getNewDomDocument();
        $doc->loadXML('<_><a>1.1</a><b>5.5</b><c>3.0</c></_>');
        $this->assertSame(
            9.6,
            $this->mapHelper->extractFloatSum($doc->documentElement->childNodes)
        );
    }
    /**
     * Test getting a negative sum of amounts for use as a discount amount.
     */
    public function testDiscountSum()
    {
        $doc = $this->coreHelper->getNewDomDocument();
        $doc->loadXML('<_><a>1.1</a><b>5.5</b><c>3.0</c></_>');
        $this->assertSame(
            -9.6,
            $this->mapHelper->extractDiscountSum($doc->documentElement->childNodes)
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
        $doc = $this->coreHelper->getNewDomDocument();
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
            $this->mapHelper->extractStatusValue(
                $xpath->query('Item/BaseAttributes/ItemStatus', $doc->documentElement),
                $this->product
            )
        );
    }
    /**
     * @see testExtractStatusValueWhenActive test, now testing when the extract string is not equal to 'active'
     */
    public function testExtractStatusValueWhenNotActive()
    {
        $doc = $this->coreHelper->getNewDomDocument();
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
            $this->mapHelper->extractStatusValue(
                $xpath->query('Item/BaseAttributes/ItemStatus', $doc->documentElement),
                $this->product
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
        $helper = $this->getHelperMock('eb2ccore/data', ['extractNodeVal']);
        $helper->expects($this->once())
            ->method('extractNodeVal')
            ->with($this->identicalTo($nodes))
            ->will($this->returnValue($nodeValue));

        /** @var EbayEnterprise_Catalog_Helper_Map $map */
        $map = $this->getHelperMock('ebayenterprise_catalog/map', ['foo'], false, [[
            'core_helper' => $helper,
        ]]);

        $this->assertSame($visibility, $map->extractVisibilityValue($nodes, $product));
    }
    /**
     * Test extracting product links. Should return a serialized array of
     * product links in the feed.
     */
    public function testExtractProductLinks()
    {
        $catalogId = 45;
        $helperMock = $this->getHelperMock('eb2ccore/data', ['getConfigModel']);
        $helperMock->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry([
                'catalogId' => $catalogId
            ])));
        $this->replaceByMock('helper', 'eb2ccore', $helperMock);

        $doc = $this->coreHelper->getNewDomDocument();
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

        $links = [
            ['link_type' => 'related', 'operation_type' => 'Add', 'link_to_unique_id' => '45-12345'],
            ['link_type' => 'crosssell', 'operation_type' => 'Delete', 'link_to_unique_id' => '45-23456'],
        ];

        $map = $this->getHelperMock('ebayenterprise_catalog/map', ['_convertToMagentoLinkType']);
        $map->expects($this->exactly(2))
            ->method('_convertToMagentoLinkType')
            ->will($this->returnValueMap([
                ['ES_Accessory', 'related'],
                ['ES_CrossSelling', 'crosssell']
            ]));

        $this->assertSame(
            serialize($links),
            $map->extractProductLinks(
                $nodes,
                $this->product
            )
        );
    }
    /**
     */
    public function testExtractProductLinksUnknownLink()
    {
        $doc = $this->coreHelper->getNewDomDocument();
        $doc->loadXML('<root><ProductLink link_type="NO_CLUE_WHAT_THIS_IS" operation_type="Add"><LinkToUniqueId>45-23456</LinkToUniqueId></ProductLink></root>');
        $nodes = $doc->getElementsByTagName('ProductLink');

        $links = [];

        $map = $this->getHelperMock('ebayenterprise_catalog/map', ['_convertToMagentoLinkType']);
        $map->expects($this->once())
            ->method('_convertToMagentoLinkType')
            ->with($this->identicalTo('NO_CLUE_WHAT_THIS_IS'))
            ->will($this->throwException(new Mage_Core_Exception()));

        $this->assertSame(
            serialize($links),
            $map->extractProductLinks(
                $nodes,
                $this->product
            )
        );
    }
    /**
     * Test mapping related product link types through the config registry.
     */
    public function testConvertToMagentoLinkType()
    {
        $linkTypes = ['ES_Accessory' => 'related', 'ES_CrossSelling' => 'crosssell', 'ES_UpSelling' => 'upsell'];

        $configRegistry = $this->getModelMockBuilder('eb2ccore/config_registry')
            ->disableOriginalConstructor()
            ->setMethods(['getConfig'])
            ->getMock();
        $configRegistry->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValueMap([
                ['link_types_es_accessory', null, 'related'],
                ['link_types_es_crossselling', null, 'crosssell'],
                ['link_types_es_upselling', null, 'upsell'],
            ]));

        $prodHelper = $this->getHelperMock('ebayenterprise_catalog/data', ['getConfigModel']);
        $prodHelper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($configRegistry));
        $this->replaceByMock('helper', 'ebayenterprise_catalog', $prodHelper);

        $helper = $this->mapHelper;
        foreach ($linkTypes as $ebcLink => $magentoLink) {
            $this->assertSame(
                $magentoLink,
                EcomDev_Utils_Reflection::invokeRestrictedMethod($helper, '_convertToMagentoLinkType', [$ebcLink])
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
            ->setMethods(['extractNodeVal', 'getConfigModel'])
            ->getMock();
        $coreHelperMock->expects($this->once())
            ->method('extractNodeVal')
            ->with($this->identicalTo($nodes))
            ->will($this->returnValue($sku));
        $coreHelperMock->expects($this->once())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry([
                'catalogId' => $catalogId
            ])));

        /** @var EbayEnterprise_Catalog_Helper_Map $map */
        $map = $this->getHelperMock('ebayenterprise_catalog/map', ['foo'], false, [[
            'core_helper' => $coreHelperMock,
        ]]);

        $this->assertSame($result, $map->extractSkuValue($nodes));
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
            ->setMethods(['extractNodeVal'])
            ->getMock();
        $coreHelperMock->expects($this->once())
            ->method('extractNodeVal')
            ->with($this->identicalTo($nodes))
            ->will($this->returnValue($value));
        $this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

        $product = $this->getModelMockBuilder('catalog/product')
            ->disableOriginalConstructor()
            ->setMethods(['setTypeId', 'setTypeInstance'])
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
            ->setMethods(['_isValidProductType'])
            ->setConstructorArgs([['core_helper' => $coreHelperMock]])
            ->getMock();
        $mapMock->expects($this->once())
            ->method('_isValidProductType')
            ->with($this->identicalTo($value))
            ->will($this->returnValue(true));

        $this->assertSame($value, $mapMock->extractProductTypeValue($nodes, $product));
    }

    /**
     * @return array
     */
    public function providerIsValidProductType()
    {
        return [
            [true, 'simple'],
            [false, 'wrong'],
        ];
    }
    /**
     * Test _isValidProductType method for the following expectations
     * Expectation 1: when this test invoked the method EbayEnterprise_Catalog_Helper_Map::_isValidProductType
     *                with a given value it will check if the value in the six possible Magento product type
     *                it will return true if value match otherwise false
     * @param bool
     * @param string
     * @dataProvider providerIsValidProductType
     */
    public function testIsValidProductType($expect, $value)
    {
        $this->assertSame($expect, EcomDev_Utils_Reflection::invokeRestrictedMethod($this->mapHelper, '_isValidProductType', [$value]));
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
        $data = serialize([
            ['mfn_duty_rate' => '10', 'destination_country' => 'AU', 'restricted' => 'N', 'hts_code' => '6114.2'],
            ['mfn_duty_rate' => '12', 'destination_country' => 'AT', 'restricted' => 'N', 'hts_code' => '6114.20']
        ]);

        $doc = $this->coreHelper->getNewDomDocument();

        $doc->loadXML(
            '<root>
                <htscodes>
                    <HTSCode mfn_duty_rate="10" destination_country="AU" restricted="N">6114.2</HTSCode>
                    <HTSCode mfn_duty_rate="12" destination_country="AT" restricted="N">6114.20</HTSCode>
                </htscodes>
            </root>'
        );

        $xpath = new DOMXPath($doc);

        $this->assertSame($data, $this->mapHelper->extractHtsCodesValue($xpath->query(
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

        $helper = $this->getHelperMock('ebayenterprise_catalog/data', ['getAttributeSetIdByName']);
        $helper->expects($this->once())
            ->method('getAttributeSetIdByName')
            ->with($this->identicalTo($attributeSetName))
            ->will($this->returnValue($nonExistsAttribute));
        $this->replaceByMock('helper', 'ebayenterprise_catalog', $helper);

        $product = Mage::getModel('catalog/product', ['attribute_set_id' => $attributeSetId]);

        $doc = $this->coreHelper->getNewDomDocument();
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

        $xpath = $this->coreHelper->getNewDomXPath($doc);
        $nodes = $xpath->query(
            'Item/CustomAttributes/Attribute[@name="AttributeSet"]/Value',
            $doc->documentElement
        );
        $this->assertSame(
            $attributeSetId,
            $this->mapHelper->extractAttributeSetValue($nodes, $product)
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

        $helper = $this->getHelperMock('ebayenterprise_catalog/data', ['getAttributeSetIdByName']);
        $helper->expects($this->once())
            ->method('getAttributeSetIdByName')
            ->with($this->identicalTo($attributeSetName))
            ->will($this->returnValue($knownAttributeSetId));
        $this->replaceByMock('helper', 'ebayenterprise_catalog', $helper);

        $product = Mage::getModel('catalog/product', ['attribute_set_id' => $attributeSetId]);

        $doc = $this->coreHelper->getNewDomDocument();
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

        $xpath = $this->coreHelper->getNewDomXPath($doc);
        $nodes = $xpath->query(
            'Item/CustomAttributes/Attribute[@name="AttributeSet"]/Value',
            $doc->documentElement
        );
        $this->assertSame(
            $knownAttributeSetId,
            $this->mapHelper->extractAttributeSetValue($nodes, $product)
        );
    }

    /**
     * Prepare a DOMNodeList object using the XML fixture file, and XPath querying
     * all custom attributes. Finally, return a nested array with element 1 as the DOMNodelist
     * and element 2 as a new empty catalog/product instance.
     *
     * @return array
     */
    public function providerExtractCustomAttributes()
    {
        return [
            [
                __DIR__ . DS . 'MapTest' . DS . 'fixtures' . DS . 'CustomAttributes-ItemMaster.xml',
                Mage::getModel('catalog/product'),
            ],
        ];
    }

    /**
     * Test that the helper callback method ebayenterprise_catalog/map::extractCustomAttributes()
     * when invoked will be given as its first parameter an object of type DOMNodeList, and as its second parameter
     * an object of type catalog/product. Then, we expect the proper custom attribute to be extracted
     * and added to the passed-in product object.
     *
     * @param string
     * @param Mage_Catalog_Model_Product
     * @dataProvider providerExtractCustomAttributes
     */
    public function testExtractCustomAttributes($feedFile, Mage_Catalog_Model_Product $product)
    {
        $this->doc->loadXML(file_get_contents($feedFile));
        /** @var DOMXPath $xpath */
        $xpath = $this->coreHelper->getNewDomXPath($this->doc);
        /** @var DOMNodeList $nodes */
        $nodes = $xpath->query('//CustomAttributes/Attribute');
        /** @var EbayEnterprise_Catalog_Helper_Map $map */
        $map = $this->mapHelper;
        // Proving that product object has no value
        $this->assertEmpty($product->getData());
        // Proving that when the method ebayenterprise_catalog/map::extractCustomAttributes()
        // invoked it will always return null
        $this->assertNull($map->extractCustomAttributes($nodes, $product));
        // Proving that after calling the method ebayenterprise_catalog/map::extractCustomAttributes()
        // the empty passed in product object will now contains data
        $this->assertNotEmpty($product->getData());
    }

    /**
     * @return array
     */
    public function providerExtractAllowGiftMessage()
    {
        /** @var EbayEnterprise_Eb2cCore_Helper_Data */
        $coreHelper = Mage::helper('eb2ccore');
        /** @var DOMDocument */
        $doc = $coreHelper->getNewDomDocument();
        $doc->loadXML(
            '<root>
                <ExtendedAttributes>
                    <AllowGiftMessage>true</AllowGiftMessage>
                </ExtendedAttributes>
            </root>'
        );
        /** @var DOMXPath */
        $xpath = $coreHelper->getNewDomXPath($doc);
        /** @var DOMNodeList */
        $nodeListA = $xpath->query('ExtendedAttributes/AllowGiftMessage', $doc->documentElement);
        /** @var DOMNodeList */
        $nodeListB = new DOMNodeList();
        return [
            [$nodeListA, 1],
            [$nodeListB, null],
        ];
    }

    /**
     * Scenario: Extract Allow Gift Message
     * Given an XML NodeList object containing Allow Gift Message data.
     * When the callback extracts the allow gift message data.
     * Then the catalog/product setter method 'setUseConfigGiftMessageAvailable' will be set to zero.
     * And the boolean value cast to an integer value will be returned.
     *
     * Given an XML NodeList object that doesn't contain any Allow Gift Message data.
     * When the callback extracts the allow gift message data.
     * Then the catalog/product setter method 'setUseConfigGiftMessageAvailable' never be invoked.
     * And null value will be returned.
     *
     * @param DOMNodeList
     * @param int | null
     * @dataProvider providerExtractAllowGiftMessage
     */
    public function testExtractAllowGiftMessage(DOMNodeList $nodeList, $result)
    {
        /** @var Mock_Mage_Catalog_Model_Product */
        $product = $this->getModelMock('catalog/product', ['setUseConfigGiftMessageAvailable']);
        $product->expects($nodeList->length ? $this->once() : $this->never())
            ->method('setUseConfigGiftMessageAvailable')
            ->with($this->identicalTo(0))
            ->will($this->returnSelf());

        $this->assertSame($result, $this->mapHelper->extractAllowGiftMessage($nodeList, $product));
    }
}
