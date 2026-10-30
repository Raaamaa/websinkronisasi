<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

class AuditAuthEventsListener
{
    public function __construct(
        protected AuditLogService $auditService
    ) {}

    public function handleLogin(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $this->auditService->log(
            action: 'auth.login',
            entityType: 'users',
            entityId: (string) $user->id,
            oldValues: null,
            newValues: ['email' => $user->email, 'role' => $user->role],
            userId: (string) $user->id
        );
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if ($user !== null) {
            $this->auditService->log(
                action: 'auth.logout',
                entityType: 'users',
                entityId: (string) $user->id,
                oldValues: null,
                newValues: ['email' => $user->email],
                userId: (string) $user->id
            );
        }
    }

    /**
     * Register listeners for subscriber.
     *
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
        ];
    }
}
