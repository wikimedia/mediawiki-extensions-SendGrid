<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'SendGrid' );

	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['SendGrid'] = __DIR__ . '/i18n';
	/* wfWarn(
		'Deprecated PHP entry point used for SendGrid extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return;
} else {
	die( 'This version of the SendGrid extension requires MediaWiki 1.25+' );
}
