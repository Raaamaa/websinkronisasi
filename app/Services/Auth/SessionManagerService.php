<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SessionManagerService
{
    /**
     * Revoke all active sessions for given user ID.
     */
    public function revokeUserSessions(string $userId): void
    {
        // 1. Invalidate sessions in database table if exists
        try {
            DB::table('sessions')->where('user_id', $userId)->delete();
        } catch (\Throwable) {
            // Ignore if session driver is not database
        }

        // 2. Invalidate redis sessions if redis driver is active
        try {
            if (config('session.driver') === 'redis') {
                $redis = Redis::connection();
                $prefix = (string) config('database.redis.options.prefix', '');
                $keys = $redis->keys($prefix.'*');

                foreach ($keys as $key) {
                    $payload = $redis->get($key);
                    if ($payload && str_contains((string) $payload, $userId)) {
                        $redis->del($key);
                    }
                }
            }
        } catch (\Throwable) {
            // Safe fallback: EnsureAccountIsActive middleware will reject on next request
        }
    }
}
