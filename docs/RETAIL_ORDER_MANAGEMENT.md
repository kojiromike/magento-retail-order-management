# Other Platform Notes

## Internationalization

Like a lot of other software in PHP, our extension does not use mb_string functions directly, so support for high-order unicode characters (such as Japanese and Arabic) can be achieved via installing the [Multibyte String Extension](http://www.php.net/manual/en/book.mbstring.php) and using its [Function Overloading Feature](http://www.php.net/manual/en/mbstring.overload.php).

## Order History

When displaying orders from ROMS to the customer:

- Order data will be retrieved in realtime to be displayed to the customer.
- Orders must be in Magento and the Retail Order Management System.
- Only order information from the Retail Order Management System will be displayed.
- Invoices will not be shown in the order detail page.
- The "Recent Orders" and "My Orders" sections of the customer account pages display the "Ship To" name for the order in Magento, not the Retail Order Management System. The templates being used to display this data may be modified to prevent this data from displaying.
