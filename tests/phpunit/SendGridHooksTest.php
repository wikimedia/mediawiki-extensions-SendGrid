<?php
/**
 * Test for hooks code.
 *
 * @file
 * @author Nikita Volobuev
 * @license GPL-2.0-or-later
 *
 */

class SendGridHooksTest extends MediaWikiTestCase {

	/**
	 * Test that onAlternateUserMailer throws Exception if api key is missing.
	 * @covers \SendGridHooks::onAlternateUserMailer
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

		SendGridHooks::onAlternateUserMailer(
			[ 'SomeHeader' => 'SomeValue' ],
			[ new MailAddress( 'receiver@example.com' ) ],
			new MailAddress( 'sender@example.com' ),
			'Some subject',
			'Email body'
		);
	}

	/**
	 * Test sending mail in onAlternateUserMailer hook.
	 * @covers \SendGridHooks::onAlternateUserMailer
	 */
	public function testOnAlternateUserMailer() {
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

		$result = SendGridHooks::onAlternateUserMailer(
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
