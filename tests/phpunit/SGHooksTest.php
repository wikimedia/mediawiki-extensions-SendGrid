<?php

namespace MediaWiki\Extension\SendGrid;

use MailAddress;
use MediaWikiIntegrationTestCase;
use MWException;
use SendGrid\Response;
use Status;

/**
 * Test for SGHooks code.
 *
 * @author Nikita Volobuev
 * @author Derick Alangi
 * @coversDefaultClass \MediaWiki\Extension\SendGrid\SGHooks
 */
class SGHooksTest extends MediaWikiIntegrationTestCase {
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
	public function testOnAlternateUserMailerNoApiKey(): void {
		$this->setConfig( '' );

		$this->expectException( MWException::class );
		$this->expectExceptionMessage(
			'Please update your LocalSettings.php with the correct SendGrid API key.' );

		SGHooks::onAlternateUserMailer(
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
	public function testOnAlternateUserMailerWithInvalidApiKey(): void {
		$this->setConfig( 'TestAPIKeyString' );

		$actual = SGHooks::onAlternateUserMailer(
			[ 'SomeHeader' => 'SomeValue' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);

		$this->assertSame(
			wfMessage( 'sendgrid-email-not-sent' )->plain(),
			$actual
		);
	}

	/**
	 * Test sending email sendEmail() method.
	 *
	 * @covers ::sendEmail
	 */
	public function testOnAlternateUserMailerWithMockedSendGridObject(): void {
		$mockSendGrid = $this->getMockBuilder( 'SendGrid' )
			->onlyMethods( [ 'send' ] )
			->disableOriginalConstructor()
			->getMock();

		$mockResponse = $this->createMock( Response::class );
		$mockResponse->method( 'statusCode' )->willReturn( 200 );

		$mockSendGrid->expects( $this->once() )
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
			} ) )->willReturn( $mockResponse );

		$actual = SGHooks::sendEmail(
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body',
			$mockSendGrid
		);

		$this->assertInstanceOf( Status::class, $actual );
		$this->assertTrue( $actual->isOK() );
	}

}
