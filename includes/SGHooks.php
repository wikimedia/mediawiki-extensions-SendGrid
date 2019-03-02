<?php

namespace MediaWiki\SendGrid;

use MailAddress;
use MWException;
use RequestContext;
use SendGrid;

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
 * @author Derick Alangi <alangiderick@gmail.com>
 * @license GPL-2.0-or-later
 *
 * @link https://www.mediawiki.org/wiki/Extension:SendGrid Documentation
 * @ingroup Extensions
*/

class SGHooks {

	/**
	 * Hook handler to send e-mails
	 *
	 * @param array $headers
	 * @param array $to
	 * @param MailAddress $from
	 * @param string $subject
	 * @param string $body
	 * @throws MWException
	 *
	 * @return string
	 */
	public static function onAlternateUserMailer(
		array $headers,
		array $to,
		MailAddress $from,
		$subject,
		$body
	) {
		$conf = RequestContext::getMain()->getConfig();

		// From "wgSendGridAPIKey" in LocalSettings.php when defined.
		$sendgridAPIKey = $conf->get( 'SendGridAPIKey' );

		if ( $sendgridAPIKey === '' || !isset( $sendgridAPIKey ) ) {
			throw new MWException(
				'Please update your LocalSettings.php with the correct SendGrid API key.'
			);
		}

		$sendgrid = new \SendGrid( $sendgridAPIKey );

		return self::sendEmail( $headers, $to, $from, $subject, $body, $sendgrid );
	}

	/**
	 * Send Email via the API
	 *
	 * @param array $headers
	 * @param array $to
	 * @param MailAddress $from
	 * @param string $subject
	 * @param string $body
	 * @param SendGrid|null $sendgrid
	 *
	 * @return string
	 */
	public static function sendEmail(
		array $headers,
		array $to,
		MailAddress $from,
		$subject,
		$body,
		\SendGrid $sendgrid = null
	) {
		// Get $to and $from email addresses from the
		// `array` and `MailAddress` object respectively
		$email = new \SendGrid\Mail\Mail();
		$email->addTo( $to[0]->address );
		$email->setFrom( $from->address );
		$email->setSubject( $subject );
		$email->addContent( 'text/plain', $body );

		try {
			$sendgrid->send( $email );
		} catch ( MWException $e ) {
			return $e->getMessage();
		}
	}
}
