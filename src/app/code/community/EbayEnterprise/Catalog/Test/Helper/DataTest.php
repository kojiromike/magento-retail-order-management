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

class EbayEnterprise_Catalog_Test_Helper_DataTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    public function setUp()
    {
        parent::setUp();

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
    }
    /**
     * testing getConfigModel method
     */
    public function testGetConfigModel()
    {
        $configRegistryModelMock = $this->getModelMockBuilder('eb2ccore/config_registry')
            ->disableOriginalConstructor()
            ->setMethods(array(
                '__get',
                '__set',
                '_getStoreConfigValue',
                '_magicNameToConfigKey',
                'addConfigModel',
                'getConfig',
                'getConfigFlag',
                'getStore',
                'setStore',
            ))
            ->getMock();
        $configRegistryModelMock->expects($this->any())
            ->method('setStore')
            ->will($this->returnSelf());
        $configRegistryModelMock->expects($this->any())
            ->method('addConfigModel')
            ->will($this->returnSelf());
        $configRegistryModelMock->expects($this->any())
            ->method('getStore')
            ->will($this->returnValue(1));
        $configRegistryModelMock->expects($this->any())
            ->method('_getStoreConfigValue')
            ->will($this->returnValue(null));
        $configRegistryModelMock->expects($this->any())
            ->method('getConfigFlag')
            ->will($this->returnValue(1));
        $configRegistryModelMock->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue(null));
        $configRegistryModelMock->expects($this->any())
            ->method('_magicNameToConfigKey')
            ->will($this->returnValue(null));
        $configRegistryModelMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValue(null));
        $configRegistryModelMock->expects($this->any())
            ->method('__set')
            ->will($this->returnValue(null));
        $this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistryModelMock);
        $productConfigModelMock = $this->getModelMockBuilder('ebayenterprise_catalog/config')
            ->disableOriginalConstructor()
            ->setMethods(array('hasKey', 'getPathForKey'))
            ->getMock();
        $productConfigModelMock->expects($this->any())
            ->method('hasKey')
            ->will($this->returnValue(null));
        $productConfigModelMock->expects($this->any())
            ->method('getPathForKey')
            ->will($this->returnValue(null));
        $this->replaceByMock('model', 'ebayenterprise_catalog/config', $productConfigModelMock);
        $coreConfigModelMock = $this->getModelMockBuilder('eb2ccore/config')
            ->disableOriginalConstructor()
            ->setMethods(array('hasKey', 'getPathForKey'))
            ->getMock();
        $coreConfigModelMock->expects($this->any())
            ->method('hasKey')
            ->will($this->returnValue(null));
        $coreConfigModelMock->expects($this->any())
            ->method('getPathForKey')
            ->will($this->returnValue(null));
        $this->replaceByMock('model', 'eb2ccore/config', $coreConfigModelMock);
        $productHelper = Mage::helper('ebayenterprise_catalog');
        $this->assertInstanceOf('EbayEnterprise_Eb2cCore_Model_Config_Registry', $productHelper->getConfigModel());
    }
    public function providerHasEavAttr()
    {
        return array(
            array('known-attr'),
            array('alien-attr'),
        );
    }
    /**
     * Test that a product attribute is known if it has an id > 0.
     * @param string $name The attribute name
     * @dataProvider providerHasEavAttr
     */
    public function testHasEavAttr($name)
    {
        $atId = $this->expected($name)->getId();
        $att = $this->getModelMock('eav/attribute', array('getId'));
        $att->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($atId));
        $this->replaceByMock('model', 'eav/attribute', $att);
        $eav = $this->getModelMock('eav/config', array('getAttribute'));
        $eav->expects($this->once())
            ->method('getAttribute')
            ->with($this->equalTo(Mage_Catalog_Model_Product::ENTITY), $this->equalTo($name))
            ->will($this->returnValue($att));
        $this->replaceByMock('model', 'eav/config', $eav);
        // If $atId > 0, the result should be true
        $this->assertSame($atId > 0, Mage::helper('ebayenterprise_catalog')->hasEavAttr($name));
    }
    /**
     * Test that a known product type is validated and an unknown is rejected.
     */
    public function testHasProdType()
    {
        $this->assertSame(false, Mage::helper('ebayenterprise_catalog')->hasProdType('alien'));
        // Normally I would inject a known value into Mage_Catalog_Model_Product_Type::getTypes()
        // so that this test is a true "unit" test and doesn't depend on the environment
        // at all, but getTypes is static, and you can bet there's gonna be a "simple"
        // type in every environment.
        $this->assertSame(true, Mage::helper('ebayenterprise_catalog')->hasProdType('simple'));
    }
    /**
     * Should throw an exception when creating a dummy product template
     * if the configuration specifies an invalid Magento product type
     * This test will use hasProdType() so has a real, if marginal, environmental
     * dependence.
     * @expectedException EbayEnterprise_Catalog_Model_Config_Exception
     */
    public function testInvalidDummyTypeFails()
    {
        $fakeCfg = new StdClass();
        $fakeCfg->dummyTypeId = 'someWackyTypeThatWeHopeDoesntExist';
        $hlpr = $this->getHelperMock('ebayenterprise_catalog/data', array(
            'getConfigModel',
        ));
        $hlpr->expects($this->once())
            ->method('getConfigModel')
            ->will($this->returnValue($fakeCfg));
        EcomDev_Utils_Reflection::invokeRestrictedMethod($hlpr, '_getProdTplt');
    }
    /**
     * Test the various dummy defaults.
     */
    public function testGetDefaults()
    {
        $hlpr = Mage::helper('ebayenterprise_catalog');
        $this->assertInternalType('array', EcomDev_Utils_Reflection::invokeRestrictedMethod($hlpr, '_getAllWebsiteIds'));
        $this->assertInternalType('integer', EcomDev_Utils_Reflection::invokeRestrictedMethod($hlpr, '_getDefProdAttSetId'));
        $this->assertInternalType('integer', EcomDev_Utils_Reflection::invokeRestrictedMethod($hlpr, '_getDefStoreId'));
        $this->assertInternalType('integer', EcomDev_Utils_Reflection::invokeRestrictedMethod($hlpr, '_getDefStoreRootCatId'));
    }
    public function testBuildDummyBoilerplate()
    {
        $fakeCfg = new StdClass();
        $fakeCfg->dummyInStockFlag = true;
        $fakeCfg->dummyManageStockFlag = true;
        $fakeCfg->dummyStockQuantity = 123;
        $fakeCfg->dummyDescription = 'hello world';
        $fakeCfg->dummyPrice = 45.67;
        $fakeCfg->dummyShortDescription = 'hello';
        $fakeCfg->dummyTypeId = 'simple';
        $fakeCfg->dummyWeight = 79;
        $hlpr = $this->getHelperMock('ebayenterprise_catalog/data', array(
            '_getAllWebsiteIds',
            '_getDefProdAttSetId',
            '_getDefStoreId',
            '_getDefStoreRootCatId',
            'getConfigModel',
        ));
        $hlpr->expects($this->once())
            ->method('_getAllWebsiteIds')
            ->will($this->returnValue(array(980)));
        $hlpr->expects($this->once())
            ->method('_getDefProdAttSetId')
            ->will($this->returnValue(132));
        $hlpr->expects($this->once())
            ->method('_getDefStoreId')
            ->will($this->returnValue(531));
        $hlpr->expects($this->once())
            ->method('_getDefStoreRootCatId')
            ->will($this->returnValue(771));
        $hlpr->expects($this->once())
            ->method('getConfigModel')
            ->will($this->returnValue($fakeCfg));
        $expected = array(
            'attribute_set_id' => 132,
            'category_ids' => array(771),
            'description' => 'hello world',
            'price' => 45.67,
            'short_description' => 'hello',
            'status' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED,
            'stock_data' => array(
                'is_in_stock' => true,
                'manage_stock' => true,
                'qty' => 123,
            ),
            'store_ids' => array(531),
            'type_id' => 'simple',
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
            'website_ids' => array(980),
            'weight' => 79,
        );
        $this->assertSame($expected, EcomDev_Utils_Reflection::invokeRestrictedMethod($hlpr, '_getProdTplt'));
    }
    /**
     * @dataProvider dataProvider
     */
    public function testApplyDummyData($sku, $additionalData = array())
    {
        $name = isset($additionalData['name']) ? $additionalData['name'] : null;
        $hlpr = $this->getHelperMock('ebayenterprise_catalog/data', array('_getProdTplt'));
        $hlpr->expects($this->once())
            ->method('_getProdTplt')
            ->will($this->returnValue(array()));
        $prod = EcomDev_Utils_Reflection::invokeRestrictedMethod($hlpr, '_applyDummyData', array(Mage::getModel('catalog/product'), $sku, $additionalData));
        $this->assertSame($sku, $prod->getSku());
        $this->assertSame($name ?: "Incomplete Product: $sku", $prod->getName());
        $this->assertSame($sku, $prod->getUrlKey());
    }
    /**
     * Given a mapped array containing language, parse should return
     * a flattened array, keyed by language
     */
    public function testParseTranslations()
    {
        $sampleInput = array (
            array (
                'lang' => 'en-US',
                'description' => 'An en-US translation',
            ),
            array (
                'lang' => 'ja-JP',
                'description' => 'ja-JP に変換',
            ),
        );
        $expectedOutput = array (
            'en-US' => 'An en-US translation',
            'ja-JP' => 'ja-JP に変換',
        );
        $this->assertSame($expectedOutput, Mage::helper('ebayenterprise_catalog')->parseTranslations($sampleInput));
    }
    /**
     * Given a null an empty array is returned.
     */
    public function testParseTranslationsWithNull()
    {
        $sampleInput = null;
        $expectedOutput = array();
        $this->assertSame($expectedOutput, Mage::helper('ebayenterprise_catalog')->parseTranslations($sampleInput));
    }
    /**
     * Test getDefaultLanguageCode the feed
     */
    public function testGetDefaultLanguageCode()
    {
        $productHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
            ->disableOriginalConstructor()
            ->setMethods(array('_getLocaleCode'))
            ->getMock();
        $productHelperMock->expects($this->once())
            ->method('_getLocaleCode')
            ->will($this->returnValue('en_US'));
        $this->replaceByMock('helper', 'ebayenterprise_catalog', $productHelperMock);
        $this->assertSame('en-US', Mage::helper('ebayenterprise_catalog')->getDefaultLanguageCode());
    }
    /**
     * Test getProductAttributeId the feed
     */
    public function testGetProductAttributeId()
    {
        $entityAttributeModelMock = $this->getModelMockBuilder('eav/entity_attribute')
            ->disableOriginalConstructor()
            ->setMethods(array('loadByCode', 'getId'))
            ->getMock();
        $entityAttributeModelMock->expects($this->once())
            ->method('loadByCode')
            ->with($this->equalTo('catalog_product'), $this->equalTo('color'))
            ->will($this->returnSelf());
        $entityAttributeModelMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(92));
        $this->replaceByMock('model', 'eav/entity_attribute', $entityAttributeModelMock);
        $this->assertSame(92, Mage::helper('ebayenterprise_catalog')->getProductAttributeId('color'));
    }
    /**
     * Test extractNodeVal method
     */
    public function testExtractNodeVal()
    {
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML('<root><Description xml:lang="en_US">desc1</Description></root>');
        $xpath = new DOMXPath($doc);
        $this->assertSame('desc1', Mage::helper('ebayenterprise_catalog')->extractNodeVal($xpath->query('Description', $doc->documentElement)));
    }
    /**
     * Test extractNodeAttributeVal method
     */
    public function testExtractNodeAttributeVal()
    {
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML('<root><Description xml:lang="en_US">desc1</Description></root>');
        $xpath = new DOMXPath($doc);
        $this->assertSame('en_US', Mage::helper('ebayenterprise_catalog')->extractNodeAttributeVal($xpath->query('Description', $doc->documentElement), 'xml:lang'));
    }
    /**
     * Test prepareProductModel the feed
     */
    public function testPrepareProductModel()
    {
        $additionalData = array('name' => 'Test Product Title');
        $productModelMock = $this->getModelMockBuilder('catalog/product')
            ->disableOriginalConstructor()
            ->setMethods(array('getId'))
            ->getMock();
        $productModelMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(0));
        $productHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
            ->disableOriginalConstructor()
            ->setMethods(array('loadProductBySku', '_applyDummyData'))
            ->getMock();
        $productHelperMock->expects($this->once())
            ->method('loadProductBySku')
            ->with($this->equalTo('TST-1234'))
            ->will($this->returnValue($productModelMock));
        $productHelperMock->expects($this->once())
            ->method('_applyDummyData')
            ->with(
                $this->isInstanceOf('Mage_Catalog_Model_Product'),
                $this->equalTo('TST-1234'),
                $this->identicalTo($additionalData)
            )
            ->will($this->returnValue($productModelMock));
        $this->replaceByMock('helper', 'ebayenterprise_catalog', $productHelperMock);
        $this->assertInstanceOf(
            'Mage_Catalog_Model_Product',
            Mage::helper('ebayenterprise_catalog')->prepareProductModel('TST-1234', $additionalData)
        );
    }
    /**
     * Test getCustomAttributeCodeSet the feed
     */
    public function testGetCustomAttributeCodeSet()
    {
        $apiModelMock = $this->getModelMockBuilder('catalog/product_attribute_api')
            ->disableOriginalConstructor()
            ->setMethods(array('items'))
            ->getMock();
        $apiModelMock->expects($this->once())
            ->method('items')
            ->with($this->equalTo(172))
            ->will($this->returnValue(array(
                array('code' => 'brand_name'),
                array('code' => 'brand_description'),
                array('code' => 'is_drop_shipped'),
                array('code' => 'drop_ship_supplier_name')
            )));
        $this->replaceByMock('model', 'catalog/product_attribute_api', $apiModelMock);
        $helper = Mage::helper('ebayenterprise_catalog');
        // setting _customAttributeCodeSets property back to an empty array
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($helper, '_customAttributeCodeSets', array());
        $this->assertSame(
            array('brand_name', 'brand_description', 'is_drop_shipped', 'drop_ship_supplier_name'),
            Mage::helper('ebayenterprise_catalog')->getCustomAttributeCodeSet(172)
        );
        $this->assertSame(
            array(172 => array('brand_name', 'brand_description', 'is_drop_shipped', 'drop_ship_supplier_name')),
            EcomDev_Utils_Reflection::getRestrictedPropertyValue($helper, '_customAttributeCodeSets')
        );
        // resetting _customAttributeCodeSets property back to an empty array
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($helper, '_customAttributeCodeSets', array());
    }
    /**
     * Data provider for testGetConfigAttributesData
     * @return array Arrays for use as args to the testGetConfigAttributesData test
     */
    public function providerTestGetConfigAttributesData()
    {
        return array(
            // should return source data as this would be a brand new product - no id
            array(null, null, 'do-not-care', array(), array('source_data'), array('source_data')),
            // should return source data as this is a simple product and could not have existing config attr data
            array(42, Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, 'do-not-care', array(), array('source_data'), array('source_data')),
            // should retrn source data as this won't end up being a config product...seems a bit odd but I guess it just means any existing data doesn't matter
            array(42, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, Mage_Catalog_Model_Product_Type::TYPE_SIMPLE, array(), array('source_data'), array('source_data')),
            // should return source data as config product doesn't alreay have config attr data set
            array(42, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, array(), array('source_data'), array('source_data')),
            // should return null as existing config product has already had config attr data set and it cannot/shoudl not be overridden once set
            array(42, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, array('existing_data'), array('source_data'), null),
        );
    }
    /**
     * Test getting the configurable attributes to add to a product. When the product is an existing product
     * (has an id, type id), is a configurable or is supposed to be a configurable and already has
     * configurable attributes data, it should return null. Otherwise, it should return
     * whatever configurable attributes data is contained in the source data.
     * @param int $existingId Id of existing product, null if expected to be a new product
     * @param string $existingType Product type of the existing product
     * @param string $newType Product type the product is expected to be post import
     * @param array $existingAttributes Array of configurable attribute data the product already has had applied
     * @param array $sourceAttributes Data from the feed that should be applied to the product, may contain configurable_attributes_data to add
     * @param array $expectedAttributes Attributes that are expected to be returned from the method
     * @mock Mage_Catalog_Model_Product::getId return expected product id
     * @mock Mage_Catalog_Model_Product::getTypeId return expected product type id
     * @mock Mage_Catalog_Model_Product::getTypeInstance return the stub product type model
     * @mock Mage_Catalog_Model_Product_Type_Abstract::getConfigurableAttributesAsArray return expected existing attributes
     * @dataProvider providerTestGetConfigAttributesData
     */
    public function testGetConfigAttributesData($existingId, $existingType, $newType, $existingAttributes, $sourceAttributes, $expectedAttributes)
    {
        $prod = $this->getModelMock('catalog/product', array('getId', 'getTypeId', 'getTypeInstance'));
        $prodType = $this->getModelMock('catalog/product/type/abstract', array('getConfigurableAttributesAsArray'));
        $source = new Varien_Object(array('configurable_attributes_data' => $sourceAttributes));
        $prod
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($existingId));
        $prod
            ->expects($this->any())
            ->method('getTypeId')
            ->will($this->returnValue($existingType));
        $prod
            ->expects($this->any())
            ->method('getTypeInstance')
            ->with($this->isTrue())
            ->will($this->returnValue($prodType));
        $prodType
            ->expects($this->any())
            ->method('getConfigurableAttributesAsArray')
            ->with($this->identicalTo($prod))
            ->will($this->returnValue($existingAttributes));
        $this->assertSame(
            $expectedAttributes,
            Mage::helper('ebayenterprise_catalog')->getConfigurableAttributesData($newType, $source, $prod)
        );
    }
    /**
     * verify the helper is used correctly
     */
    public function testLoadProductBySku()
    {
        $sku = 'thesku';
        $product = new Varien_Object();
        $store = $this->getModelMockBuilder('core/store')
            ->disableOriginalConstructor()
            ->getMock();
        $helper = $this->getHelperMockBuilder('catalog/product')
            ->disableOriginalConstructor()
            ->setMethods(array('getProduct'))
            ->getMock();
        $helper->expects($this->once())
            ->method('getProduct')
            ->with(
                $this->identicalTo($sku),
                $this->identicalTo($store),
                $this->identicalTo('sku')
            )
            ->will($this->returnValue($product));
        $this->replaceByMock('helper', 'catalog/product', $helper);
        $testModel = Mage::helper('ebayenterprise_catalog');
        $this->assertSame(
            $product,
            $testModel->loadProductBySku($sku, $store)
        );
    }

    /**
     * Test mapPattern method
     */
    public function testMapPattern()
    {
        $dataHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
            ->disableOriginalConstructor()
            ->setMethods(array('__construct'))
            ->getMock();
        $dataHelperMock->expects($this->any())
            ->method('__construct')
            ->will($this->returnValue(null));

        $testData = array(
            array(
                'expect' => 'ItemMaster_20140107224605_12345_ABCD.xml',
                'keyMap' => array('feed_type' => 'ItemMaster', 'time_stamp' => '20140107224605', 'channel' => '12345', 'store_id' => 'ABCD'),
                'pattern' => '{feed_type}_{time_stamp}_{channel}_{store_id}.xml'
            )
        );

        foreach ($testData as $data) {
            $this->assertSame($data['expect'], $dataHelperMock->mapPattern($data['keyMap'], $data['pattern']));
        }
    }

    /**
     * Test generateFileName method
     */
    public function testGenerateFileName()
    {
        $filenameFormat = '{feed_type}_{time_stamp}_{channel}_{store_id}.xml';
        $filenameConfig = array(
            'feed_type' => 'ItemMaster',
            'time_stamp' => '2014-01-09T13:47:32-00:00',
            'channel' => '1234',
            'store_id' => 'ABCD'
        );
        $filename = 'SomeFile_Name.xml';

        $feedHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/feed')
            ->disableOriginalConstructor()
            ->setMethods(array('getFileNameConfig'))
            ->getMock();
        $feedHelperMock->expects($this->once())
            ->method('getFileNameConfig')
            ->with($this->equalTo('ItemMaster'))
            ->will($this->returnValue($filenameConfig));
        $this->replaceByMock('helper', 'ebayenterprise_catalog/feed', $feedHelperMock);

        $dataHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
            ->disableOriginalConstructor()
            ->setMethods(array('mapPattern', 'getConfigModel'))
            ->getMock();
        $dataHelperMock->expects($this->once())
            ->method('mapPattern')
            ->with($this->equalTo($filenameConfig), $this->equalTo($filenameFormat))
            ->will($this->returnValue($filename));
        $this->assertSame(
            $filename,
            $dataHelperMock->generateFileName('ItemMaster', $filenameFormat)
        );
    }

    /**
     * Test getting the processing directory set in the configuration, creating
     * the directory if it doesn't exist already.
     */
    public function testGetProcessingDirectory()
    {
        $base = Mage::getBaseDir('var');
        $processingDir = 'processing';

        $coreHelperMock = $this->getHelperMock(
            'eb2ccore/data',
            array('isDir', 'createDir', 'getConfigModel')
        );
        $cfg = $this->buildCoreConfigRegistry(array(
            'feedProcessingDirectory' => $processingDir,
        ));

        $this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

        $coreHelperMock->expects($this->once())
            ->method('isDir')
            ->will($this->returnValue(true));
        $coreHelperMock->expects($this->once())
            ->method('createDir')
            ->will($this->returnValue(null));
        $coreHelperMock->expects($this->once())
            ->method('getConfigModel')
            ->will($this->returnValue($cfg));

        $this->assertSame(
            $base . DS . $processingDir,
            Mage::helper('ebayenterprise_catalog')->getProcessingDirectory()
        );
    }

    /**
     * Test generateFilePath method, throw exception if the directory did not
     * and exists and creating it fail for any reason.
     * @expectedException EbayEnterprise_Catalog_Exception_Feed_File
     */
    public function testGenerateFilePathWithException()
    {
        $base = Mage::getBaseDir('var');
        $processingDir = 'processing';

        $coreHelperMock = $this->getHelperMock(
            'eb2ccore/data',
            array('isDir', 'createDir', 'getConfigModel')
        );
        $cfg = $this->buildCoreConfigRegistry(array(
            'feedProcessingDirectory' => $processingDir,
        ));

        $this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

        $coreHelperMock->expects($this->once())
            ->method('isDir')
            ->will($this->returnValue(false));
        $coreHelperMock->expects($this->once())
            ->method('createDir')
            ->will($this->returnValue(null));
        $coreHelperMock->expects($this->once())
            ->method('getConfigModel')
            ->will($this->returnValue($cfg));

        $this->setExpectedException(
            'EbayEnterprise_Catalog_Exception_Feed_File',
            "Can not create the following directory ({$base}/{$processingDir})"
        );
        Mage::helper('ebayenterprise_catalog')->getProcessingDirectory();
    }

    /**
     * Test buildErrorFeedFilename method
     */
    public function testBuildErrorFeedFilename()
    {
        $feedType = 'SomeEvent';
        $filenameFormat = 'file_{name}_format.xml';

        $coreHelper = $this->getHelperMock('eb2ccore/data', array('getConfigModel'));
        $coreHelper->expects($this->once())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry(array(
                'errorFeedFilenameFormat' => $filenameFormat
            ))));
        $this->replaceByMock('helper', 'eb2ccore', $coreHelper);

        $helper = $this->getHelperMock(
            'ebayenterprise_catalog/data',
            array('getProcessingDirectory', 'generateFileName')
        );
        $helper->expects($this->once())
            ->method('getProcessingDirectory')
            ->will($this->returnValue('/Mage/var/processing'));
        $helper->expects($this->once())
            ->method('generateFileName')
            ->with($this->identicalTo($feedType), $this->identicalTo($filenameFormat))
            ->will($this->returnValue('error_file.xml'));

        $this->assertSame(
            '/Mage/var/processing/error_file.xml',
            $helper->buildErrorFeedFilename($feedType)
        );
    }

    /**
     * Test generateMessageHeader method
     */
    public function testGenerateMessageHeader()
    {
        $feedHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/feed')
            ->disableOriginalConstructor()
            ->setMethods(array('getHeaderConfig'))
            ->getMock();
        $feedHelperMock->expects($this->once())
            ->method('getHeaderConfig')
            ->with($this->equalTo('ItemMaster'))
            ->will($this->returnValue(array(
                'standard' => 'GSI',
                'source_id' => 'ABCD',
                'source_type' => '1234',
                'message_id' => 'ABCD_1234_52ceae46381f0',
                'create_date_and_time' => '2014-01-09T13:47:32-00:00',
                'header_version' => '2.3.0',
                'version_release_number' => '2.3.0',
                'destination_id' => 'MWS',
                'destination_type' => 'WS',
                'event_type' => 'ItemMaster',
                'correlation_id' => 'WS'
            )));
        $this->replaceByMock('helper', 'ebayenterprise_catalog/feed', $feedHelperMock);

        $coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
            ->disableOriginalConstructor()
            ->setMethods(array('getConfigModel'))
            ->getMock();
        $coreHelperMock->expects($this->once())
            ->method('getConfigModel')
            ->with($this->equalTo(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID))
            ->will($this->returnValue($this->buildCoreConfigRegistry(array(
                'feedHeaderTemplate' => '<MessageHeader>
		<Standard>{standard}</Standard>
		<HeaderVersion>{header_version}</HeaderVersion>
		<VersionReleaseNumber>{version_release_number}</VersionReleaseNumber>
		<SourceData>
			<SourceId>{source_id}</SourceId>
			<SourceType>{source_type}</SourceType>
		</SourceData>
		<DestinationData>
			<DestinationId>{destination_id}</DestinationId>
			<DestinationType>{destination_type}</DestinationType>
		</DestinationData>
		<EventType>{event_type}</EventType>
		<MessageData>
			<MessageId>{message_id}</MessageId>
			<CorrelationId>{correlation_id}</CorrelationId>
		</MessageData>
		<CreateDateAndTime>{create_date_and_time}</CreateDateAndTime>
	</MessageHeader>'
            ))));
        $this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

        $dataHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
            ->disableOriginalConstructor()
            ->setMethods(array('mapPattern'))
            ->getMock();
        $dataHelperMock->expects($this->once())
            ->method('mapPattern')
            ->will($this->returnValue('<MessageHeader>
		<Standard>GSI</Standard>
		<HeaderVersion>2.3.0</HeaderVersion>
		<VersionReleaseNumber>2.3.0</VersionReleaseNumber>
		<SourceData>
			<SourceId>ABCD</SourceId>
			<SourceType>1234</SourceType>
		</SourceData>
		<DestinationData>
			<DestinationId>MWS</DestinationId>
			<DestinationType>WS/DestinationType>
		</DestinationData>
		<EventType>ItemMaster</EventType>
		<MessageData>
			<MessageId>ABCD_1234_52ceae46381f0</MessageId>
			<CorrelationId>WS</CorrelationId>
		</MessageData>
		<CreateDateAndTime>2014-01-09T13:47:32-00:00</CreateDateAndTime>
	</MessageHeader>'));

        $testData = array(
            array(
                'expect' => '<MessageHeader>
		<Standard>GSI</Standard>
		<HeaderVersion>2.3.0</HeaderVersion>
		<VersionReleaseNumber>2.3.0</VersionReleaseNumber>
		<SourceData>
			<SourceId>ABCD</SourceId>
			<SourceType>1234</SourceType>
		</SourceData>
		<DestinationData>
			<DestinationId>MWS</DestinationId>
			<DestinationType>WS/DestinationType>
		</DestinationData>
		<EventType>ItemMaster</EventType>
		<MessageData>
			<MessageId>ABCD_1234_52ceae46381f0</MessageId>
			<CorrelationId>WS</CorrelationId>
		</MessageData>
		<CreateDateAndTime>2014-01-09T13:47:32-00:00</CreateDateAndTime>
	</MessageHeader>',
                'feedType' => 'ItemMaster'
            ),
        );

        foreach ($testData as $data) {
            $this->assertSame($data['expect'], $dataHelperMock->generateMessageHeader($data['feedType']));
        }
    }

    /**
     * Test getStoreViewLanguage method
     */
    public function testGetStoreViewLanguage()
    {
        $storeModelMock = $this->getModelMockBuilder('core/store')
            ->disableOriginalConstructor()
            ->setMethods(array('getName'))
            ->getMock();
        $storeModelMock->expects($this->at(0))
            ->method('getName')
            ->will($this->returnValue('magtna_magt1_en-US'));
        $storeModelMock->expects($this->at(1))
            ->method('getName')
            ->will($this->returnValue('magtna_magt1'));
        $testData = array(
            array('expect' => 'en-us', 'store' => $storeModelMock),
            array('expect' => null, 'store' => $storeModelMock),
        );
        foreach ($testData as $data) {
            $this->assertSame($data['expect'], Mage::helper('ebayenterprise_catalog')->getStoreViewLanguage($data['store']));
        }
    }

    /**
     * Test _getDefaultParentCategoryId method
     */
    public function testGetDefaultParentCategoryId()
    {
        $catogyCollectionModelMock = $this->getResourceModelMockBuilder('catalog/category_collection')
            ->disableOriginalConstructor()
            ->setMethods(array('addAttributeToSelect', 'addAttributeToFilter', 'getFirstItem'))
            ->getMock();
        $catogyCollectionModelMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with($this->equalTo('entity_id'))
            ->will($this->returnSelf());
        $catogyCollectionModelMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->with($this->equalTo('parent_id'), $this->equalTo(array('eq' => 0)))
            ->will($this->returnSelf());
        $catogyCollectionModelMock->expects($this->once())
            ->method('getFirstItem')
            ->will($this->returnValue(Mage::getModel('catalog/category')->addData(array('entity_id' => 1))));
        $this->replaceByMock('resource_model', 'catalog/category_collection', $catogyCollectionModelMock);

        $helper = Mage::helper('ebayenterprise_catalog');
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($helper, '_defaultParentCategoryId', null);

        $this->assertSame(1, $helper->getDefaultParentCategoryId());
    }
    /**
     */
    public function testCreateNewProduct()
    {
        $additionalData = array('name' => 'Fake Product');
        $productMock = $this->getModelMockBuilder('catalog/product')
            ->disableOriginalConstructor()
            ->getMock();
        $this->replaceByMock('model', 'catalog/product', $productMock);

        $productHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
            ->disableOriginalConstructor()
            ->setMethods(array('_applyDummyData'))
            ->getMock();
        $productHelperMock->expects($this->once())
            ->method('_applyDummyData')
            ->with($this->equalTo($productMock), $this->equalTo('1234'), $this->identicalTo($additionalData))
            ->will($this->returnValue($productMock));
        $this->replaceByMock('helper', 'ebayenterprise_catalog', $productHelperMock);

        $this->assertSame($productMock, $productHelperMock->createNewProduct('1234', $additionalData));
    }
    /**
     * Test that the method EbayEnterprise_Catalog_Helper_Data::isValidIsoCountryCode
     * return true when the valid ISO Country code is passed in.
     */
    public function testIsValidIsoCountryCode()
    {
        $countryCode = 'US';

        $collection = $this->getResourceModelMock('directory/country_collection', array('addFieldToFilter', 'count'));
        $collection->expects($this->once())
            ->method('addFieldToFilter')
            ->with($this->identicalTo('iso2_code'), $this->identicalTo($countryCode))
            ->will($this->returnSelf());
        $collection->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1));
        $this->replaceByMock('resource_model', 'directory/country_collection', $collection);

        $this->assertSame(true, Mage::helper('ebayenterprise_catalog')->isValidIsoCountryCode($countryCode));
    }
    /**
     * Test normalizing a product style id to match formatting for skus
     * @param  string $style   The product style id
     * @param  string $catalog The product catalog id
     * @dataProvider dataProvider
     */
    public function testNormalizeSku($styleId, $catalogId)
    {
        $normalized = Mage::helper('ebayenterprise_catalog')->normalizeSku($styleId, $catalogId);
        $this->assertSame($this->expected('style-%s-%s', $styleId, $catalogId)->getStyleId(), $normalized);
    }

    /**
     * Test EbayEnterprise_Catalog_Helper_Data::denormalizeSku method for the following expectations
     * Expectation 1: this test will invoked the method EbayEnterprise_Catalog_Helper_Data::denormalizeSku given a sku
     *                with sku and a catalog id to denormalize the sku
     */
    public function testDenormalizeSku()
    {
        $catalogId = '54';
        $testData = array(
            array(
                'sku' => '54-49392002',
                'expect' => '49392002'
            ),
            array(
                'sku' => '9484884',
                'expect' => '9484884'
            )
        );

        foreach ($testData as $data) {
            $this->assertSame($data['expect'], Mage::helper('ebayenterprise_catalog')->denormalizeSku($data['sku'], $catalogId));
        }
    }
    /**
     * Test getProductHtsCodeByCountry method for the following expectations
     * Expectation 1: the method EbayEnterprise_Catalog_Helper_Data::getProductHtsCodeByCountry will be invoked by this test
     *                given a mock Mage_Catalog_Model_Product object and known country code, then the test expect
     *                the method Mage_Catalog_Model_Product::getHtsCodes to be called once and return a know serialize
     *                string in which will be unzerialized and loop through to return the match htscode data match match
     *                the given country code
     */
    public function testGetProductHtsCodeByCountry()
    {
        $countryCode = 'US';
        $data = array(array('destination_country' => $countryCode, 'hts_code' => '73739.33'),);
        $htscodes = serialize($data);

        $productMock = $this->getModelMockBuilder('catalog/product')
            ->disableOriginalConstructor()
            ->setMethods(array('getHtsCodes'))
            ->getMock();
        $productMock->expects($this->once())
            ->method('getHtsCodes')
            ->will($this->returnValue($htscodes));

        $this->assertSame($data[0]['hts_code'], Mage::helper('ebayenterprise_catalog')->getProductHtsCodeByCountry(
            $productMock,
            $countryCode
        ));
    }

    /**
     * @see self::testGetProductHtsCodeByCountry, however this test is expecting no hts_code data in which
     *      the return value for the method EbayEnterprise_Catalog_Helper_Data::getProductHtsCodeByCountry to return null
     */
    public function testGetProductHtsCodeByCountryNoHtsCodeFound()
    {
        $countryCode = 'US';
        $htscodes = serialize(array());

        $productMock = $this->getModelMockBuilder('catalog/product')
            ->disableOriginalConstructor()
            ->setMethods(array('getHtsCodes'))
            ->getMock();
        $productMock->expects($this->once())
            ->method('getHtsCodes')
            ->will($this->returnValue($htscodes));

        $this->assertSame(null, Mage::helper('ebayenterprise_catalog')->getProductHtsCodeByCountry(
            $productMock,
            $countryCode
        ));
    }
}
