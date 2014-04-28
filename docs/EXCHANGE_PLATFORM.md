# Other Platform Notes

## Internationalization

Like a lot of other software in PHP, our extension does not use mb_string functions directly, so support for high-order unicode characters (such as Japanese and Arabic) can be achieved via installing the [Multibyte String Extension](http://www.php.net/manual/en/book.mbstring.php) and using its [Function Overloading Feature](http://www.php.net/manual/en/mbstring.overload.php).

## Gift Options

The eBay Enterprise Exchange extension includes support for gift messages that closely matches the functionality available in Magento. All gift message fields available in Magento are supported by the extension. Support is also included for gift messages for the entire order as well as on individual items. The "Add Printed Card" option is used to determine if the gift messages are to be included on the pack slip or as a separate gift card. The extensions currently does not include support for adding additional charges for including a printed card.

The eBay Enterprise Exchange extension currently does not include support for gift wrapping.
