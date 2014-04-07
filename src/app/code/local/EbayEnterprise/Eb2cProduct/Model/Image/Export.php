<?php
/**
 * Export images "the best we can"
 */
class EbayEnterprise_Eb2cProduct_Model_Image_Export extends Varien_Object
{
	const XML_TEMPLATE = '<%1$s imageDomain="%2$s" clientId="%3$s" timestamp="%4$s">%5$s</%1$s>';
	const ROOT_NODE = 'ItemImages';
	const ID_PLACE_HOLDER = '{current_store_id}';
	const FRONTEND_INPUT = 'media_image';
	const FILTER_OUT_VALUE = 'no_selection';

	/**
	 * Builds the Image Export DOM - creates the export file, validates the schema, and then sends it.
	 * @return Number of Stores examined for images
	 */
	public function process()
	{
		return array_reduce(
			array_keys(Mage::helper('eb2cproduct')->getStores()),
			array($this, '_buildExport')
		);
	}
	/**
	 * build image feed per store
	 * @param int $processed
	 * @param int $storeId
	 * @return int
	 */
	protected function _buildExport($processed=0, $storeId)
	{
		$processed += 1;
		$imageData = $this->_getImageData($storeId);
		if (!empty($imageData)) {
			$helper = Mage::helper('eb2cproduct');
			$helper->setCurrentStore($storeId);
			$this->_buildItemImages($this->_loadDom($storeId), $storeId, $imageData);
			$helper->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
		}
		return $processed;
	}
	/**
	 * load the preliminary data into EbayEnterprise_Dom_Document object and then return
	 * a EbayEnterprise_Dom_Document object
	 * @param int $storeId
	 * @return EbayEnterprise_Dom_Document
	 */
	protected function _loadDom($storeId)
	{
		$pHelper = Mage::helper('eb2cproduct');
		$cHelper = Mage::helper('eb2ccore');
		$cfg = $pHelper->getConfigModel();
		$doc = $cHelper->getNewDomDocument();
		$doc->formatOutput = true;
		$doc->loadXml(sprintf(
			self::XML_TEMPLATE,
			self::ROOT_NODE,
			$this->_getCurrentHostName($storeId),
			$cfg->clientId,
			Mage::getModel('core/date')->date('c'),
			$pHelper->generateMessageHeader($cfg->imageFeedEventType),
			$cHelper->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
		));

		return $doc;
	}
	/**
	 * get the current host name
	 * @param int $storeId
	 * @return string
	 */
	protected function _getCurrentHostName($storeId)
	{
		$domainParts = parse_url(Mage::helper('eb2cproduct')->getStoreUrl($storeId));
		return $domainParts['host'];
	}
	/**
	 * Create a file from the dom, and return its full path.
	 * 'protected' so we can test around it.
	 * @param EbayEnterprise_Dom_Document dom
	 * @param storeId
	 * @return string File name
	 */
	protected function _createFileFromDom(EbayEnterprise_Dom_Document $dom, $storeId)
	{
		$filename = $this->_generateFilePath($storeId);
		$dom->save($filename);
		return $filename;
	}
	/**
	 * generate image feed file name
	 * @param string $storeId
	 * @return string
	 */
	protected function _generateFilePath($storeId)
	{
		$helper = Mage::helper('eb2cproduct');
		$cfg = $helper->getConfigModel();
		$coreFeed = Mage::getModel('eb2ccore/feed', array('feed_config' => $cfg->imageFeed));
		return $coreFeed->getLocalDirectory() . DS . str_replace(
			self::ID_PLACE_HOLDER,
			$storeId,
			$helper->generateFileName($cfg->imageFeedEventType, $cfg->imageExportFilenameFormat)
		);
	}
	/**
	 * Build an item's worth of images
	 * @param EbayEnterprise_Dom_Document node into which itemImages are placed
	 * @param int $storeId
	 * @param array $imageData
	 * @return int number of images for all products in this store
	 */
	protected function _buildItemImages(EbayEnterprise_Dom_Document $doc, $storeId, array $imageData)
	{
		$this->_buildXmlNodes($doc, $imageData)
			->_validateXml($doc)
			->_createFileFromDom($doc, $storeId);

		return count($imageData);
	}
	/**
	 * validate the dom xml
	 * @param EbayEnterprise_Dom_Document $doc
	 * @return self
	 */
	protected function _validateXml(EbayEnterprise_Dom_Document $doc)
	{
		Mage::getModel('eb2ccore/api')->schemaValidate(
			$doc, Mage::helper('eb2cproduct')->getConfigModel()->imageExportXsd
		);

		return $this;
	}
	/**
	 * build the xml node base on the image data given and the DOMDocument object given
	 * @param EbayEnterprise_Dom_Document
	 * @param array $imageData
	 * @return self
	 */
	protected function _buildXmlNodes(EbayEnterprise_Dom_Document $doc, array $imageData)
	{
		$itemImages = Mage::helper('eb2ccore')->getDomElement($doc);
		foreach ($imageData as $data){
			$this->_buildImagesNodes(
				$itemImages->createChild('Item', null, array('id' => $data['id'])),
				$data['image_data']
			);
		}
		return $this;
	}
	/**
	 * build images/image nodes
	 * @param EbayEnterprise_Dom_Element $item
	 * @param array $imageData
	 * @return self
	 */
	protected function _buildImagesNodes(EbayEnterprise_Dom_Element $item, array $imageData)
	{
		$images = $item->createChild('Images');
		foreach ($imageData as $image) {
			$images->createChild('Image', null, array(
				'imageview' => $image['view'],
				'imagename' => $image['name'],
				'imageurl' => $image['url'],
				'imagewidth' => $image['width'],
				'imageheight' => $image['height']
			));
		}
		return $this;
	}
	/**
	 * get product image data
	 * @param int $storeId
	 * @return array
	 */
	protected function _getImageData($storeId)
	{
		$data = array();
		foreach ($this->_getProductCollection($storeId) as $product) {
			$data[] = $this->_extractImageData($product->load($product->getId()));
		}
		return array_filter($data);
	}
	/**
	 * extracting image data from a given Mage_Catalog_Model_Product object
	 * @param Mage_Catalog_Model_Product $product
	 * @return array | null
	 */
	protected function _extractImageData(Mage_Catalog_Model_Product $product)
	{
		$media = $product->getMediaGalleryImages();

		return ($media && $media->count())?
			array(
				'id' => $product->getSku(),
				'image_data' => $this->_getMediaData($media, $product)
			) : null;
	}
	/**
	 * extracting image data from a given Mage_Catalog_Model_Product object
	 * @param Varien_Data_Collection $media
	 * @param Mage_Catalog_Model_Product $product
	 * @return array
	 */
	protected function _getMediaData(Varien_Data_Collection $media, Mage_Catalog_Model_Product $product)
	{
		$mData = array();
		$imageViews = $this->_filterImageViews($this->_getMageImageViewMap($product));
		foreach ($media as $mageImage) {
			$dimension = $this->_getImageDimension($mageImage);
			foreach ($imageViews as $view) {
				$mData[] = array(
					'view' => $view,
					'name' => $mageImage->getLabel(),
					'url' => $mageImage->getUrl(),
					'width' => $dimension->getWidth(),
					'height' => $dimension->getHeight()
				);
			}
		}
		return $mData;
	}
	/**
	 * get image demensions
	 * @param Varien_Object $image
	 * @return Varien_Object
	 */
	protected function _getImageDimension(Varien_Object $mageImage)
	{
		list($w, $h) = getimagesize(
			(file_exists($mageImage->getPath())) ? $mageImage->getPath() : $mageImage->getUrl()
		);
		return new Varien_Object(array('width' => $w, 'height' => $h));
	}
	/**
	 * get a collection of product per store
	 * @param int $storeId
	 * @return Mage_Catalog_Model_Resource_Product_Collection
	 */
	protected function _getProductCollection($storeId)
	{
		return Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect(array('*'))
			->addStoreFilter($storeId)
			->load();
	}
	/**
	 * Searchs for all media_image type attributes for this product's attribute set, and creates a hash matching
	 * the attribute code to its value, which is a media path. The attribute code is used as the
	 * image 'view', and we use array_search to match based on media path.
	 * @return array of view_names => image_paths
	 */
	protected function _getMageImageViewMap($mageProduct)
	{
		$attributes = $mageProduct->getAttributes();
		return array_reduce(array_keys($attributes), function($result=array(), $key) use($attributes, $mageProduct) {
			if (!strcmp($attributes[$key]->getFrontendInput(), EbayEnterprise_Eb2cProduct_Model_Image_Export::FRONTEND_INPUT)) {
				$result[$key] = $mageProduct->getData($key);
			}
			return $result;
		});
	}
	/**
	 * given an array of image view filter out any key with value self::FILTER_OUT_VALUE
	 * return any key without self::FILTER_OUT_VALUE value if empty return an array with one empty string element
	 * @param array $imageViews
	 * @return array
	 */
	protected function _filterImageViews(array $imageViews)
	{
		$views = array_filter(array_keys($imageViews), function($key) use ($imageViews) {
			return ($imageViews[$key] !== EbayEnterprise_Eb2cProduct_Model_Image_Export::FILTER_OUT_VALUE);
		});

		return !empty($views)? $views : array('');
	}
}
