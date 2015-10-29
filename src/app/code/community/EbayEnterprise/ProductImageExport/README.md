![ebay logo](/docs/static/logo-vert.png)

**Magento Retail Order Management Extension**
# Product Image Export

The intended audience for this guide is Magento merchants, business users and system integrators.

## Introduction

Image Feed Files will be generated per store configured in Magento in the `/ExchangePlatform/Feed/out-box/` folder having the following file pattern `{client_id}_{store_id}_{feed_type}_{time_stamp}_{current_store_id}.xml`

### File Pattern Map:

| Pattern            | Value                                                      |
|:-------------------|:-----------------------------------------------------------|
| `client_id`        | `eb2ccore/general/client_id`                               |
| `store_id`         | `eb2ccore/general/store_id`                                |
| `feed_type`        | `ebayenterprise_catalog/image_master_feed/filename_format` |
| `time_stamp`       | `Mage::getModel('core/date')->gmtDate('YmdHis', time())`   |
| `current_store_id` | `Mage::getModel('core/store')->getId()`                    |

### Sample Image Feed Export

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

### Product Image Export Map

| XML Nodes/Attributes | Magento Product Image Data | Example |
|:---------------------|:---------------------------|:--------|
| `/ItemImages[@imageDomain]` | Store base URL | `parse_url(Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB))['host']` |
| `/ItemImages[@clientId]` | Store client id  | `eb2ccore/etc/config.xml (eb2ccore/general/client_id)` |
| `/ItemImages[@timestamp]` | Current date time | `(xsd:date Mage::getModel('core/date')->date('c'))` |
| `/ItemImages/MessageHeader` | Export message header | `Mage::helper('ebayenterprise_catalog')->generateMessageHeader($cfg->imageFeedEventType)` |
| `/ItemImages/Item[@id]` | Product SKU | `$product->getSku()` |
| `/ItemImages/Item/Images/Image[@imageview]` | Product image view types | `image_media type (image, small_image, thumbnail)` |
| `/ItemImages/Item/Images/Image[@imagename]` | Product image label | `($product->getMediaGalleryImages()[Varien_Data_Collection][Varien_Object]->getLabel())` |
| `/ItemImages/Item/Images/Image[@imageurl]` | Product image url | `($product->getMediaGalleryImages()[Varien_Data_Collection][Varien_Object]->getUrl())` |
| `/ItemImages/Item/Images/Image[@imagewidth`] | Product image width | `getimagesize(path|url) (getimagesize($product->getMediaGalleryImages()[Varien_Data_Collection][Varien_Object]->getPath()|getUrl()))` |
| `/ItemImages/Item/Images/Image[@imageheight]` | Product image height | `getimagesize(path|url) (getimagesize($product->getMediaGalleryImages()[Varien_Data_Collection][Varien_Object]->getPath()|getUrl()))` |

*NOTE: if the products in Magento have no image data then the image export will not generate any feed files.*

- - -
Copyright © 2014 eBay Enterprise, Inc.

