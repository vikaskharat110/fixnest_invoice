<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class SalesOrderNumberGenerator
{
    public function generateFor(Organization $organization): string
    {
        return DB::transaction(function () use ($organization) {
            $locked = Organization::where('id', $organization->id)->lockForUpdate()->first();

            $number = $locked->next_order_number;

            $locked->increment('next_order_number');

            return sprintf(
                '%s-SO/%s/%04d',
                $locked->invoice_prefix,
                now()->format('Y'),
                $number
            );
        });
    }
}
