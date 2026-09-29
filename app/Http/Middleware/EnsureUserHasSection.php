<?php

namespace App\Http\Middleware;

use App\Models\Profile;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasSection
{
    /**
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next, string $section): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthorizationException;
        }

        $hasAccess = Profile::query()
            ->whereIn('_id', $user->profile_ids ?? [])
            ->get(['sections'])
            ->contains(
                fn (Profile $profile): bool => in_array($section, $profile->sections ?? [], true)
            );

        if (! $hasAccess) {
            throw new AuthorizationException;
        }

        return $next($request);
    }
}
