<?php

namespace App\Http\Requests;

use App\Models\Contact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can('create', Contact::class);
        }

        $contact = $this->route('contact');

        return $this->user()->can('update', $contact);
    }

    public function rules(): array
    {
        $contactId = $this->route('contact')?->id;

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
}
