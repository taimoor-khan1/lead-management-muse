<?php

namespace App\Support;

use App\Models\Lead;

/**
 * Replaces {{variable}} placeholders in email subjects/bodies
 * with values from a Lead. Unknown placeholders are left untouched.
 */
class EmailVariables
{
    public const VARIABLES = [
        'business_name',
        'contact_person',
        'city',
        'industry',
        'suggested_service',
    ];

    public static function replace(string $text, Lead $lead): string
    {
        return preg_replace_callback('/{{\s*([a-z_]+)\s*}}/', function (array $m) use ($lead) {
            $key = $m[1];

            if (! in_array($key, self::VARIABLES, true)) {
                return $m[0];
            }

            return (string) ($lead->{$key} ?? '');
        }, $text);
    }
}
