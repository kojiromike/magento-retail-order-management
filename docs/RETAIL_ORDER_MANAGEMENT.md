# Other Platform Notes

## Internationalization

Like a lot of other software in PHP, our extension does not use mb_string functions directly, so support for high-order unicode characters (such as Japanese and Arabic) can be achieved via installing the [Multibyte String Extension](http://www.php.net/manual/en/book.mbstring.php) and using its [Function Overloading Feature](http://www.php.net/manual/en/mbstring.overload.php).

## Gift Options

The eBay Enterprise Retail Order Management extension includes support for gift messages that closely matches the functionality available in Magento. The availability of gift messages for items and orders is dependent upon specific agreements between the merchant and eBay Enterprise.

When available, gift messages may be added for the entire order as well as for individual items. The "Add Printed Card" option is used to determine if the gift messages are to be included on the pack slip or as separate cards. The extension currently does not include support for adding additional charges for including a printed card.

## Gift Wrapping

The eBay Enterprise Retail Order Management extension also includes limited support for gift wrapping. The availability of gift wrapping is dependent upon specific agreements between the merchant and eBay Enterprise.

When available, gift wrapping may be included for the entire order as well as for individual items. Gift wrapping may be added with or without including a gift message. If the customer selects to include a printed card, however, any items that include a gift message will also be gift wrapped. The extension currently does not include support for adding additional charges for gift wrapping or selecting different styles of gift wrapping. Additional rules regarding gift wrapping may be determined as part of the merchant agreement with eBay Enterprise.

This module does not change how Magento determines which gift options are available and which products are eligible for gift messages and gift wrapping.

When making gift options available to customers, it is advised that the user interface be customized to make only the options supported by this extension and agreements with eBay Enterprise available.

## Order Creation

The eBay Enterprise Retail Order Management Extension will save and resubmit orders that fail due to temporary issues or downtime with the Retail Order Management API.

- Orders will be created in a state of _new_ with status of _unsubmitted_.
- When an order is successfully created using the Retail Order Management API, the order status will be updated to _pending_.
- A cron job will regularly look for orders with an _unsubmitted_ status and resubmit those orders.

## Order History

When displaying orders from ROMS to the customer:

- Order data will be retrieved in realtime to be displayed to the customer.
- Orders must be in Magento and the Retail Order Management System.
- Only order information from the Retail Order Management System will be displayed.
- Invoices will not be shown in the order detail page.
- The "Recent Orders" and "My Orders" sections of the customer account pages display the "Ship To" name for the order in Magento, not the Retail Order Management System. The templates being used to display this data may be modified to prevent this data from displaying.

## Order Event Status

Order event status will synch orders in Retail Order Management System with configured order status in Magento store orders.

Order status in Magento stores can be configured this way:

- Logged in the administrative backend portal.
- Go under the 'system -> Configuration' menu.
- Click the 'Retail Order Management' tab.
- Under 'Order Management' section there will be a number of order statuses that can be configured in Magento per Retail Order Management System order status event.
