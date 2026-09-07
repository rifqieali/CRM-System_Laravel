<?php

declare(strict_types=1);

namespace App\Livewire\Tags;

use App\Http\Requests\TagRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Tags')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'sort', except: 'name')]
    public string $sort = 'name';

    public string $sortDirection = 'asc';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $color = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('create', Tag::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $tagId): void
    {
        $tag = Tag::findOrFail($tagId);
        $this->authorize('update', $tag);
        $this->editingId = $tag->id;
        $this->name = $tag->name;
        $this->color = (string) $tag->color;
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function save(): void
    {
        $rules = (new TagRequest)->rules();
        $this->validate($rules, (new TagRequest)->messages());

        if ($this->editingId !== null) {
            $tag = Tag::findOrFail($this->editingId);
            $this->authorize('update', $tag);
            $tag->update([
                'name' => $this->name,
                'color' => $this->color !== '' ? $this->color : null,
            ]);
            session()->flash('status', "Tag \"{$tag->name}\" diperbarui.");
        } else {
            $this->authorize('create', Tag::class);
            $slug = Str::slug($this->name);
            $suffix = 2;
            $base = $slug;
            while (Tag::where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix++;
            }
            $tag = Tag::create([
                'name' => $this->name,
                'slug' => $slug,
                'color' => $this->color !== '' ? $this->color : null,
            ]);
            session()->flash('status', "Tag \"{$tag->name}\" dibuat.");
        }

        $this->cancelForm();
    }

    public function delete(int $tagId): void
    {
        $tag = Tag::findOrFail($tagId);
        $this->authorize('delete', $tag);
        $name = $tag->name;
        $tag->delete();
        session()->flash('status', "Tag \"{$name}\" dihapus.");
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->color = '';
    }

    #[Computed]
    public function usageCounts(): array
    {
        $counts = [];
        $tables = ['contacts', 'companies', 'deals'];
        $types = [Contact::class, Company::class, Deal::class];

        foreach (Tag::pluck('id') as $id) {
            $total = 0;
            foreach ($types as $type) {
                $relation = match ($type) {
                    Contact::class => 'contacts',
                    Company::class => 'companies',
                    Deal::class => 'deals',
                };
                $total += \DB::table('taggables')
                    ->where('tag_id', $id)
                    ->where('taggable_type', $type)
                    ->count();
            }
            $counts[$id] = $total;
        }

        return $counts;
    }

    public function render(): View
    {
        $query = Tag::query();

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        $allowedSorts = ['name', 'slug', 'created_at'];
        $sortField = in_array($this->sort, $allowedSorts, true) ? $this->sort : 'name';
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $direction);

        $tags = $query->paginate(15);

        return view('livewire.tags.index', [
            'tags' => $tags,
        ]);
    }
}
