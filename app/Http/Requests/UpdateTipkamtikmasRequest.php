<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCoordinates;
use App\Models\Tipkamtikmas;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTipkamtikmasRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('tipkamtikma'));
    }

    public function rules(): array
    {
        $c = $this->coordinateRules(false);

        return [
            'kecamatan_id' => ['required', 'exists:kecamatans,id'],
            'kelurahan_id' => ['nullable', 'exists:kelurahans,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'latitude' => $c['latitude'],
            'longitude' => $c['longitude'],
        ];
    }
}
