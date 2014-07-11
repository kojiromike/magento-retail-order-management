<?php
class EbayEnterprise_Eb2cProduct_Test_Model_Feed_CleanerTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	protected function _getProductCollectionStub()
	{
		$col = $this->getResourceModelMockBuilder('catalog/product_collection')
			->setMethods(array('load', 'save'))
			->disableOriginalConstructor()
			->getMock();
		$col->setItemObjectClass('catalog/product');
		$col->expects($this->any())
			->method('load')
			->will($this->returnSelf());
		$col->expects($this->any())
			->method('save')
			->will($this->returnSelf());
		return $col;
	}
	/**
	 * Test getProductsToClean method, making sure our assumption are correct and
	 * the right parameters are being pass to the collect to filter where is_clean is false
	 */
	public function testGetProductsToClean()
	{
		$catalogResourceModelProductMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('addAttributeToSelect', 'addFieldToFilter'))
			->getMock();

		$catalogResourceModelProductMock->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->equalTo('*'))
			->will($this->returnSelf());
		$catalogResourceModelProductMock->expects($this->once())
			->method('addFieldToFilter')
			->with($this->equalTo('is_clean'), $this->equalTo(false))
			->will($this->returnSelf());

		$catalogModelProductMock = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getCollection'))
			->getMock();
		$catalogModelProductMock->expects($this->any())
			->method('getCollection')
			->will($this->returnValue($catalogResourceModelProductMock));
		$this->replaceByMock('model', 'catalog/product', $catalogModelProductMock);

		$cleanerObject = Mage::getModel('eb2cproduct/feed_cleaner', array('products' => $this->_getProductCollectionStub()));

		$this->assertInstanceOf(
			'Mage_Catalog_Model_Resource_Product_Collection',
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$cleanerObject,
				'_getProductsToClean'
			)
		);
	}
	/**
	 * Test getting all skus that may be affected by the cleaner. This includes
	 * any products mentioned in the unresolved product links of products to be
	 * cleaned, any potential parent configurable products of products to be
	 * cleaned and any potential simple used products of the products to be cleaned.
	 */
	public function testGetAffectedSkus()
	{
		$linkedSkus = array('45-link-1', '45-link-2');
		$parentConfigSkus = array('45-parent-sku', '45-link-2');
		$usedSimpleSkus = array('45-simple');

		$products = $this->_getProductCollectionStub();

		$cleaner = $this->getModelMock(
			'eb2cproduct/feed_cleaner',
			array('_getAllLinkedSkus', '_getAllParentConfigurableSkus', '_getAllUsedProductSkus'),
			false,
			array(array('products' => $products))
		);

		$cleaner->expects($this->once())
			->method('_getAllLinkedSkus')
			->with($this->identicalTo($products))
			->will($this->returnValue($linkedSkus));
		$cleaner->expects($this->once())
			->method('_getAllParentConfigurableSkus')
			->with($this->identicalTo($products))
			->will($this->returnValue($parentConfigSkus));
		$cleaner->expects($this->once())
			->method('_getAllUsedProductSkus')
			->with($this->identicalTo($products))
			->will($this->returnValue($usedSimpleSkus));
		$this->assertEquals(
			array_values(array('45-link-1', '45-link-2', '45-parent-sku', '45-simple')),
			array_values(EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$cleaner,
				'_getAffectedSkus',
				array($products)
			))
		);
	}
	/**
	 * Test getting an array of all skus included in the unresolved product links
	 * for all products in a given collection.
	 */
	public function testGetAllLinkedSkus()
	{
		$products = $this->_getProductCollectionStub();
		$products->addItem(Mage::getModel('catalog/product', array(
			'unresolved_product_links' => serialize(array(
				array('link_type' => 'related', 'operation_type' => 'Add', 'link_to_unique_id' => '45-related'),
				array('link_type' => 'upsell', 'operation_type' => 'Add', 'link_to_unique_id' => '45-upsell'),
			)),
		)));
		$products->addItem(Mage::getModel('catalog/product', array(
			'unresolved_product_links' => serialize(array(
				array('link_type' => 'crosssell', 'operation_type' => 'Add', 'link_to_unique_id' => '45-crosssell'),
			)),
		)));

		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner', array('products' => $products));
		$this->assertSame(
			array_values(array('45-related', '45-upsell', '45-crosssell')),
			array_values(EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$cleaner,
				'_getAllLinkedSkus',
				array($products)
			))
		);
	}

	/**
	 * Test determining if a product is expected to have a configurable parent products.
	 */
	public function testGetAllParentConfigurableSkus()
	{
		$products = $this->_getProductCollectionStub();
		// should not be included as style_id and sku match
		$products->addItem(Mage::getModel('catalog/product', array(
			'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
			'style_id' => '45-simple-matching-styleid',
			'sku' => '45-simple-matching-styleid'
		)));
		// should not be included as product has no style_id
		$products->addItem(Mage::getModel('catalog/product', array(
			'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
			'sku' => '45-simple-no-styleid'
		)));
		// should not be included as only simple products can be child products
		$products->addItem(Mage::getModel('catalog/product', array(
			'type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
			'style_id' => '45-config-style',
			'sku' => '45-config-sku'
		)));
		// should be included - simple product with style_id that does not match sku
		$products->addItem(Mage::getModel('catalog/product', array(
			'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
			'style_id' => '45-parent-sku',
			'sku' => '45-child-sku'
		)));

		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner', array('products' => $products));
		$this->assertEquals(
			array_values(array('45-parent-sku')),
			array_values(EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$cleaner,
				'_getAllParentConfigurableSkus',
				array($products)
			))
		);
	}
	/**
	 * Get all of the simple products used to configure the products included in
	 * the collection of products.
	 */
	public function testGetAllUsedProductSkus()
	{
		$products = $this->_getProductCollectionStub();
		$products->addItem(Mage::getModel('catalog/product', array(
			'type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
			'sku' => '45-parent-sku-1',
		)));
		$products->addItem(Mage::getModel('catalog/product', array(
			'type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
			'sku' => '45-parent-sku-2',
		)));
		$products->addItem(Mage::getModel('catalog/product', array(
			'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
			'sku' => '45-simple-sku',
		)));

		$collectionMock = $this->getResourceModelMockBuilder('catalog/product_collection')
			->setMethods(array('addAttributeToFilter', 'getColumnValues'))
			->disableOriginalConstructor()
			->getMock();
		// This is far more brittle than I'd like but in order to ensure that both
		// filters are added, need to use the overly strict check.
		$collectionMock->expects($this->exactly(2))
			->method('addAttributeToFilter')
			->will($this->returnSelf());
		$collectionMock->expects($this->at(0))
			->method('addAttributeToFilter')
			->with(
				$this->identicalTo('style_id'),
				$this->identicalTo(array('in' => array('45-parent-sku-1', '45-parent-sku-2')))
			)
			->will($this->returnSelf());
		$collectionMock->expects($this->at(1))
			->method('addAttributeToFilter')
			->with(
				$this->identicalTo('type_id'),
				$this->identicalTo(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
			)
			->will($this->returnSelf());
		$collectionMock->expects($this->once())
			->method('getColumnValues')
			->with($this->identicalTo('sku'))
			->will($this->returnValue(array('45-child-sku-1', '45-child-sku-2')));

		$productMock = $this->getModelMock('catalog/product', array('getCollection'));
		$productMock->expects($this->any())
			->method('getCollection')
			->will($this->returnValue($collectionMock));
		$this->replaceByMock('model', 'catalog/product', $productMock);

		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner', array('products' => $products));

		$this->assertSame(
			array_values(array('45-child-sku-1', '45-child-sku-2')),
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$cleaner,
				'_getAllUsedProductSkus',
				array($products)
			)
		);
	}
	/**
	 * Provide various values for the product's unresolved_product_links and the
	 * value they are expected to result in.
	 * @return array
	 */
	public function provideProductLinks()
	{
		$unresolvedLinks = array(array('link_type' => 'related', 'operation_type' => 'Add', 'link_to_unique_id' => '45-12345'));
		return array(
			array(serialize(array()), array()),
			array(serialize($unresolvedLinks), $unresolvedLinks),
			array(serialize(null), array()),
			array(null, array()),
			array(serialize('How did this get in here?'), array()),
		);
	}
	/**
	 * Test getting the array of unresolved product links for a product, ensuring
	 * that an array is always returned.
	 * @param  string|null $rawValue Raw, serialized value of the attribute
	 * @param  array $links Expected links
	 * @dataProvider provideProductLinks
	 */
	public function testGetAllUnresolvedRelations($rawValue, $links)
	{
		$product = Mage::getModel('catalog/product', array('unresolved_product_links' => $rawValue));
		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner', array('products' => $this->_getProductCollectionStub()));
		$this->assertSame(
			$links,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$cleaner,
				'_getAllUnresolvedProductLinks',
				array($product)
			)
		);
	}
	/**
	 * Test reducing the array of products links into the array of skus. All skus,
	 * in the products links as 'link_to_unique_id', should be added to the array
	 * of skus passed to the method and the updated list of skus returned.
	 */
	public function testReduceLinksToSkus()
	{
		$relatedLinks = array(
			array('link_to_unique_id' => '45-23456'),
			array('link_to_unique_id' => '45-34567'),
			array('link_to_unique_id' => '45-45678'),
			array(),
			array('something_else' => 23),
		);
		$skus = array('45-12345', '45-23456');
		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner', array('products' => $this->_getProductCollectionStub()));
		$this->assertSame(
			array('45-12345', '45-23456', '45-23456', '45-34567', '45-45678'),
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$cleaner, '_reduceLinksToSkus', array($skus, $relatedLinks)
			)
		);
	}
	/**
	 * Test cleaning all products. Assertions are intentionally simplistic as the
	 * more rigorous coverage is included in the other test methods.
	 * I just want to ensure that when put together, all the pieces work to some
	 * verifiable degree.
	 */
	public function testCleanAllProducts()
	{
		$newCollection = $this->getResourceModelMockBuilder('catalog/product_collection')
			->setMethods(array('save', 'load'))
			->disableOriginalConstructor()
			->getMock();
		// manually set the collection item class - normally done by constructor but
		// that has been disabled to prevent the DB connection
		$newCollection->setItemObjectClass('catalog/product');
		$newCollection->expects($this->once())
			->method('save')
			->will($this->returnSelf());
		foreach (range(1, 5) as $id) {
			$newCollection->addItem(Mage::getModel('catalog/product')->addData(array('entity_id' => $id)));
		}

		$feedCleanerModelProductMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->setMethods(array('cleanProduct'))
			->setConstructorArgs(array(array('products' => $newCollection)))
			->getMock();
		$feedCleanerModelProductMock->expects($this->exactly(5))
			->method('cleanProduct')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());

		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cProduct_Model_Feed_Cleaner',
			$feedCleanerModelProductMock->cleanAllProducts()
		);
	}

	/**
	 * Test cleanProduct method, when the product is configurable
	 */
	public function testCleanProductWithConfigurableProduct()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getTypeId'))
			->getMock();
		$product->expects($this->once())
			->method('getTypeId')
			->will($this->returnValue('configurable'));

		$feedCleanerModelProductMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->setMethods(array('_resolveProductLinks', '_addUsedProducts', 'markProductClean'))
			->disableOriginalConstructor()
			->getMock();
		$feedCleanerModelProductMock->expects($this->once())
			->method('_resolveProductLinks')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$feedCleanerModelProductMock->expects($this->once())
			->method('_addUsedProducts')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$feedCleanerModelProductMock->expects($this->once())
			->method('markProductClean')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $feedCleanerModelProductMock);

		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cProduct_Model_Feed_Cleaner',
			Mage::getModel('eb2cproduct/feed_cleaner')->cleanProduct($product)
		);
	}

	/**
	 * Test cleanProduct method, when the product is a child of a configurable
	 */
	public function testCleanProductWithChildConfigurableProduct()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getTypeId', 'getSku', 'getStyleId'))
			->getMock();
		$product->expects($this->once())
			->method('getTypeId')
			->will($this->returnValue('simple'));
		$product->expects($this->once())
			->method('getSku')
			->will($this->returnValue('1234-Simple'));
		$product->expects($this->exactly(2))
			->method('getStyleId')
			->will($this->returnValue('1234-Configurable'));

		$feedCleanerModelProductMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->setMethods(array('_resolveProductLinks', '_addToConfigurableProduct', 'markProductClean'))
			->disableOriginalConstructor()
			->getMock();
		$feedCleanerModelProductMock->expects($this->once())
			->method('_resolveProductLinks')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$feedCleanerModelProductMock->expects($this->once())
			->method('_addToConfigurableProduct')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$feedCleanerModelProductMock->expects($this->once())
			->method('markProductClean')
			->with($this->isInstanceOf('Mage_Catalog_Model_Product'))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $feedCleanerModelProductMock);

		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cProduct_Model_Feed_Cleaner',
			Mage::getModel('eb2cproduct/feed_cleaner')->cleanProduct($product)
		);
	}

	/**
	 * Test _resolveProductLinks method, when the product has unresolved link data
	 */
	public function testResolveProductLinks()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getUnresolvedProductLinks', 'setUnresolvedProductLinks'))
			->getMock();
		$product->expects($this->once())
			->method('getUnresolvedProductLinks')
			->will($this->returnValue(
				'a:1:{i:0;a:3:{s:9:"link_type";s:7:"related";s:14:"operation_type";s:3:"Add";s:17:"link_to_unique_id";s:7:"clean_0";}}'
			));
		$product->expects($this->once())
			->method('setUnresolvedProductLinks')
			->will($this->returnSelf());

		$feedCleanerModelProductMock = $this->getModelMockBuilder('eb2cproduct/feed_cleaner')
			->setMethods(array('_linkProducts'))
			->disableOriginalConstructor()
			->getMock();
		$feedCleanerModelProductMock->expects($this->at(0))
			->method('_linkProducts')
			->with(
				$this->isInstanceOf('Mage_Catalog_Model_Product'),
				$this->equalTo(array(array(
					'link_type' => 'related',
					'operation_type' => 'Add',
					'link_to_unique_id' => 'clean_0',
				))),
				$this->equalTo('related')
			)
			->will($this->returnValue(array(array(
				'link_type' => 'related',
				'operation_type' => 'Add',
				'link_to_unique_id' => 'clean_0',
			))));
		$feedCleanerModelProductMock->expects($this->at(1))
			->method('_linkProducts')
			->with(
				$this->isInstanceOf('Mage_Catalog_Model_Product'),
				$this->equalTo(array()),
				$this->equalTo('upsell')
			)
			->will($this->returnValue(array()));
		$feedCleanerModelProductMock->expects($this->at(2))
			->method('_linkProducts')
			->with(
				$this->isInstanceOf('Mage_Catalog_Model_Product'),
				$this->equalTo(array()),
				$this->equalTo('crosssell')
			)
			->will($this->returnValue(array()));
		$this->replaceByMock('model', 'eb2cproduct/feed_cleaner', $feedCleanerModelProductMock);

		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cProduct_Model_Feed_Cleaner',
			$this->_reflectMethod($feedCleanerModelProductMock, '_resolveProductLinks')->invoke($feedCleanerModelProductMock, $product)
		);
	}

	/**
	 * Test _linkProducts method, when the product has unresolved link data and
	 * the product to be linked does not yet exist.
	 */
	public function testLinkProducts()
	{
		// Type of link to create.
		$linkType = 'related';
		// Product links to try to make
		$linkUpdates = array(
			array(
				'link_type' => $linkType, 'operation_type' => 'Add','link_to_unique_id' => '45-missing-link',
			),
			array(
				'link_type' => $linkType, 'operation_type' => 'Add','link_to_unique_id' => '45-add-link',
			),
			array(
				'link_type' => $linkType, 'operation_type' => 'delete', 'link_to_unique_id' => '45-link-to-remove',
			),
		);
		$addId = 13;
		$deleteId = 29;
		$existingId = 37;
		// Cleaners product collection - put nothing in it to ensure that
		// the sku being looked for to link doesn't exist in it.
		$productCollection = $this->_getProductCollectionStub();
		// this product should be selected as one to add
		$productCollection->addItem(Mage::getModel('catalog/product', array(
			'sku' => '45-add-link', 'entity_id' => $addId,
		)));
		// this product, while in the links, should not be selected as one to add
		// as the link_type in the link update is not of the right type
		$productCollection->addItem(Mage::getModel('catalog/product', array(
			'sku' => '45-wrong-type', 'entity_id' => 99,
		)));

		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getRelatedProducts'))
			->getMock();
		// already linked products of the type being linked
		$product->expects($this->once())
			->method('getRelatedProducts')
			->will($this->returnValue(array(
				// this product should remain in the related product links
				Mage::getModel('catalog/product', array('sku' => '1234-Related', 'entity_id' => $existingId)),
				// this product should be removed
				Mage::getModel('catalog/product', array('sku' => '45-link-to-remove', 'entity_id' => $deleteId)),
			)));

		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner', array('products' => $productCollection));

		$this->assertSame(
			array(array(
				'link_to_unique_id' => '45-missing-link',
				'link_type' => 'related',
				'operation_type' => 'Add',
			)),
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$cleaner,
				'_linkProducts',
				array($product, $linkUpdates, $linkType)
			)
		);
		$this->assertSame(
			array($existingId => array('position' => ''), $addId => array('position' => '')),
			$product->getData('related_link_data')
		);
	}

	/**
	 * Test _addUsedProducts method, when the product has used product data
	 */
	public function testAddUsedProducts()
	{
		$parentId = 1;
		$parentSku = '45-parent-style';
		$childId = 2;
		$existingId = 3;
		// Products the cleaner knows about
		$productCollection = $this->_getProductCollectionStub();
		// expect this product to be added
		$productCollection->addItem(Mage::getModel('catalog/product', array(
			'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
			'style_id' => $parentSku,
			'entity_id' => $childId,
		)));
		// can't add a config product as a used product
		$productCollection->addItem(Mage::getModel('catalog/product', array(
			'type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
			'style_id' => $parentSku,
			'entity_id' => 37,
		)));
		// style id of this product doesn't match sku of config, so should not be linked
		$productCollection->addItem(Mage::getModel('catalog/product', array(
			'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
			'style_id' => null,
			'entity_id' => 53,
		)));

		// product being cleaned
		$product = $this->getModelMock(
			'catalog/product',
			array('getTypeInstance'),
			false,
			array(array('sku' => $parentSku, 'entity_id' => $parentId))
		);
		// product type model of the product being cleaned
		$configurable = $this->getModelMockBuilder('catalog/product_type_configurable')
			->setMethods(array('saveProducts', 'getUsedProductIds'))
			->disableOriginalConstructor()
			->getMock();
		// resource model used to save the configurable product data
		$configurableResource = $this->getResourceModelMockBuilder('catalog/product_type_configurable')
			->disableOriginalConstructor()
			->setMethods(array('saveProducts'))
			->getMock();
		$this->replaceByMock('resource_model', 'catalog/product_type_configurable', $configurableResource);

		$product->expects($this->any())
			->method('getTypeInstance')
			->will($this->returnValue($configurable));

		$configurable->expects($this->once())
			->method('getUsedProductIds')
			->will($this->returnValue(array($existingId)));

		$configurableResource->expects($this->once())
			->method('saveProducts')
			->with($this->identicalTo($product), $this->identicalTo(array($existingId, $childId)))
			->will($this->returnSelf());

		$cleanerObject = Mage::getModel('eb2cproduct/feed_cleaner', array('products' => $productCollection));

		$this->assertSame(
			$cleanerObject,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$cleanerObject,
				'_addUsedProducts',
				array($product)
			)
		);
		$this->assertSame(array($existingId, $childId), $product->getData('_cache_instance_product_ids'));
		// ensure the configurable product's status and visibility were updated
		$this->assertSame(Mage_Catalog_Model_Product_Status::STATUS_ENABLED, $product->getStatus());
		$this->assertSame(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH, $product->getVisibility());
	}

	/**
	 * Test _addToConfigurableProduct method, when child product has a known Parent configruable product
	 */
	public function testAddToConfigurableProduct()
	{
		$parentSku = '1234-Configurable';
		// Products the cleaner knows about
		$productCollection = $this->_getProductCollectionStub();
		// simple product to link to a configurable
		$product = Mage::getModel('catalog/product', array(
			'style_id' => $parentSku, 'entity_id' => 1
		));

		// the parent product expected to have the simple product added to
		$parentProduct = Mage::getModel('catalog/product', array(
			'sku' => $parentSku, 'type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
		));
		// add in some other products not expected to be chosen
		$productCollection->addItem(Mage::getModel('catalog/product', array(
			// must be configurable product
			'sku' => $parentSku, 'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
		)));
		$productCollection->addItem(Mage::getModel('catalog/product', array(
			// sku must patch used product's style id
			'sku' => '1234-something-else', 'type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
		)));
		// add the parent to the known products so the child can be added to it
		$productCollection->addItem($parentProduct);

		$configType = $this->getModelMockBuilder('catalog/product_type_configurable')
			->setMethods(array('getUsedProductIds'))
			->getMock();
		$configTypeResource = $this->getResourceModelMockBuilder('catalog/product_type_configurable')
			->disableOriginalConstructor()
			->setMethods(array('saveProducts'))
			->getMock();
		$this->replaceByMock('resource_model', 'catalog/product_type_configurable', $configTypeResource);

		// set the mocked type instance on the product model
		$parentProduct->setTypeInstance($configType);

		$configType->expects($this->any())
			->method('getUsedProductIds')
			->will($this->returnValue(array(2, 3, 4)));
		// make sure the used product is added to the right parent
		$configTypeResource->expects($this->once())
			->method('saveProducts')
			->with($this->identicalTo($parentProduct), $this->equalTo(array(2, 3, 4, 1)))
			->will($this->returnSelf());

		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner', array('products' => $productCollection));

		$this->assertSame(
			$cleaner,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$cleaner,
				'_addToConfigurableProduct',
				array($product)
			)
		);
		// this must get updated so future uses of this product by the cleaner will
		// include the correct used product ids
		$this->assertSame(array(2, 3, 4, 1), $parentProduct->getData('_cache_instance_product_ids'));
		// make sure the configurable product's status and visibility were updated
		$this->assertSame(Mage_Catalog_Model_Product_Status::STATUS_ENABLED, $parentProduct->getStatus());
		$this->assertSame(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH, $parentProduct->getVisibility());
	}

	/**
	 * Test _addToConfigurableProduct method, when child product has a known Parent configruable product
	 */
	public function testAddToCofigurableProductNoParentProduct()
	{
		$parentSku = '1234-Configurable';
		// Products the cleaner knows about
		$productCollection = $this->_getProductCollectionStub();

		$product = Mage::getModel('catalog/product', array(
			'style_id' => $parentSku, 'entity_id' => 1
		));
		// add in some other products not expected to be chosen
		$productCollection->addItem(Mage::getModel('catalog/product', array(
			// must be configurable product
			'sku' => $parentSku, 'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
		)));
		$productCollection->addItem(Mage::getModel('catalog/product', array(
			// sku must patch used product's style id
			'sku' => '1234-something-else', 'type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
		)));

		$configTypeResource = $this->getResourceModelMockBuilder('catalog/product_type_configurable')
			->disableOriginalConstructor()
			->setMethods(array('saveProducts'))
			->getMock();
		$this->replaceByMock('resource_model', 'catalog/product_type_configurable', $configTypeResource);

		// no parent was found so nothing shoudl get saved
		$configTypeResource->expects($this->never())
			->method('saveProducts');

		$cleaner = Mage::getModel('eb2cproduct/feed_cleaner', array('products' => $productCollection));

		$this->assertSame(
			$cleaner,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$cleaner,
				'_addToConfigurableProduct',
				array($product)
			)
		);
	}

	/**
	 * Test markProductClean method, marking product clean
	 */
	public function testMarkProductClean()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getUnresolvedProductLinks', 'setIsClean'))
			->getMock();
		$product->expects($this->once())
			->method('getUnresolvedProductLinks')
			->will($this->returnValue(serialize(array())));
		$product->expects($this->once())
			->method('setIsClean')
			->with($this->equalTo(true))
			->will($this->returnSelf());

		$cleanerObject = Mage::getModel('eb2cproduct/feed_cleaner', array('products' => $this->_getProductCollectionStub()));

		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cProduct_Model_Feed_Cleaner',
			$cleanerObject->markProductClean($product)
		);
	}

	/**
	 * Test markProductClean method, marking product clean
	 */
	public function testMarkProductCleanNoUnresolvedLinkData()
	{
		$product = $this->getModelMockBuilder('catalog/product')
			->setMethods(array('getUnresolvedProductLinks', 'setIsClean', 'getSku'))
			->getMock();
		$product->expects($this->once())
			->method('getUnresolvedProductLinks')
			->will($this->returnValue(
				'a:1:{i:0;a:3:{s:9:"link_type";s:7:"related";s:14:"operation_type";s:3:"Add";s:17:"link_to_unique_id";s:7:"clean_0";}}'
			));
		$product->expects($this->once())
			->method('setIsClean')
			->with($this->equalTo(false))
			->will($this->returnSelf());
		$product->expects($this->once())
			->method('getSku')
			->will($this->returnValue('1234'));

		$cleanerObject = Mage::getModel('eb2cproduct/feed_cleaner', array('products' => $this->_getProductCollectionStub()));

		$this->assertInstanceOf(
			'EbayEnterprise_Eb2cProduct_Model_Feed_Cleaner',
			$cleanerObject->markProductClean($product)
		);
	}
}
