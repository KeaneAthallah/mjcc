<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\ValidatesCoordinates;
use Illuminate\Foundation\Http\FormRequest;

class UpdateResponderLocationRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return $this->user()->can('updateLocation', $this->route('sos'));
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
        ];
    }
}
