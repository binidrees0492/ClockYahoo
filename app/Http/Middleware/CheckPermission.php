<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission, ?string $param = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Active status check
        if ($user->status === 'Inactive') {
            auth()->logout();
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Please contact your administrator.']);
        }

        $allowed = match ($permission) {
            'admin'              => $user->canAccessAdmin(),
            'approve_timesheets' => $user->canApproveTimesheets(),
            'timesheets'         => $user->canAccessTimesheets(),
            'schedules'          => $user->canAccessSchedules(),
            'reports'            => $user->canViewReports(),
            'map'                => $user->canViewMap(),
            'view'               => $param ? $user->canView($param) : true,
            'edit'               => $param ? $user->canEdit($param) : true,
            'timeoff_manage'     => $user->canManageTimeOff(),
            default              => false,
        };

        if (!$allowed) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
