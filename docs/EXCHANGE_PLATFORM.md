# Other Platform Notes

## Internationalization

Like a lot of other software in PHP, our extension does not use mb_string functions directly, so support for high-order unicode characters (such as Japanese and Arabic) can be achieved via installing the [Multibyte String Extension](http://www.php.net/manual/en/book.mbstring.php) and using its [Function Overloading Feature](http://www.php.net/manual/en/mbstring.overload.php).
