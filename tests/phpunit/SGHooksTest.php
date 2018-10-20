<?php

namespace MediaWiki\SendGrid;

use HashConfig;
use MailAddress;
use MediaWikiTestCase;
use MWException;
use MultiConfig;
use RequestContext;

/**
 * Test for hooks code.
 *
 * @file
 * @author Nikita Volobuev
 * @license GPL-2.0-or-later
 *
 */

class SGHooksTest extends MediaWikiTestCase {

	/**
	 * Test that onAlternateUserMailer throws Exception if api key is missing.
	 * @covers \MediaWiki\SendGrid\SGHooks::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailerNoApiKey() {
		$this->setExpectedException(
			MWException::class,
			'Please update your LocalSettings.php with the correct SendGrid API key.'
		);

		RequestContext::getMain()->setConfig( new MultiConfig( [
			new HashConfig( [
				'SendGridAPIKey' => '',
			] ),
		] ) );

		SGHooks::onAlternateUserMailer(
			[ 'SomeHeader' => 'SomeValue' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);
	}

	/**
	 * Test sending email using the onAlternateUserMailer hook.
	 * @covers \MediaWiki\SendGrid\SGHooks::onAlternateUserMailer
	 */
	public function testSendEmail() {
		$mock = $this->getMockBuilder( 'SendGrid' )
			->setMethods( [ 'send' ] )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( $this->once() )
			->method( 'send' )
			->with( $this->callback( function ( $email ) {
				$this->assertSame(
					'sender@example.com',
					$email->getFrom()->getEmail()
				);
				$this->assertCount( 1, $email->getPersonalizations() );
				$this->assertCount(
					1,
					$email->getPersonalizations()[0]->getTos()
				);
				$this->assertSame(
					'receiver@example.com',
					$email->getPersonalizations()[0]->getTos()[0]->getEmail()
				);
				$this->assertSame(
					'Some subject',
					$email->getGlobalSubject()->getSubject()
				);
				$this->assertCount( 1, $email->getContents() );
				$this->assertSame(
					'text/plain',
					$email->getContents()[0]->getType()
				);
				$this->assertSame(
					'Email body',
					$email->getContents()[0]->getValue()
				);
				return true;
			} ) );

		$result = SGHooks::sendEmail(
			[ 'SomeHeader' => 'SomeValue' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body',
			$mock
		);

		$this->assertSame( false, $result );
	}

}
