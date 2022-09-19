<?php

namespace MediaWiki\Extension\SendGrid\Tests;

use Exception;
use MailAddress;
use MediaWiki\Extension\SendGrid\SGHooks;
use MediaWikiIntegrationTestCase;
use MWException;
use SendGrid;
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
	private function setSendGridConfig( string $apiKey ) {
		$this->overrideConfigValue( SGHooks::SENDGRID_API_KEY, $apiKey );
	}

	/**
	 * Test that onAlternateUserMailer throws Exception if api key is missing.
	 *
	 * @covers ::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailerNoApiKey(): void {
		$this->setSendGridConfig( '' );

		$this->expectException( MWException::class );
		$this->expectExceptionMessage(
			'Please update your LocalSettings.php with the correct SendGrid API key.' );

		( new SGHooks() )->onAlternateUserMailer(
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
		$this->setSendGridConfig( 'TestAPIKeyString' );

		$this->expectException( MWException::class );

		( new SGHooks() )->onAlternateUserMailer(
			[ 'SomeHeader' => 'SomeValue' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);
	}

	/**
	 * Test sending email sendEmail() method.
	 *
	 * @covers ::sendEmail
	 */
	public function testOnAlternateUserMailerWithMockedSendGridObject(): void {
		$mockSendGrid = $this->createNoOpMock( SendGrid::class, [ 'send' ] );

		$mockResponse = $this->createMock( Response::class );
		$mockResponse->method( 'statusCode' )->willReturn( 202 );

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

		$actual = ( new SGHooks() )->sendEmail(
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body',
			$mockSendGrid
		);

		$this->assertInstanceOf( Status::class, $actual );
		$this->assertTrue( $actual->isOK() );
	}

	/**
	 * @covers ::sendEmail
	 */
	public function testSendEmailSendEmailThrows() {
		$mockSendGrid = $this->createNoOpMock( SendGrid::class, [ 'send' ] );

		$mockSendGrid->expects( $this->once() )
			->method( 'send' )
			->willThrowException( new Exception( 'Test exception' ) );

		$actual = ( new SGHooks() )->sendEmail(
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body',
			$mockSendGrid
		);

		$this->assertInstanceOf( Status::class, $actual );
		$this->assertSame( 'Test exception', $actual->getValue() );
	}

}
