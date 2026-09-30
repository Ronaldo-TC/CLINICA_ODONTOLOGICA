<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Http\Request;

class LogService
{
    public const TYPE_INFO  = 'info';
    public const TYPE_WARN  = 'warn';
    public const TYPE_ERROR = 'error';
    public const TYPE_DEBUG = 'debug';

    /**
     * Registra una entrada en logtable.
     */
    public static function write(
        string $type,
        string $data,
        ?int $userId = null,
        ?string $ip = null,
        string $status = ''
    ): void {
        Log::create([
            'loguser'   => $userId,
            'logip'     => $ip ?? request()->ip() ?? '0.0.0.0',
            'logdate'   => now()->timestamp,
            'logtype'   => $type,
            'logdata'   => $data,
            'logstatus' => $status,
        ]);
    }

    public static function info(string $data, ?int $userId = null, string $status = 'OK'): void
    {
        self::write(self::TYPE_INFO, $data, $userId, null, $status);
    }

    public static function warn(string $data, ?int $userId = null): void
    {
        self::write(self::TYPE_WARN, $data, $userId);
    }

    public static function error(string $data, ?int $userId = null): void
    {
        self::write(self::TYPE_ERROR, $data, $userId);
    }

    public static function fromRequest(Request $request, string $type, string $data, string $status = 'OK'): void
    {
        self::write($type, $data, $request->user()?->usernumber, $request->ip(), $status);
    }
}
