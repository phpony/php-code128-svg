# php-code128-svg
Simple single file class to generate Code 128 barcode as SVG image that works with all PHP versions from 5.3 up to current 8.x

*The codebase is largely from the [php-barcode-generator]([https://github.com/tecnickcom/TCPDF](https://github.com/brewerwall/php-barcode-generator) downported to support php 5.3 and tinyfied to single file with single class approach only with Code 128 barcode and SVG format in mind.

## Usage
Require single class file code128.php and create a new barcode generator:

```php
require_once("code128.php");
$generator = new code128();
// Generate our code
$generated = $generator->generate('012345678');
// Generates the same code with style updates
$generated = $generator->generate('012345678', '', 4, 50, '#FFCC33');
```

The `$generator->generate()` method accepts the following parameters:
- `$code` Barcode value we need to generate.
- `$codeType` barcode type: A, B, C or empty for automatic switch (AUTO mode)
- `$widthFactor` (default: 2) Width is based on the length of the data, with this factor you can make the barcode bars wider than default
- `$totalHeight` (default: 30) The total height of the barcode
- `$color` (default: #000000) Hex code of the foreground color

- ## Examples
Embedded SVG image in HTML:

```php
$generator = new code128();
echo '<img src="data:image/svg+xml;base64,' . base64_encode($generator->generate('012345678')) . '">';
```
