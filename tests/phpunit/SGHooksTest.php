<?php

namespace MediaWiki\SendGrid;

use MailAddress;
use MediaWikiTestCase;
use MWException;

/**
 * Test for SGHooks code.
 *
 * @author Nikita Volobuev
 * @author Derick N. Alangi
 * @coversDefaultClass \MediaWiki\SendGrid\SGHooks
 */
class SGHooksTest extends MediaWikiTestCase {
	/**
	 * @param string $apiKey SendGrid API key
	 */
	public function setConfig( $apiKey ) {
		$this->setMwGlobals( 'wgSendGridAPIKey', $apiKey );
	}

	/**
	 * Test that onAlternateUserMailer throws Exception if api key is missing.
	 *
	 * @covers ::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailerNoApiKey() {
		$this->setConfig( '' );

		$this->setExpectedException(
			MWException::class, 'Please update your LocalSettings.php with the correct SendGrid API key.'
		);

		$actual = SGHooks::onAlternateUserMailer(
			[ 'SomeHeader' => 'SomeValue' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);
	}

	/**
	 * @covers ::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailerWithApiKey() {
		$this->setConfig( 'TestAPIKeyString' );

		$actual = SGHooks::onAlternateUserMailer(
			[ 'SomeHeader' => 'SomeValue' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);

		$this->assertFalse( $actual );
	}

	/**
	 * Test sending email sendEmail() method.
	 *
	 * @covers ::sendEmail
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

		$actual = SGHooks::sendEmail(
			[ 'SomeHeader' => 'SomeValue' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body',
			$mock
		);

		$this->assertSame( false, $actual );
	}
}
