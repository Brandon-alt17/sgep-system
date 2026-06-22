<?php

declare(strict_types=1);

namespace App\Exports;

/** Instrumentación temporal de depuración (sesión b8f154). */
final class F023AgentDebugLog
{
    private const LOG_PATH = '.cursor/debug-b8f154.log';

    private const SESSION_ID = 'b8f154';

    private const INGEST_URL = 'http://127.0.0.1:7921/ingest/a8667666-f323-486d-82ef-bdf9cf7ae764';

    /**
     * @param array<string, mixed> $data
     */
    public static function write(string $hypothesisId, string $location, string $message, array $data = [], string $runId = 'pre-fix'): void
    {
        // #region agent log
        $payload = [
            'sessionId' => self::SESSION_ID,
            'hypothesisId' => $hypothesisId,
            'location' => $location,
            'message' => $message,
            'data' => $data,
            'timestamp' => (int) round(microtime(true) * 1000),
            'runId' => $runId,
        ];
        $entry = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($entry === false) {
            return;
        }

        // Apache corre como usuario "http" sin permiso de escritura en .cursor; usar HTTP ingest.
        self::sendHttp($entry);

        // Fallback a archivo (CLI/tests donde sí hay permisos).
        @file_put_contents(base_path(self::LOG_PATH), $entry . "\n", FILE_APPEND | LOCK_EX);
        // #endregion
    }

    private static function sendHttp(string $jsonBody): void
    {
        // #region agent log
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nX-Debug-Session-Id: " . self::SESSION_ID . "\r\n",
                'content' => $jsonBody,
                'timeout' => 1.5,
                'ignore_errors' => true,
            ],
        ]);
        @file_get_contents(self::INGEST_URL, false, $ctx);
        // #endregion
    }
}
