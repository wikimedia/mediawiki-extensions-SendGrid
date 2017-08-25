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
 * @license GPL-2.0+
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
	 * @return bool
	 * @throws Exception
	 */
	public static function onAlternateUserMailer(
		array $headers,
		array $to,
		MailAddress $from,
		$subject,
		$body
	) {
		$conf = RequestContext::getMain()->getConfig();

		// Value gotten from "wgSendGridAPIKey" variable from LocalSettings.php
		$sendgridAPIKey = $conf->get( 'SendGridAPIKey' );

		if ( $sendgridAPIKey == "" ) {
			throw new MWException(
				'Please update your LocalSettings.php with the correct SendGrid API key.'
			);
		}

		// Get $to and $from email addresses from the array and MailAddress object respectively
		$from = new SendGrid\Email( null, $from->address );
		$to = new SendGrid\Email( null, $to[0]->address );
		$body = new SendGrid\Content( "text/plain", $body );
		$mail = new SendGrid\Mail( $from, $subject, $to, $body );
		$sendgrid = new \SendGrid( $sendgridAPIKey );

		try {
			$sendgrid->client->mail()->send()->post( $mail );
		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		return false;
	}

}
