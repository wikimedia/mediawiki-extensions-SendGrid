# SendGrid MediaWiki Extension

SendGrid is an extension that allows MediaWiki to send emails through SendGrid API service.
* Author: Derick N. Alangi
* Current release: 2.0
* MediaWiki: 1.25+
* PHP: 5.6+
* License: GNU GPL-2+

## Installation

* Make sure you have a MediaWiki environment setup properly.
* [Download](https://www.mediawiki.org/wiki/Special:ExtensionDistributor/SendGrid) the **SendGrid** extension.
* Place the extension in ``extensions/`` folder of your MediaWiki project.
* Add the following code at the bottom of your [LocalSettings.php](https://www.mediawiki.org/wiki/Manual:LocalSettings.php):
```php
 wfLoadExtension( 'SendGrid' );
```

For MediaWiki version 1.24 or earlier:
* Add the following code (instead) at the bottom of your [LocalSettings.php](https://www.mediawiki.org/wiki/Manual:LocalSettings.php):
```php
 require_once "$IP/extensions/SendGrid/SendGrid.php";
```

 **Note:** You will have to run composer update in `extensions/SendGrid/` folder so that composer can pick up the required SendGrid dependencies for the extension to run smoothly.

## Contribute

Please refer to [https://phabricator.wikimedia.org/tag/mediawiki-extensions-sendgrid/](https://phabricator.wikimedia.org/tag/mediawiki-extensions-sendgrid/) for tasks on which you can contribute to.

## More information

Go to the extension's page on [MediaWiki](https://www.mediawiki.org/wiki/Extension:SendGrid) for further read.


## License

SendGrid is licensed under the terms of the GNU General Public License 2.0 or later.
