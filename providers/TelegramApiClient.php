<?php

namespace LiveHelperChatExtension\lhctelegram\providers;

/**
 * HTTP client for every Bot API call of the extension (Longman\TelegramBot\Request uses the client set here).
 *
 * - "429 Too Many Requests": Telegram says how long to wait (parameters.retry_after). Short waits are honoured and
 *   the call is repeated, so a burst of messages (e.g. many chats at once in one group, ~20 messages a minute per
 *   group) is delayed instead of lost. Longer waits are not slept through: the call fails as before.
 * - Every refused call (HTTP 4xx/5xx) goes to the audit log (category telegram_api_error) with the method and
 *   Telegram's description, so failed deliveries are visible in "System → Audit log". The bot token is never logged.
 */
class TelegramApiClient
{
    const MAX_RETRIES = 2;
    const MAX_WAIT_SECONDS = 10;

    // Expected answers that are not failures of delivery
    const QUIET_ERRORS = array('message is not modified', 'query is too old', 'message to delete not found');

    public static function install()
    {
        static $installed = false;
        if ($installed || !class_exists('\GuzzleHttp\HandlerStack') || !class_exists('\Longman\TelegramBot\Request')) {
            return;
        }
        $installed = true;

        // HandlerStack::create() puts http_errors outermost and these two innermost (retry closest to the transport):
        // both still see a 4xx as a response, before http_errors turns it into an exception for Longman.
        $stack = \GuzzleHttp\HandlerStack::create();
        $stack->push(self::logMiddleware(), 'telegram_log');
        $stack->push(\GuzzleHttp\Middleware::retry(array(self::class, 'shouldRetry'), array(self::class, 'retryDelay')), 'telegram_retry');

        // Same base URI and timeouts as the client Longman creates by default
        \Longman\TelegramBot\Request::setClient(new \GuzzleHttp\Client(array(
            'base_uri' => 'https://api.telegram.org',
            'timeout' => 20,
            'connect_timeout' => 10,
            'handler' => $stack,
        )));
    }

    private static $lastWait = 1;

    public static function retryAfter($response)
    {
        if (!$response instanceof \Psr\Http\Message\ResponseInterface || $response->getStatusCode() !== 429) {
            return null;
        }
        $data = json_decode((string)$response->getBody(), true);
        return isset($data['parameters']['retry_after']) ? (int)$data['parameters']['retry_after'] : 1;
    }

    public static function shouldRetry($retries, $request, $response = null, $exception = null)
    {
        if ($response === null && $exception instanceof \GuzzleHttp\Exception\RequestException && $exception->hasResponse()) {
            $response = $exception->getResponse();
        }
        $wait = self::retryAfter($response);
        if ($wait === null || $retries >= self::MAX_RETRIES || $wait > self::MAX_WAIT_SECONDS) {
            return false;
        }
        // The same body is sent again: only possible when it can be read from the start (files are seekable streams)
        $body = $request->getBody();
        if (!$body->isSeekable()) {
            return false;
        }
        $body->rewind();
        self::$lastWait = max(1, $wait);
        return true;
    }

    public static function retryDelay($retries, $response = null)
    {
        return self::$lastWait * 1000;
    }

    private static function logMiddleware()
    {
        return function (callable $handler) {
            return function ($request, array $options) use ($handler) {
                return $handler($request, $options)->then(function ($response) use ($request) {
                    if ($response instanceof \Psr\Http\Message\ResponseInterface && $response->getStatusCode() >= 400) {
                        self::logFailure(basename($request->getUri()->getPath()), $response);
                    }
                    return $response;
                }, function ($reason) use ($request) {
                    if ($reason instanceof \GuzzleHttp\Exception\RequestException && $reason->hasResponse()) {
                        self::logFailure(basename($request->getUri()->getPath()), $reason->getResponse());
                    }
                    return \GuzzleHttp\Promise\Create::rejectionFor($reason);
                });
            };
        };
    }

    private static function logFailure($method, $response)
    {
        $data = json_decode((string)$response->getBody(), true);
        $description = is_array($data) && isset($data['description']) ? (string)$data['description'] : 'HTTP ' . $response->getStatusCode();
        foreach (self::QUIET_ERRORS as $quiet) {
            if (stripos($description, $quiet) !== false) {
                return;
            }
        }
        try {
            \erLhcoreClassLog::write($method . ': ' . $response->getStatusCode() . ' ' . $description,
                \ezcLog::SUCCESS_AUDIT,
                array(
                    'source' => 'lhc',
                    'category' => 'telegram_api_error',
                    'line' => __LINE__,
                    'file' => __FILE__,
                )
            );
        } catch (\Throwable $e) {
            // Logging must never break delivery
        }
    }
}
