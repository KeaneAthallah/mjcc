<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCoordinates;
use Illuminate\Foundation\Http\FormRequest;

class StoreKelurahanRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Kelurahan::class);
    }

    public function rules(): array
    {
        $c = $this->coordinateRules(false);

        return [
            'kecamatan_id' => ['required', 'exists:kecamatans,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'latitude' => $c['latitude'],
            'longitude' => $c['longitude'],
            'population' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
