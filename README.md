# MailAPI MediaWiki Extension

MediaWiki extension that sends emails through an external service conforming to the [Mail API Specification](https://github.com/mailapi/mailapi) (`openapi.yaml`).

## Features

- **Mail API Compliant**: Strictly follows the vendor-neutral [Mail API OpenAPI Specification](https://github.com/mailapi/mailapi/blob/main/openapi.yaml).
- **Simple Configuration**: Requires only `$wgMailAPIEndpoint` without authentication credentials.
- **Hook Integration**: Uses MediaWiki's standard `AlternateUserMailer` hook to intercept and route all outgoing emails.
- **Problem Details Handling**: Parses RFC 9457 Problem Details responses from the Mail API server for clear error reporting.

## Installation

Note: Extension:MailAPI targets MediaWiki `1.43+`.

1. Clone or download the extension to your MediaWiki extensions directory:

```bash
cd /path/to/mediawiki/extensions
git clone --depth 1 https://github.com/mailapi/mediawiki-extensions-MailAPI.git
```

2. (Optional) If using Composer merge loading, update `composer.local.json`:

```json
{
    "extra": {
        "merge-plugin": {
            "include": [
                "extensions/*/composer.json",
                "skins/*/composer.json"
            ]
        }
    }
}
```

```bash
cd /path/to/mediawiki
composer update --no-dev
```

## Configuration in LocalSettings.php

Add the following lines to your `LocalSettings.php`:

```php
wfLoadExtension( 'MailAPI' );
$wgMailAPIEndpoint = 'http://localhost:8080'; // or 'http://localhost:8080/v1/messages'
```

### Configuration Options

| Variable | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `$wgMailAPIEndpoint` | `string` | `""` | The base URL or full endpoint URL (`/v1/messages`) of the Mail API service. |

## How It Works

1. **Email Interception**: Listens to the `AlternateUserMailer` hook called by `UserMailer::send()`.
2. **Payload Construction**: Formats the sender, recipient(s), subject, text/html content, and supplemental headers into the Mail API `OutboundMessageRequest` schema:
   - `from`: `{ "email": "...", "name": "..." }`
   - `to`: `[ { "email": "...", "name": "..." } ]`
   - `subject`: `"..."`
   - `text`: `"..."` / `html`: `"..."`
   - `replyTo` / `cc` / `bcc`: `[ { "email": "...", ... } ]` (extracted from headers if present)
   - `headers`: `[ { "name": "...", "value": "..." } ]` (supplemental headers)
3. **HTTP Dispatch**: Sends an HTTP `POST` request with `Content-Type: application/json` to `/v1/messages`.
4. **Result Handling**: On success (HTTP 200), skips MediaWiki's default mail transport and logs the Mail API message ID. On failure, logs the RFC 9457 problem details and falls back to MediaWiki's default mail transport.

## Testing

Run PHPUnit tests:

```bash
composer install
./vendor/bin/phpunit
```

## License

[Apache License 2.0](LICENSE)
