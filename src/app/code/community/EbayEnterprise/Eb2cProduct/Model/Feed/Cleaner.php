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
 * "Second pass" of the product feed import.
 * Responsible for finding any products that have incomplete
 * product links and updating them. This class should update
 * upsell, related, cross-sell links as well as configurable
 * product links.
 */
class EbayEnterprise_Eb2cProduct_Model_Feed_Cleaner
{
	// Name of the magic data property used product ids need to be updated in
	// to keep the list of used product ids for a configurable product up-to-date
	// throughout the cleaning process.
	const USED_PRODUCT_IDS_PROPERTY = '_cache_instance_product_ids';
	/**
	 * Collection of products that will be used by the cleaner. Includes any
	 * products that need to be cleaned as well as any products that will be
	 * used by products being cleaned.
	 * @var Mage_Catalog_Model_Resource_Product_Collection
	 */
	protected $_products;
	// map eb2c link types to Magento product link data attribute
	protected $_linkTypes = array(
		'related' => array(
			'data_attribute' => 'related_link_data',
			'linked_products_getter_method' => 'getRelatedProducts',
		),
		'upsell' => array(
			'data_attribute' => 'up_sell_link_data',
			'linked_products_getter_method' => 'getUpSellProducts',
		),
		'crosssell' => array(
			'data_attribute' => 'cross_sell_link_data',
			'linked_products_getter_method' => 'getCrossSellProducts',
		),
	);
	/**
	 * Set up collection of products that will be used by the cleaner. The
	 * collection of products to clean may be passed in as a "products" key in
	 * the args array. If passed in, the collection must be a
	 * Mage_Catalog_Model_Resource_Product_Collection or an error will be triggered.
	 * @param array $args Use single args array to work with Magento factory methods.
	 */
	public function __construct($args)
	{
		// If a collection of products is supplied, use it as long as it is at least
		// a Varien_Data_Collection. More strictly checking for a
		// Mage_Catalog_Model_Resource_Product_Collection may be useful but is probably
		// also more strict than need be.
		if (isset($args['products'])) {
			if (!$args['products'] instanceof Mage_Catalog_Model_Resource_Product_Collection) {
				// treat this as if it had been a type mismatch for an argument to this method
				trigger_error(sprintf(
					'"products" must be an instance of Mage_Catalog_Model_Resource_Product_Collection. %s given.', gettype($args['products'])
				), E_RECOVERABLE_ERROR);
			}
			$this->_products = $args['products'];
		} else {
			$this->_products = $this->_getProducts();
		}
	}
	/**
	 * Go through all products that need to be cleaned and clean them.
	 */
	public function cleanAllProducts()
	{
		// Get all products that need cleaning - due to loose comparison of column
		// values by the collection, any products that do not include an 'is_clean'
		// column will be included.
		$productsToClean = $this->_products->getItemsByColumnValue('is_clean', false);
		Mage::log(sprintf('[ %s ]: Cleaning %d products.', __CLASS__, count($productsToClean)), Zend_Log::INFO);
		foreach ($productsToClean as $product) {
			$this->cleanProduct($product);
		}
		// save all the products that may have been modified while cleaning products
		$this->_products->save();
		return $this;
	}
	/**
	 * Get all products that will be needed by the cleaner. This includes all
	 * products that need to be cleaned as well as any products that may be
	 * affected by the cleaning process - linked products, parent configurable
	 * products or simple products used by a config product.
	 * @return Mage_Catalog_Model_Resource_Product_Collection
	 */
	protected function _getProducts()
	{
		$productsToClean = $this->_getProductsToClean();
		$affectedProducts = Mage::getModel('catalog/product')->getCollection()
			->addAttributeToFilter('sku', array('in' => $this->_getAffectedSkus($productsToClean)))
			->addAttributeToSelect('*');
		foreach ($affectedProducts as $product) {
			if (!$productsToClean->getItemById($product->getId())) {
				$productsToClean->addItem($product);
			}
		}
		return $productsToClean;
	}
	/**
	 * Get a collection of products that need to be cleaned.
	 * @return Mage_Catalog_Model_Resource_Product_Collection Collection of "dirty" products.
	 */
	protected function _getProductsToClean()
	{
		return Mage::getModel('catalog/product')->getCollection()
			// @todo - order products are processed may be optimizable
			->addFieldToFilter('is_clean', false)
			// @todo - trim this down to only the necessary attributes
			->addAttributeToSelect('*');
	}
	/**
	 * Get all skus that will be needed to resolve product links or configurable
	 * product relationships.
	 * @param  Mage_Catalog_Model_Resource_Product_Collection $productCollection
	 * @return array
	 */
	protected function _getAffectedSkus($productCollection)
	{
		// Double flip will reduce the array to unique values - substantially
		// more efficient than array_unique due to array_unique's sort which isn't
		// necessary in this scenario. Useful here as this list of skus could
		// get rather large, especially when doing the initial import which could
		// result in an entire catalog's worth of skus.
		return array_flip(array_flip(array_merge(
			$this->_getAllLinkedSkus($productCollection),
			$this->_getAllParentConfigurableSkus($productCollection),
			$this->_getAllUsedProductSkus($productCollection)
		)));
	}
	/**
	 * Get all skus mentioned by the unresolved product links in the collection
	 * of products to be cleaned.
	 * @param  Varien_Data_Collection $productCollection
	 * @return array
	 */
	protected function _getAllLinkedSkus(Varien_Data_Collection $productCollection)
	{
		return array_reduce(
			array_map(
				array($this, '_getAllUnresolvedProductLinks'),
				$productCollection->getItems()
			),
			array($this, '_reduceLinksToSkus'),
			array() // initial empty array for the reduce
		);
	}
	/**
	 * Get the skus of all potential configurable products that a simple product
	 * within the collection of products to be cleaned may need to be linked to.
	 * @param  Varien_Data_Collection $productCollection
	 * @return array
	 */
	protected function _getAllParentConfigurableSkus(Varien_Data_Collection $productCollection)
	{
		return array_map(
			function ($product) { return $product->getStyleId(); },
			array_filter(
				$productCollection->getItems(),
				array($this, '_filterProductWithConfigParent')
			)
		);
	}
	/**
	 * Get an array of skus for all simple products that should be linked to a
	 * configurable product included in the collection of products to be cleaned.
	 * @param  Varien_Data_Collection $productCollection
	 * @return array
	 */
	protected function _getAllUsedProductSkus(Varien_Data_Collection $productCollection)
	{
		$configSkus = array_map(
			function ($product) { return $product->getSku(); },
			$productCollection->getItemsByColumnValue('type_id', Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
		);
		return Mage::getModel('catalog/product')->getCollection()
			->addAttributeToFilter('style_id', array('in' => $configSkus))
			->addAttributeToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
			->getColumnValues('sku');
	}
	/**
	 * Get the unresolved product links for a product
	 * @param  Mage_Catalog_Model_Product $product
	 * @return array
	 */
	protected function _getAllUnresolvedProductLinks(Mage_Catalog_Model_Product $product)
	{
		$unresolvedLinks = unserialize($product->getUnresolvedProductLinks());
		return is_array($unresolvedLinks) ? $unresolvedLinks : array();
	}
	/**
	 * Add all related skus in the list of product links to the list of skus. List
	 * of SKUs represented as keys in the array to make
	 * @param  array $skus Current list of skus
	 * @param  array $productLinks Maps of product link data
	 * @return array Update list of skus
	 */
	protected function _reduceLinksToSkus($skus, $productLinks)
	{
		foreach ($productLinks as $link) {
			if (isset($link['link_to_unique_id'])) {
				$skus[] = $link['link_to_unique_id'];
			}
		}
		return $skus;
	}
	/**
	 * Filter used to get only products that are used by a configurable parent.
	 * Currently, this means only simple products with a style_id that differs
	 * from the sku.
	 * @param  Mage_Catalog_Model_Product $product
	 * @return bool True if product is expected to have a parent configurable product, false otherwise.
	 */
	protected function _filterProductWithConfigParent(Mage_Catalog_Model_Product $product)
	{
		return $product->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_SIMPLE &&
			$product->getStyleId() &&
			$product->getStyleId() !== $product->getSku();
	}

	/**
	 * Update any product links.
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return self
	 */
	public function cleanProduct(Mage_Catalog_Model_Product $product)
	{
		// resolved any hanging linked products
		$this->_resolveProductLinks($product);
		// update configurable relationships
		if ($product->getTypeId() === 'configurable') {
			$this->_addUsedProducts($product);
		} elseif ($product->getStyleId() && $product->getStyleId() !== $product->getSku()) {
			$this->_addToConfigurableProduct($product);
		}
		$this->markProductClean($product);
		return $this;
	}

	/**
	 * Resolved linked products - related, up sell, cross sell
	 *
	 * @param  Mage_Catalog_Model_Product $product Product to add links for
	 * @return self
	 */
	protected function _resolveProductLinks(Mage_Catalog_Model_Product $product)
	{
		$missingLinks = unserialize($product->getUnresolvedProductLinks());
		if (!empty($missingLinks)) {
			// keep track of links that are not resolved
			$unresolvedLinks = array();
			foreach (array_keys($this->_linkTypes) as $linkType) {
				$unresolvedLinks = array_merge(
					$unresolvedLinks,
					$this->_linkProducts(
						$product,
						array_filter($missingLinks, function ($el) use ($linkType) { return $linkType === $el['link_type']; }),
						$linkType
					)
				);
			}
			$product->setUnresolvedProductLinks(empty($unresolvedLinks) ? '' : serialize($unresolvedLinks));
		}
		return $this;
	}

	/**
	 * Update product links for the given type of links - up sell, related, cross sell
	 * @param  Mage_Catalog_Model_Product $product     Product to add links to
	 * @param  array                      $linkUpdates Product links data
	 * @param  string                     $linkType    Type of product links to create
	 * @return array                                   Any links that could not be added
	 */
	protected function _linkProducts(Mage_Catalog_Model_Product $product, $linkUpdates, $linkType)
	{
		$opFilter = function ($operation) {
			return function ($el) use ($operation) {
				return strtolower($el['operation_type']) === $operation;
			};
		};
		$skuMap = function ($link) {
			return $link['link_to_unique_id'];
		};

		// get all currently linked products of this link type
		$linkedProductsGetter = $this->_linkTypes[$linkType]['linked_products_getter_method'];
		$linkedProducts = $product->$linkedProductsGetter();

		// remove any links that are supposed to be getting deleted
		$deleteSkus = array_map($skuMap, array_filter($linkUpdates, $opFilter('delete')));
		$linkedProducts = array_filter(
			$linkedProducts,
			function ($prod) use ($deleteSkus) { return !in_array($prod->getSku(), $deleteSkus); }
		);

		$addSkus = array_map($skuMap, array_filter($linkUpdates, $opFilter('add')));

		// Go through the skus to add and look up the product id for each one.
		// If any are missing, add the product link for that sku to a list of links that
		// cannot be resolved yet.
		$missingLinks = array();
		$idsToAdd = array();
		foreach ($addSkus as $sku) {
			$linkProduct = $this->_products->getItemByColumnValue('sku', $sku);
			$id = $linkProduct ? $linkProduct->getId() : null;
			if ($id) {
				$idsToAdd[] = $id;
			} else {
				$missingLinks[] = $this->_buildProductLinkForSku($sku, $linkType, 'Add');
			}
		}

		// get a list of all products that should be linked
		$linkIds = array_filter(array_unique(array_merge(
			array_map(function ($prod) { return $prod->getId(); }, $linkedProducts),
			$idsToAdd
		)));

		$linkData = array();
		foreach ($linkIds as $id) {
			$linkData[$id] = array('position' => '');
		}

		// add the updated links to the product
		$product->setData($this->_linkTypes[$linkType]['data_attribute'], $linkData);
		// return links that were not resolved
		return $missingLinks;
	}

	/**
	 * Build out the product link map for a given sku.
	 * @param  string $sku       Sku the link should point to
	 * @param  string $type      Type of link to create
	 * @param  string $operation Type of operation, "Add" or "Delete"
	 * @return array             Map of product link data
	 */
	protected function _buildProductLinkForSku($sku, $type, $operation)
	{
		return array(
			'link_to_unique_id' => $sku,
			'link_type' => $type,
			'operation_type' => $operation
		);
	}

	/**
	 * Add products used to configure this product.
	 *
	 * @param Mage_Catalog_Model_Product $product Configurable parent product to add children to.
	 * @return self
	 */
	protected function _addUsedProducts(Mage_Catalog_Model_Product $product)
	{
		$addProductIds = array();
		foreach ($this->_products as $collectionProduct) {
			// used product must be simple and have a style id that matches the
			// configurable product's sku
			if (
				$collectionProduct->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_SIMPLE &&
				$collectionProduct->getStyleId() === $product->getSku()
			) {
				$addProductIds[] = $collectionProduct->getId();
			}
		}

		// merge the found products with any existing links, filtering out any duplicates
		$existingIds = $product->getTypeInstance()->getUsedProductIds();
		$usedProductIds = array_unique(array_merge($existingIds, $addProductIds));

		// only update used products when there are new products to add to the list
		// update used product when there are addProductIds that are not in existingIds.
		if (array_diff($addProductIds, $existingIds)) {
			// save the configurable product links
			Mage::getResourceModel('catalog/product_type_configurable')
				->saveProducts($product, $usedProductIds);
			$product
				// Need to set the added product ids in this magic data property so
				// future attempts at getting the used product ids will include the
				// updated data. Otherwise, if the product is encountered again in
				// the same cleaning process, the added links will have been lost and
				// it may attempt to re-add the same links, causing a SQL integrity
				// constraint violation.
				// @see Mage_Catalog_Model_Product_Type_Configurable::getUsedProductIds
				->setData(self::USED_PRODUCT_IDS_PROPERTY, $usedProductIds)
				->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
				->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
		} elseif (empty($usedProductIds)) {
			Mage::log(
				sprintf('[ %s ]: Expected to find products to use for configurable product %s but found none.', __CLASS__, $product->getSku()),
				Zend_Log::DEBUG
			);
		}
		return $this;
	}

	/**
	 * Simple product that needs to be added as a configuration to a configurable product.
	 *
	 * @param Mage_Catalog_Model_Product $product Simple product used to configure a configurable product.
	 * @return self
	 */
	protected function _addToConfigurableProduct(Mage_Catalog_Model_Product $product)
	{
		$configurableProduct = null;
		$styleId = $product->getStyleId();
		foreach ($this->_products as $collectionProduct) {
			if ($collectionProduct->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE &&
				$collectionProduct->getSku() === $styleId
			) {
				$configurableProduct = $collectionProduct;
				break;
			}
		}
		if ($configurableProduct) {
			$usedProductIds   = $configurableProduct->getTypeInstance()->getUsedProductIds();
			$usedProductIds[] = $product->getId();
			// save the configurable product links
			Mage::getResourceModel('catalog/product_type_configurable')
				->saveProducts($configurableProduct, array_unique($usedProductIds));
			$configurableProduct
				// Need to set the added product ids in this magic data property so
				// future attempts at getting the used product ids will include the
				// updated data. Otherwise, if the product is encountered again in
				// the same cleaning process, the added links will have been lost and
				// it may attempt to re-add the same links, causing a SQL integrity
				// constraint violation.
				// @see Mage_Catalog_Model_Product_Type_Configurable::getUsedProductIds
				->setData(self::USED_PRODUCT_IDS_PROPERTY, $usedProductIds)
				->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
				->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
		} else {
			Mage::log(sprintf(
				'[ %s ]: Expected to find configurable product with sku %s to add product with sku %s to but no such product found',
				__CLASS__, $product->getStyleId(), $product->getSku()
			), Zend_Log::DEBUG);
		}
		return $this;
	}

	/**
	 * Determine if the product has been sufficiently cleaned.
	 * A product with no unresolved product links can be marked clean.
	 *
	 * @param  Mage_Catalog_Model_Product $product Product to check
	 * @return self
	 */
	public function markProductClean(Mage_Catalog_Model_Product $product)
	{
		// lingering unresolved links will need to be checked again in a later pass, considered dirty
		$unresolvedLinks = unserialize($product->getUnresolvedProductLinks());
		$isClean = empty($unresolvedLinks);
		// update flag on product
		$product->setIsClean($isClean);
		Mage::helper('ebayenterprise_magelog')->logDebug(
			'[%s] Product "%s" marked%s clean.',
			array(__CLASS__, $product->getSku(), $isClean ? '' : ' not')
		);
		return $this;
	}
}
