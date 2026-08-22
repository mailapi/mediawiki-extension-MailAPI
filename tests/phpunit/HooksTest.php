<?php

namespace MediaWiki\Extension\MailAPI\Tests;

use MailAddress;
use MediaWiki\Extension\MailAPI\Hooks;
use MWException;
use PHPUnit\Framework\TestCase;

class HooksTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        global $wgMailAPIEndpoint;
        $wgMailAPIEndpoint = null;
    }

    public function testOnAlternateUserMailerWithoutEndpointReturnsErrorMessage(): void
    {
        global $wgMailAPIEndpoint;
        $wgMailAPIEndpoint = '';

        $hooks = new Hooks();
        $ret = $hooks->onAlternateUserMailer(
            [],
            new MailAddress('to@example.com'),
            new MailAddress('from@example.com'),
            'Subject',
            'Body'
        );

        $this->assertIsString($ret);
        $this->assertSame('Please set $wgMailAPIEndpoint in LocalSettings.php.', $ret);
    }
}
