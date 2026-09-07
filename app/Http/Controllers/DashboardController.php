<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $pipelineValue = (float) Deal::scopedTo($user)
            ->whereNotIn('stage', [Deal::STAGE_WON, Deal::STAGE_LOST])
            ->sum('value');

        $dealsWonThisMonth = Deal::scopedTo($user)
            ->where('stage', Deal::STAGE_WON)
            ->whereMonth('closed_at', now()->month)
            ->whereYear('closed_at', now()->year)
            ->count();

        $activitiesDueToday = Activity::query()
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->whereDate('due_at', today())
            ->count();

        $newContactsThisWeek = Contact::scopedTo($user)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $recentActivities = Activity::query()
            ->with(['activityable', 'user'])
            ->latest()
            ->limit(10)
            ->get()
            ->filter(fn (Activity $activity) => $user->can('view', $activity))
            ->values();

        return view('dashboard', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail(),
            'user' => $user,
            'greetingName' => $user->name,
            'pipelineValue' => $pipelineValue,
            'dealsWonThisMonth' => $dealsWonThisMonth,
            'activitiesDueToday' => $activitiesDueToday,
            'newContactsThisWeek' => $newContactsThisWeek,
            'recentActivities' => $recentActivities,
        ]);
    }
}
