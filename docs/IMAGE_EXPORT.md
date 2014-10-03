# Image Export Feeds

1.	Go to **System > Scheduler > Schedule Configuration** in the Admin.
2.	Click the checkbox next to ebayenterprise_catalog_image_export_feed.
3.	Select run now from the actions drop down.
4.	Click submit button.

Image Feed Files will be generated per store configured in Magento in the /ExchangePlatform/Feed/out-box/ folder having the following file pattern ({client_id}_{store_id}_{feed_type}_{time_stamp}_{current_store_id}.xml)

# File Pattern Map:
| Pattern               | Value                                                    |
| --------------------- | -------------------------------------------------------- |
| {client_id}           | eb2ccore/general/client_id                               |
| {store_id}            | eb2ccore/general/store_id                                |
| {feed_type}           | ebayenterprise_catalog/image_master_feed/filename_format            |
| {time_stamp}          | Mage::getModel('core/date')->gmtDate('YmdHis', time())   |
| {current_store_id}    | Mage::getModel('core/store')->getId()                    |

# Sample Image Feed Export
```xml
<ItemImages imageDomain="example.com" clientId="TST" timestamp="2014-04-07T11:42:27+00:00">
	<MessageHeader>...</MessageHeader>
	<Item id="54-E491B455-Ite">
		<Images>
			<Image imageview="image" imagename="This is a test label" imageurl="http://example.com/media/catalog/product/e/a/earth.png" imagewidth="700" imageheight="700"/>
			<Image imageview="small_image" imagename="This is a test label" imageurl="http://example.com/media/catalog/product/e/a/earth.png" imagewidth="700" imageheight="700"/>
			<Image imageview="thumbnail" imagename="This is a test label" imageurl="http://example.com/media/catalog/product/e/a/earth.png" imagewidth="700" imageheight="700"/>
		</Images>
	</Item>
</ItemImages>
```

# Mapping of Image Feed xml nodes/xml node attributes to Magento Product Image Data:
| XML Nodes/Attributes                                 | Magento Product Image Data          | Example                                                                                                                                 |
| ---------------------------------------------------- | ----------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| /ItemImages[@imageDomain]                            | the host of the install magento app | parse_url(Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB))['host']                                     |
| /ItemImages[@clientId]                               | the client id configure in magento  | eb2ccore/etc/config.xml (eb2ccore/general/client_id)                                                                                    |
| /ItemImages[@timestamp]                              | the current date time               | (xsd:date Mage::getModel('core/date')->date('c'))                                                                                       |
| /ItemImages/MessageHeader                            | the export message header           | Mage::helper('ebayenterprise_catalog')->generateMessageHeader($cfg->imageFeedEventType)                                                            |
| /ItemImages/Item[@id]                                | the product sku                     | $product->getSku() (not currently being de-normalized???)                                                                               |
| /ItemImages/Item[@id]/Images/Image[@imageview]       | the product image view types        | image_media type (image, small_image, thumbnail)                                                                                        |
| /ItemImages/Item[@id]/Images/Image[@imagename]       | the product image label             | ($product->getMediaGalleryImages()[Varien_Data_Collection][Varien_Object]->getLabel())                                                  |
| /ItemImages/Item[@id]/Images/Image[@imageurl]        | the product image url               | ($product->getMediaGalleryImages()[Varien_Data_Collection][Varien_Object]->getUrl())                                                    |
| /ItemImages/Item[@id]/Images/Image[@imagewidth       | the image width                     | getimagesize(path|url) (getimagesize($product->getMediaGalleryImages()[Varien_Data_Collection][Varien_Object]->getPath()|getUrl()))     |
| /ItemImages/Item[@id]/Images/Image[@imageheight]     | the image height                    | getimagesize(path|url) (getimagesize($product->getMediaGalleryImages()[Varien_Data_Collection][Varien_Object]->getPath()|getUrl()))     |


*NOTE: if the products in Magento have no image data then the image export will not generate any feed files.*

