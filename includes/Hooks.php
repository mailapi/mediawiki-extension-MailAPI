<?php

namespace MediaWiki\Extension\MailAPI;

use MailAddress;
use MediaWiki\Hook\AlternateUserMailerHook;
use MediaWiki\MediaWikiServices;
use Throwable;

class Hooks implements AlternateUserMailerHook
{
    /**
     * Send MediaWiki mail through Mail API.
     *
     * @param array|string $headers
     * @param MailAddress[]|MailAddress $to
     * @param MailAddress $from
     * @param string $subject
     * @param string|array $body
     * @return bool|string False on success (skips default mailer), error string on failure
     */
    public function onAlternateUserMailer($headers, $to, $from, $subject, $body)
    {
        global $wgMailAPIEndpoint;

        $endpoint = (string)$wgMailAPIEndpoint;
        if ($endpoint === '' && class_exists(MediaWikiServices::class)) {
            $config = MediaWikiServices::getInstance()->getMainConfig();
            if ($config->has('MailAPIEndpoint')) {
                $endpoint = (string)$config->get('MailAPIEndpoint');
            }
        }

        if ($endpoint === '') {
            return 'Please set $wgMailAPIEndpoint in LocalSettings.php.';
        }

        try {
            $client = new Client($endpoint);
            $payload = $client->buildPayload($headers, $to, $from, $subject, $body);
            $client->send($payload);

            return false;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
}
