<?php
/**
 * Hooks for SendGrid extension for MediaWiki
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @author Alangi Derick <alangiderick@gmail.com>
 * @license GPL-2.0-or-later
 *
 * @link https://www.mediawiki.org/wiki/Extension:SendGrid Documentation
 * @ingroup Extensions
*/

class SendGridHooks {

	/**
	 * Send a mail using SendGrid API
	 *
	 * @param array $headers
	 * @param array $to
	 * @param MailAddress $from
	 * @param string $subject
	 * @param string $body
	 * @param SendGrid $sendgrid
	 * @return bool
	 * @throws Exception
	 */
	public static function onAlternateUserMailer(
		array $headers,
		array $to,
		MailAddress $from,
		$subject,
		$body,
		\SendGrid $sendgrid = null
	) {
		if ( $sendgrid === null ) {
			$conf = RequestContext::getMain()->getConfig();

			// Value gotten from "wgSendGridAPIKey" variable from LocalSettings.php
			$sendgridAPIKey = $conf->get( 'SendGridAPIKey' );

			if ( $sendgridAPIKey === "" ) {
				throw new MWException(
					'Please update your LocalSettings.php with the correct SendGrid API key.'
				);
			}

			$sendgrid = new \SendGrid( $sendgridAPIKey );
		}

		// Get $to and $from email addresses from the array and MailAddress object respectively
		$from = new SendGrid\Email( null, $from->address );
		$to = new SendGrid\Email( null, $to[0]->address );
		$body = new SendGrid\Content( "text/plain", $body );
		$mail = new SendGrid\Mail( $from, $subject, $to, $body );

		try {
			$sendgrid->client->mail()->send()->post( $mail );
		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		return false;
	}

	/**
	 * Handler for UnitTestsList hook.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 * @param array &$files Array of unit test files
	 * @return bool true in all cases
	 */
	public static function onUnitTestsList( &$files ) {
		// @codeCoverageIgnoreStart
		$directoryIterator = new RecursiveDirectoryIterator( __DIR__ . '/tests/' );

		/**
		 * @var SplFileInfo $fileInfo
		 */
		$ourFiles = [];
		foreach ( new RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( substr( $fileInfo->getFilename(), -8 ) === 'Test.php' ) {
				$ourFiles[] = $fileInfo->getPathname();
			}
		}

		$files = array_merge( $files, $ourFiles );

		return true;
		// @codeCoverageIgnoreEnd
	}

}
