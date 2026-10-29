<?php

declare(strict_types=1);

namespace App\Services\Auth\Contracts;

use App\Services\Auth\DTOs\SSOUserData;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface SSOProviderInterface
{
    /**
     * Generate redirect response to Identity Provider.
     */
    public function getRedirectResponse(): Response;

    /**
     * Handle callback request from Identity Provider and extract user profile data.
     */
    public function handleCallback(Request $request): SSOUserData;
}
