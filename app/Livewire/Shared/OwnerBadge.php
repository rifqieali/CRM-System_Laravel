<?php

namespace App\Livewire\Shared;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class OwnerBadge extends Component
{
    #[Locked]
    public int $userId;

    #[Locked]
    public bool $linkable = false;

    public function render(): View
    {
        $owner = User::find($this->userId);

        return view('livewire.shared.owner-badge', [
            'owner' => $owner,
        ]);
    }
}
