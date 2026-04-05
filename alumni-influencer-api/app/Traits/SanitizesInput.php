<?php

namespace App\Traits;

trait SanitizesInput
{
    protected function sanitizeInput(array $data, array $skip = []): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $skip, true)) {
                continue;
            }

            if (is_string($value)) {
                $data[$key] = trim(strip_tags($value));
            }
        }

        return $data;
    }
}
