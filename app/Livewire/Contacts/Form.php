<?php

namespace App\Livewire\Contacts;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate as GateFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Contact')]
class Form extends Component
{
    public ?int $contactId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $mobile = '';

    public string $job_title = '';

    public ?int $company_id = null;

    public int $owner_id = 0;

    public ?string $source = null;

    public string $notes = '';

    /** @var array<int> */
    public array $tag_ids = [];

    public string $newTagName = '';

    public function mount(?Contact $contact = null): void
    {
        if ($contact && $contact->exists) {
            GateFacade::authorize('update', $contact);

            $this->contactId = $contact->id;
            $this->first_name = $contact->first_name;
            $this->last_name = $contact->last_name;
            $this->email = $contact->email;
            $this->phone = (string) $contact->phone;
            $this->mobile = (string) $contact->mobile;
            $this->job_title = (string) $contact->job_title;
            $this->company_id = $contact->company_id;
            $this->owner_id = (int) $contact->owner_id;
            $this->source = $contact->source;
            $this->notes = (string) $contact->notes;
            $this->tag_ids = $contact->tags()->pluck('tags.id')->all();
        } else {
            GateFacade::authorize('create', Contact::class);
            $this->owner_id = (int) auth()->id();
        }
    }

    public function rules(): array
    {
        $contactId = $this->contactId;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('contacts', 'email')->ignore($contactId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'source' => ['nullable', Rule::in(['website', 'referral', 'cold-call', 'event', 'other'])],
            'notes' => ['nullable', 'string'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('tags', 'id')],
        ];
    }

    public function save(bool $addAnother = false): void
    {
        $validated = $this->validate();

        $contact = $this->contactId
            ? tap(Contact::findOrFail($this->contactId))->update($validated)
            : Contact::create($validated);

        $contact->tags()->sync($this->tag_ids);

        if ($addAnother) {
            $this->reset(['first_name', 'last_name', 'email', 'phone', 'mobile', 'job_title', 'company_id', 'source', 'notes', 'tag_ids']);
            $this->owner_id = (int) auth()->id();
            session()->flash('status', 'Contact disimpan. Tambah lagi.');

            return;
        }

        session()->flash('status', 'Contact disimpan.');
        $this->redirectRoute('contacts.show', ['contact' => $contact->id], navigate: true);
    }

    #[Computed]
    public function companiesList(): array
    {
        return Company::orderBy('name')->limit(200)->get(['id', 'name'])->toArray();
    }

    #[Computed]
    public function ownersList(): array
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isManager()) {
            return User::orderBy('name')->get(['id', 'name'])->toArray();
        }

        $ids = array_merge([$user->id], $user->teamIds());

        return User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])->toArray();
    }

    #[Computed]
    public function availableTags(): array
    {
        return Tag::orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function toggleTag(int $tagId): void
    {
        if (in_array($tagId, $this->tag_ids, true)) {
            $this->tag_ids = array_values(array_diff($this->tag_ids, [$tagId]));
        } else {
            $this->tag_ids[] = $tagId;
        }
    }

    public function createTag(): void
    {
        $this->validate(['newTagName' => 'required|string|max:100']);

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

        $this->tag_ids[] = $tag->id;
        $this->reset('newTagName');
    }

    public function render(): View
    {
        return view('livewire.contacts.form');
    }
}
