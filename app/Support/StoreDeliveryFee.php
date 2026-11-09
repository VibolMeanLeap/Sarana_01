<?php

namespace App\Support;

use App\Models\Settings;
use Illuminate\Database\QueryException;

/**
 * Resolves the active store delivery fee.
 * A saved Store Settings value takes priority; the legacy .env setting is
 * retained as a fallback for installations that have not saved the new field.
 */
class StoreDeliveryFee
{
    public static function current(): float
    {
        try {
            $savedFee = Settings::query()->orderByDesc('id')->value('delivery_fee');

            if ($savedFee !== null && $savedFee !== '') {
                return max(0.0, round((float) $savedFee, 2));
            }
        } catch (QueryException $exception) {
            // Older databases do not have the new column until the patch SQL/migration is applied.
        }

        return max(0.0, round((float) config('services.ecommerce.delivery_fee', 0), 2));
    }
}
