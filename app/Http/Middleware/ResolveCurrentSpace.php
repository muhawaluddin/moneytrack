<?php

namespace App\Http\Middleware;

use App\Models\Space;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentSpace
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $spaces = $user->spaces()->orderBy('type')->orderBy('name')->get();
            if ($spaces->isEmpty()) {
                $space = Space::create(['owner_id' => $user->id, 'name' => 'Pribadi '.$user->name, 'type' => 'personal']);
                $space->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
                $spaces = collect([$space]);
            }
            $space = $spaces->firstWhere('id', (int) $request->session()->get('space_id')) ?? $spaces->first();
            $space->load('members');
            $request->session()->put('space_id', $space->id);
            $request->attributes->set('space', $space);
            View::share(['userSpaces' => $spaces, 'currentSpace' => $space, 'currentRole' => $space->roleFor($user)]);
        }

        return $next($request);
    }
}
