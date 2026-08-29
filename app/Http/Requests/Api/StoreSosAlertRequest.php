<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\ValidatesCoordinates;
use App\Models\SosAlert;
use Illuminate\Foundation\Http\FormRequest;

class StoreSosAlertRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return $this->user()->can('create', SosAlert::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $c = $this->coordinateRules(true);

        return [
            'latitude' => $c['latitude'],
            'longitude' => $c['longitude'],
            'accuracy' => ['nullable', 'numeric', 'between:0,1000'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
