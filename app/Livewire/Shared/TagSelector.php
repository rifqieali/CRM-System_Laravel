<?php

namespace App\Livewire\Shared;

use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate as GateFacade;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class TagSelector extends Component
{
    #[Locked]
    public string $morphType;

    #[Locked]
    public int $morphId;

    /** @var array<int> */
    public array $selected = [];

    #[Validate('required|string|max:100')]
    public string $newTagName = '';

    public function mount(string $morphType, int $morphId, array $selected = []): void
    {
        $this->morphType = $morphType;
        $this->morphId = $morphId;
        $this->selected = $selected;
    }

    public function toggle(int $tagId): void
    {
        $parent = $this->morphType::findOrFail($this->morphId);
        GateFacade::authorize('update', $parent);

        if (in_array($tagId, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$tagId]));
            $parent->tags()->detach($tagId);
        } else {
            $this->selected[] = $tagId;
            $parent->tags()->attach($tagId);
        }

        $this->dispatch('tag-toggled');
    }

    public function createAndAttach(): void
    {
        $this->validate();

        $parent = $this->morphType::findOrFail($this->morphId);
        GateFacade::authorize('update', $parent);

        $base = Str::slug($this->newTagName);
        $slug = $base;
        $suffix = 2;

        while (Tag::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        $tag = Tag::create([
            'name' => $this->newTagName,
            'slug' => $slug,
        ]);

        $parent->tags()->attach($tag->id);
        $this->selected[] = $tag->id;
        $this->reset('newTagName');

        session()->flash('status', "Tag \"{$tag->name}\" dibuat dan ditambahkan.");
        $this->dispatch('tag-toggled');
    }

    public function render(): View
    {
        $tags = Tag::orderBy('name')->get(['id', 'name', 'color']);

        return view('livewire.shared.tag-selector', [
            'tags' => $tags,
        ]);
    }
}
