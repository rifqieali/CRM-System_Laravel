<?php

namespace App\Livewire\Shared;

use App\Models\Activity;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ActivityTimeline extends Component
{
    #[Locked]
    public string $morphType;

    #[Locked]
    public int $morphId;

    public ?string $typeFilter = null;

    public function render(): View
    {
        $query = Activity::with('user')
            ->where('activityable_type', $this->morphType)
            ->where('activityable_id', $this->morphId)
            ->latest();

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        $activities = $query->get()->filter(fn (Activity $a) => auth()->user()->can('view', $a));

        return view('livewire.shared.activity-timeline', [
            'activities' => $activities,
        ]);
    }
}
