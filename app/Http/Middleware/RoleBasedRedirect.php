<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedRedirect
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    if (Auth::check()) {
      $user = Auth::user();

      // If user is trying to access the dashboard route
      if ($request->routeIs('dashboard')) {
        // Redirect admin, HR, and managers to admin dashboard
        if ($user->hasRole(['admin', 'hr', 'manager'])) {
          return redirect()->route('admin.dashboard');
        }
      }

      // If user is trying to access the admin dashboard route
      if ($request->routeIs('admin.dashboard')) {
        // Redirect regular employees to employee dashboard
        if (!$user->hasRole(['admin', 'hr', 'manager'])) {
          return redirect()->route('dashboard');
        }
      }
    }

    return $next($request);
  }
}
