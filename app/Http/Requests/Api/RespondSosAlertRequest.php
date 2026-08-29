<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RespondSosAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('sos'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'response_message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
