<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCoordinates;
use App\Models\Poskamling;
use Illuminate\Foundation\Http\FormRequest;

class StorePoskamlingRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return $this->user()->can('create', Poskamling::class);
    }

    public function rules(): array
    {
        $c = $this->coordinateRules(false);

        return [
            'kecamatan_id' => ['required', 'exists:kecamatans,id'],
            'kelurahan_id' => ['nullable', 'exists:kelurahans,id'],
            'name' => ['required', 'string', 'max:255'],
            'latitude' => $c['latitude'],
            'longitude' => $c['longitude'],
            'status' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
