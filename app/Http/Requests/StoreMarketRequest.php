<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCoordinates;
use App\Models\Market;
use Illuminate\Foundation\Http\FormRequest;

class StoreMarketRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return $this->user()->can('create', Market::class);
    }

    public function rules(): array
    {
        $c = $this->coordinateRules(false);

        return [
            'kecamatan_id' => ['required', 'exists:kecamatans,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => $c['latitude'],
            'longitude' => $c['longitude'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
