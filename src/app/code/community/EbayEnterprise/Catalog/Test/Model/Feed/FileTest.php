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


class EbayEnterprise_Catalog_Test_Model_Feed_FileTest extends EbayEnterprise_Eb2cCore_Test_Base
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
     * Data provider for testing the constructor. Provides the array
     * of file details and, if expected for the given set of details, the
     * message for the error triggered.
     * @return array
     */
    public function provideConstructorDetailsAndErrors()
    {
        return array(
            array(array('doc' => Mage::helper('eb2ccore')->getNewDomDocument(), 'error_file' => 'error_file.xml'), null),
            array(array('doc' => "this isn't a DOMDocument", 'error_file' => 'error_file.xml'), 'User Error: EbayEnterprise_Catalog_Model_Feed_File::__construct called with invalid doc. Must be instance of EbayEnterprise_Dom_Document'),
            array(array('wat' => "There's aren't the arguments you're looking for"), 'User Error: EbayEnterprise_Catalog_Model_Feed_File::__construct called without required feed details: doc, error_file missing.'),
        );
    }
    /**
     * Test constructing instances - should be invoked with an array of
     * "feed details". The array of data the model is instantiated with
     * must contain keys for 'error_file' and 'doc'. When instantiated with a
     * proper set of file details, the array should be stored on the _feedDetails
     * property. When given an invalid set of file details, an error should be triggered.
     * @param array  $fileDetails  Argument to the constructor
     * @param string $errorMessage Expected error message, if empty, no error is expected
     * @dataProvider provideConstructorDetailsAndErrors
     */
    public function testConstruction($fileDetails, $errorMessage)
    {
        if ($errorMessage) {
            // Errors should be getting converted to PHPUnit_Framework_Error but
            // they aren't...instead just getting plain ol' Exceptions...so at least the
            // messages are rather explicit so hopefully no miscaught exceptions by this test.
            $this->setExpectedException('Exception', $errorMessage);
        }

        $feedFile = Mage::getModel('ebayenterprise_catalog/feed_file', $fileDetails);

        if (!$errorMessage) {
            $this->assertSame($fileDetails, EcomDev_Utils_Reflection::getRestrictedPropertyValue($feedFile, '_feedDetails'));
        }
    }
    /**
     * Test _getSkusToRemoveFromWebsites method with the following assumptions when invoked by this test
     * Expectation 1: this set first set the class property EbayEnterprise_Catalog_Model_Feed_File::_feedDetails
     *                to a known state of key value array
     * Expectation 2: when the method EbayEnterprise_Catalog_Model_Feed_File::_getSkusToRemoveFromWebsites get invoked by this
     *                test the method EbayEnterprise_Catalog_Model_Feed_File::_getDoc will be called once and it
     *                will return the DOMDocument object, then the method EbayEnterprise_Catalog_Helper_Data::splitDomByXslt
     *                will be called with the DOMDocument object and the xslt full path to the delete template file, this method
     *                will return DOMDocument object with skus to be deleted
     * Expectation 3: the DOMDocument object will be passed as parameter to the EbayEnterprise_Eb2cCore_Helper_Data::getNewDomXPath
     *                method which will return the DOMXPath object, with this xpath object the method query the each sku node and
     *                extract each sku to be deleted into an array of skus
     * Expectation 4: this array of skus then get return
     * @mock EbayEnterprise_Catalog_Model_Feed_File::_getDoc
     * @mock EbayEnterprise_Catalog_Helper_Data::splitDomByXslt
     * @mock EbayEnterprise_Eb2cCore_Helper_Data::getNewDomXPath
     */
    public function testGetSkusToRemoveFromWebsites()
    {
        $cfgData = array(
            'xslt_deleted_sku' => 'delete-template.xsl',
            'xslt_module' => 'EbayEnterprise_Catalog',
            'deleted_base_xpath' => 'sku',
        );
        $skus = array('45-4321' , '45-9432');
        $dData = array(
            $skus[0] => array('gsi_client_id' => 'MAGTNA', 'catalog_id' => '45'),
            $skus[1] => array('gsi_client_id' => 'MAGTNA', 'catalog_id' => '45')
        );
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML(
            '<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-1234</ClientItemId>
					</ItemId>
				</Item>
				<Item operation_type="Delete" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-4321</ClientItemId>
					</ItemId>
				</Item>
				<Item operation_type="Delete" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-9432</ClientItemId>
					</ItemId>
				</Item>
			</ItemMaster>'
        );

        $dlDoc = Mage::helper('eb2ccore')->getNewDomDocument();
        $dlDoc->loadXML(
            '<product_to_be_deleted>
				<sku operation_type="Delete" gsi_client_id="MAGTNA" catalog_id="45">45-4321</sku>
				<sku operation_type="Delete" gsi_client_id="MAGTNA" catalog_id="45">45-9432</sku>
			</product_to_be_deleted>'
        );

        $catalogId = '45';

        $xpath = new DOMXPath($dlDoc);

        $xslt = 'path/to/delete/xslt.xsl';

        $productHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
            ->disableOriginalConstructor()
            ->setMethods(array('splitDomByXslt', 'normalizeSku'))
            ->getMock();
        $productHelperMock->expects($this->once())
            ->method('splitDomByXslt')
            ->with($this->equalTo($doc), $this->equalTo($xslt))
            ->will($this->returnValue($dlDoc));
        $productHelperMock->expects($this->exactly(2))
            ->method('normalizeSku')
            ->will($this->returnValueMap(array(
                array($skus[0], $catalogId, $skus[0]),
                array($skus[1], $catalogId, $skus[1])
            )));

        $coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
            ->disableOriginalConstructor()
            ->setMethods(array('getNewDomXPath'))
            ->getMock();
        $coreHelperMock->expects($this->once())
            ->method('getNewDomXPath')
            ->with($this->equalTo($dlDoc))
            ->will($this->returnValue($xpath));

        $file = $this->getModelMockBuilder('ebayenterprise_catalog/feed_file')
            ->disableOriginalConstructor()
            ->setMethods(array('_getDoc', '_getXsltPath'))
            ->getMock();
        $file->expects($this->once())
            ->method('_getDoc')
            ->will($this->returnValue($doc));
        $file->expects($this->once())
            ->method('_getXsltPath')
            ->with(
                $this->identicalTo($cfgData['xslt_deleted_sku']),
                $this->identicalTo($cfgData['xslt_module'])
            )
            ->will($this->returnValue($xslt));

        $feedDetails = array(
            'doc' => $doc,
            'local' => '/EbayEnterprise/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_Subset.xml',
            'remote' => '/ItemMaster/',
            'timestamp' => '2012-07-06 10:09:05',
            'type' => 'ItemMaster',
            'error_file' => '/EbayEnterprise/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'
        );
        EcomDev_Utils_Reflection::setRestrictedPropertyValues(
            $file,
            array('_helper' => $productHelperMock, '_coreHelper' => $coreHelperMock, '_feedDetails' => $feedDetails)
        );

        $this->assertSame($dData, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $file,
            '_getSkusToRemoveFromWebsites',
            array($cfgData)
        ));
    }
    /**
     * Test getting an array of all SKUs contained in a split feed file. Can
     * assume that any SKUs to delete have already been stripped out by the XSLT.
     */
    public function testGetSkusToUpdate()
    {
        $cfgData = array('all_skus_xpath' => '/Items/Item/ItemId/ClientItemId|/Items/Item/UniqueID|/Items/Item/ClientItemId');
        $skus = array('45-12345', '45-23456', '45-34567');
        $doc = '<Items>
					<Item><ItemId><ClientItemId>45-12345</ClientItemId></ItemId></Item>
					<Item><ClientItemId>45-23456</ClientItemId></Item>
					<Item><UniqueID>45-34567</UniqueID></Item>
				</Items>';

        $dom = Mage::helper('eb2ccore')->getNewDomDocument();
        $dom->loadXML($doc);
        $xpath = new DOMXPath($dom);

        $catalogId = 45;

        $catalogHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
            ->disableOriginalConstructor()
            ->setMethods(array('normalizeSku'))
            ->getMock();
        $catalogHelperMock->expects($this->exactly(3))
            ->method('normalizeSku')
            ->will($this->returnValueMap(array(
                array($skus[0], $catalogId, $skus[0]),
                array($skus[1], $catalogId, $skus[1]),
                array($skus[2], $catalogId, $skus[2])
            )));

        $coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
            ->disableOriginalConstructor()
            ->setMethods(array('getConfigModel'))
            ->getMock();
        $coreHelperMock->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry(array(
                'catalogId' => $catalogId
            ))));

        $file = $this->getModelMockBuilder('ebayenterprise_catalog/feed_file')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        EcomDev_Utils_Reflection::setRestrictedPropertyValues(
            $file,
            array(
                '_helper' => $catalogHelperMock,
                '_coreHelper' => $coreHelperMock,
                '_logger' => Mage::helper('ebayenterprise_magelog'),
                '_context' => Mage::helper('ebayenterprise_magelog/context'),
            )
        );
        $this->assertSame($skus, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $file,
            '_getSkusToUpdate',
            array($xpath, $cfgData)
        ));
    }
    /**
     * Test EbayEnterprise_Catalog_Model_Feed_File::_removeFromWebsites method for the following expectations
     * Expectation 1: this test will invoked the method EbayEnterprise_Catalog_Model_Feed_File::_removeFromWebsites given
     *                a mocked Mage_Catalog_Model_Resource_Product_Collection object and an array of extracted data to be
     *                remove per for any given website.
     * Expectation 2: the method EbayEnterprise_Catalog_Helper_Data::loadWebsiteFilter will be called which will return
     *                an array of websites with client id, catalog id and mage website id keys per webssites
     * Expectation 3: the method EbayEnterprise_Catalog_Model_Feed_File::_getSkusInWebsite will be invoked given the array
     *                of skus data and an array of specific website data
     * Expectation 4: the method Mage_Catalog_Model_Resource_Product_Collection::getItemById will then be invoked given
     *                a sku if the return value is an Mage_Catalog_Model_Product object it will call the method
     *                Mage_Catalog_Model_Product::getWebsites which will return an array of all website ids in this product
     *                this array will be pass as the first parameter to the method EbayEnterprise_Catalog_Model_Feed_File::_removeWebsiteId
     *                and a second parameter of the website data mage website id which will return an array excluding the website id
     *                that was pass it and this return array will be pass to the method Mage_Catalog_Model_Product::setWebsites method
     * Expectation 5: last thing is the method Mage_Catalog_Model_Resource_Product_Collection::save will be inovked
     */
    public function testRemoveFromWebsite()
    {
        $sku = '45-1334';
        $dData = array($sku => array());
        $websiteFilters = array(
            array('mage_website_id' => '1'),
            array('mage_website_id' => '2')
        );
        $wIds = array('1', '2', '3');
        $removedIds = array('2', '3');

        $productMock = $this->getModelMockBuilder('catalog/product')
            ->disableOriginalConstructor()
            ->setMethods(array('getWebsiteIds', 'setWebsiteIds'))
            ->getMock();
        $productMock->expects($this->once())
            ->method('getWebsiteIds')
            ->will($this->returnValue($wIds));
        $productMock->expects($this->once())
            ->method('setWebsiteIds')
            ->with($this->identicalTo($removedIds))
            ->will($this->returnSelf());

        $collectionMock = $this->getResourceModelMockBuilder('ebayenterprise_catalog/feed_product_collection')
            ->disableOriginalConstructor()
            ->setMethods(array('getItemById', 'save'))
            ->getMock();
        $collectionMock->expects($this->once())
            ->method('getItemById')
            ->with($this->identicalTo($sku))
            ->will($this->returnValue($productMock));
        $collectionMock->expects($this->once())
            ->method('save')
            ->will($this->returnSelf());

        $helperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/data')
            ->disableOriginalConstructor()
            ->setMethods(array('loadWebsiteFilters'))
            ->getMock();
        $helperMock->expects($this->once())
            ->method('loadWebsiteFilters')
            ->will($this->returnValue($websiteFilters));
        $this->replaceByMock('helper', 'ebayenterprise_catalog', $helperMock);

        $fileMock = $this->getModelMockBuilder('ebayenterprise_catalog/feed_file')
            ->disableOriginalConstructor()
            ->setMethods(array('_getSkusInWebsite', '_removeWebsiteId'))
            ->getMock();
        $fileMock->expects($this->exactly(2))
            ->method('_getSkusInWebsite')
            ->will($this->returnValueMap(array(
                array($dData, $websiteFilters[0], array($sku)),
                array($dData, $websiteFilters[1], array())
            )));
        $fileMock->expects($this->once())
            ->method('_removeWebsiteId')
            ->with($this->identicalTo($wIds), $this->identicalTo($websiteFilters[0]['mage_website_id']))
            ->will($this->returnValue($removedIds));

        EcomDev_Utils_Reflection::setRestrictedPropertyValues($fileMock, array('_helper' => $helperMock));
        $this->assertSame($fileMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $fileMock,
            '_removeFromWebsites',
            array($collectionMock, $dData)
        ));
    }

    /**
     * Test EbayEnterprise_Catalog_Model_Feed_File::_getSkusInWebsite method for the following expectations
     * Expectation 1: this test will invoked the method EbayEnterprise_Catalog_Model_Feed_File::_getSkusInWebsite given
     *                an array of skus map to gsi_client_id/catalog_id and the second parameter pass is an array of
     *                website containing config for client_id, and catalog_id
     */
    public function testGetSkusInWebsite()
    {
        $skus = array('52-8842', '53-9448');
        $result = array($skus[0]);
        $dData = array(
            $skus[0] => array(
                'gsi_client_id' => 'MWS',
                'catalog_id' => '52'
            ),
            $skus[1] => array(
                'gsi_client_id' => 'SWM',
                'catalog_id' => '53'
            ),
        );
        $wData = array(
            'client_id' => 'MWS',
            'catalog_id' => '52'
        );

        $fileMock = $this->getModelMockBuilder('ebayenterprise_catalog/feed_file')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $fileMock,
            '_getSkusInWebsite',
            array($dData, $wData)
        ));
    }

    /**
     * Test EbayEnterprise_Catalog_Model_Feed_File::_removeWebsiteId method for the following expectations
     * Expectation 1: the method EbayEnterprise_Catalog_Model_Feed_File::_removeWebsiteId will be invoked by this test
     *                given an array of website ids and string of website id to exclude from the website ids
     */
    public function testRemoveWebsiteId()
    {
        $websiteIds = array('1', '2', '3');
        $result = array('1' => '2', '2' => '3');
        $websiteId = '1';

        $fileMock = $this->getModelMockBuilder('ebayenterprise_catalog/feed_file')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $fileMock,
            '_removeWebsiteId',
            array($websiteIds, $websiteId)
        ));
    }
}
