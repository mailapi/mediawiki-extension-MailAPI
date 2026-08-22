<?php

namespace MediaWiki\Extension\MailAPI;

use MailAddress;
use MediaWiki\Hook\AlternateUserMailerHook;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Throwable;

class Hooks implements AlternateUserMailerHook
{
    private ?LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Send MediaWiki mail through Mail API.
     *
     * @param array|string $headers
     * @param MailAddress[]|MailAddress $to
     * @param MailAddress $from
     * @param string $subject
     * @param string|array $body
     * @return bool False on success (skips the default mailer), true on failure to
     *   allow the default mailer to handle the message.
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
            $this->getLogger()->error(
                'MailAPI endpoint is not configured; falling back to the default mailer.'
            );
            return true;
        }

        try {
            $client = new Client($endpoint);
            $payload = $client->buildPayload($headers, $to, $from, $subject, $body);
            $response = $client->send($payload);
            $this->getLogger()->info(
                'MailAPI accepted email for delivery. Message ID: {message_id}',
                ['message_id' => $response['id'] ?? '(missing)']
            );

            return false;
        } catch (Throwable $e) {
            $this->getLogger()->error(
                'MailAPI failed to send email; falling back to the default mailer: {error}',
                [
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]
            );
            return true;
        }
    }

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? LoggerFactory::getInstance('mailapi');
    }
}
