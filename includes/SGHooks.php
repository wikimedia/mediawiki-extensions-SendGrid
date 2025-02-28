<?php

namespace MediaWiki\Extension\SendGrid;

use Exception;
use FormatJson;
use MailAddress;
use MediaWiki\Hook\AlternateUserMailerHook;
use MWException;
use RequestContext;
use SendGrid;
use SendGrid\Mail\Mail;
use SendGrid\Mail\TypeException;
use Status;

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
class SGHooks implements AlternateUserMailerHook {

	/**
	 * @internal For use to get the SendGrid API key
	 * @var string
	 */
	public const SENDGRID_API_KEY = 'SendGridAPIKey';

	private const RESPONSE_CODE_OK = 202;

	/**
	 * Hook handler to send e-mails
	 *
	 * @param array $headers
	 * @param array $to
	 * @param MailAddress $from
	 * @param string $subject
	 * @param string $body
	 *
	 * @return bool|void|string
	 * @throws MWException|TypeException
	 */
	public function onAlternateUserMailer( $headers, $to, $from, $subject, $body ) {
		$conf = RequestContext::getMain()->getConfig();

		$sendgridAPIKey = $conf->get( self::SENDGRID_API_KEY );

		if ( $sendgridAPIKey === '' || $sendgridAPIKey === null ) {
			throw new MWException(
				'Please update your LocalSettings.php with the correct SendGrid API key.'
			);
		}

		$sendgrid = new SendGrid( $sendgridAPIKey );
		$response = $this->sendEmail( $to, $from, $subject, $body, $sendgrid );

		if ( $response !== null && $response->isOK() ) {
			// The email was successfully sent. Return
			// `false` to skip calling `mail()` which
			// is the regular way that core sends mails.
			return false;
		}

		if ( $response === null || !$response->isOK() ) {
			// Inform the user why the email was not sent.
			$error = FormatJson::decode( $response->getErrors()[0]['message'] );
			throw new MWException(
				$error->errors[0]->message ?? '$wgPasswordSender does not match Sender Identity on SendGrid'
			);
		}
	}

	/**
	 * Send Email via the API
	 *
	 * @param array $to
	 * @param MailAddress $from
	 * @param string $subject
	 * @param string $body
	 * @param SendGrid|null $sendgrid
	 *
	 * @return Status|null
	 * @throws MWException|TypeException
	 */
	public function sendEmail(
		array $to,
		MailAddress $from,
		$subject,
		$body,
		?SendGrid $sendgrid = null
	): ?Status {
		if ( $sendgrid === null ) {
			return null;
		}

		// Get $to and $from email addresses from the
		// `array` and `MailAddress` object respectively
		$email = new Mail();
		$email->addTo( $to[0]->address );

		if ( filter_var( $from->address, FILTER_VALIDATE_EMAIL ) ) {
			try {
				$email->setFrom( $from->address );
			} catch ( TypeException $e ) {
				return Status::newGood( $e->getMessage() );
			}
		} else {
			throw new MWException(
				'Invalid "from" email, check the $wgPasswordSender configs'
			);
		}

		$email->setSubject( $subject );
		$email->addContent( 'text/plain', $body );

		try {
			$response = $sendgrid->send( $email );
			if ( $response->statusCode() === self::RESPONSE_CODE_OK ) {
				return Status::newGood();
			}

			return Status::newFatal( 'sendgrid-email-not-sent' );
		} catch ( Exception $e ) {
			return Status::newGood( $e->getMessage() );
		}
	}

}
