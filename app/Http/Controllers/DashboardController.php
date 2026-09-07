<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $now = now();

        $pipelineValue = (float) Deal::scopedTo($user)
            ->whereNotIn('stage', [Deal::STAGE_WON, Deal::STAGE_LOST])
            ->sum('value');

        $dealsWonThisMonth = Deal::scopedTo($user)
            ->where('stage', Deal::STAGE_WON)
            ->whereMonth('closed_at', $now->month)
            ->whereYear('closed_at', $now->year)
            ->count();

        $dealsClosingThisWeek = Deal::scopedTo($user)
            ->whereNotIn('stage', [Deal::STAGE_WON, Deal::STAGE_LOST])
            ->whereBetween('expected_close_date', [$now->startOfWeek()->toDateString(), $now->endOfWeek()->toDateString()])
            ->count();

        $activitiesDueToday = Activity::query()
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->whereDate('due_at', today())
            ->count();

        $newContactsThisWeek = Contact::scopedTo($user)
            ->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])
            ->count();

        $recentActivities = $this->recentScopedActivities($user);

        $hour = (int) $now->format('H');
        $greeting = match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        return view('dashboard', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail(),
            'user' => $user,
            'greeting' => $greeting,
            'greetingName' => $user->name,
            'dealsClosingThisWeek' => $dealsClosingThisWeek,
            'pipelineValue' => $pipelineValue,
            'dealsWonThisMonth' => $dealsWonThisMonth,
            'activitiesDueToday' => $activitiesDueToday,
            'newContactsThisWeek' => $newContactsThisWeek,
            'recentActivities' => $recentActivities,
        ]);
    }

    private function recentScopedActivities(User $user)
    {
        $query = Activity::with(['activityable', 'user'])
            ->latest('created_at');

        if (! $user->hasAnyRole(['Admin', 'Manager'])) {
            $query->where(function ($q) use ($user) {
                $this->scopeMorphOwner($q, $user, Contact::class);
                $this->scopeMorphOwner($q, $user, Company::class);
                $this->scopeMorphOwner($q, $user, Deal::class);
            });
        }

        return $query->limit(10)->get();
    }

    private function scopeMorphOwner($query, User $user, string $morphClass): void
    {
        $query->orWhereHasMorph(
            'activityable',
            $morphClass,
            function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('owner_id', $user->id);

                    if ($user->manager_id !== null) {
                        $sub->orWhereIn('owner_id', function ($subQuery) use ($user) {
                            $subQuery->select('id')
                                ->from('users')
                                ->where('manager_id', $user->manager_id);
                        });
                    }
                });
            }
        );
    }
}
