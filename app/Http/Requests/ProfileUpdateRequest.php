<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:5120'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'timezone' => ['nullable', 'string', 'max:100'],
            'notification_channel_preference' => ['nullable', 'string', 'in:both,bell_only,email_only'],
            'detailed_notification_settings' => ['nullable', 'array'],
            'theme_preference' => ['nullable', 'string', 'in:light,dark,gradient,sage dark green'],
            'use_custom_email' => ['nullable', 'boolean'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['nullable', 'string', 'in:none,tls,ssl'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],
            'imap_host' => ['nullable', 'string', 'max:255'],
            'imap_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'imap_encryption' => ['nullable', 'string', 'in:none,tls,ssl'],
            'imap_username' => ['nullable', 'string', 'max:255'],
            'imap_password' => ['nullable', 'string', 'max:255'],
            'standard_shift_start' => ['nullable', 'string'],
            'standard_shift_end' => ['nullable', 'string'],
            'standard_shift_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ];
    }
}
