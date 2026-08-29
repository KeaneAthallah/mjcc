<?php

namespace App\Http\Requests\Concerns;

trait ValidatesCoordinates
{
    /**
     * @return array<string, mixed>
     */
    protected function coordinateRules(bool $required = false): array
    {
        $requiredRule = $required ? ['required'] : ['nullable'];

        return [
            'latitude' => [...$requiredRule, 'numeric', 'between:-90,90'],
            'longitude' => [...$requiredRule, 'numeric', 'between:-180,180'],
        ];
    }

    protected function optionalField(?string $value = null): array
    {
        return match (true) {
            $value === null => ['nullable'],
            default => ['nullable', $value],
        };
    }
}
