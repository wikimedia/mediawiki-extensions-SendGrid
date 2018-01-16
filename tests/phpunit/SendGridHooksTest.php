<?php
/**
 * Test for hooks code.
 *
 * @file
 * @author Nikita Volobuev
 * @license GPL-2.0-or-later
 */

class SendGridHooksTest extends MediaWikiTestCase {

	/**
	 * Test that onAlternateUserMailer throws Exception if api key is missing.
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
	 */
	public function testOnAlternateUserMailer() {
		$mock = $this->getMockBuilder( 'SendGrid' )
			->disableOriginalConstructor()
			->getMock();
		$mock->client = $this->getMockBuilder( 'SendGrid\Client' )
			->setMethods( [ 'mail', 'send', 'post' ] )
			->disableOriginalConstructor()
			->getMock();

		$mock->client->expects( $this->once() )
			->method( 'mail' )
			->will( $this->returnValue( $mock->client ) );
		$mock->client->expects( $this->once() )
			->method( 'send' )
			->will( $this->returnValue( $mock->client ) );
		$mock->client->expects( $this->once() )
			->method( 'post' )
			->with( $this->callback( function ( $email ) {
				$this->assertSame(
					'sender@example.com',
					$email->from->getEmail()
				);
				$this->assertCount( 1, $email->personalization );
				$this->assertCount( 1, $email->personalization[0]->getTos() );
				$this->assertSame(
					'receiver@example.com',
					$email->personalization[0]->getTos()[0]->getEmail()
				);
				$this->assertSame( 'Some subject', $email->subject );
				$this->assertCount( 1, $email->contents );
				$this->assertSame(
					'text/plain',
					$email->contents[0]->getType()
				);
				$this->assertSame(
					'Email body',
					$email->contents[0]->getValue()
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
