<?php

namespace App\Services;

use App\Models\ReferenceSequence;
use Illuminate\Support\Facades\DB;

/**
 * Issues unique, gap-free reference numbers per type and year,
 * e.g. MEPTDI-IN-2026-0001 and MEPTDI-OUT-2026-0001.
 * Must be called inside a database transaction.
 */
class ReferenceNumber
{
    /** @return array{reference_no: string, ref_year: int, ref_sequence: int} */
    public static function next(string $type, ?int $year = null): array
    {
        $year ??= (int) now()->format('Y');

        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Reference numbers must be issued inside a transaction.');
        }

        ReferenceSequence::query()->insertOrIgnore([
            'type' => $type, 'year' => $year, 'last_number' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $sequence = ReferenceSequence::where('type', $type)->where('year', $year)->lockForUpdate()->firstOrFail();
        $sequence->increment('last_number');

        $number = $sequence->last_number;

        return [
            'reference_no' => self::format($type, $year, $number),
            'ref_year' => $year,
            'ref_sequence' => $number,
        ];
    }

    public static function format(string $type, int $year, int $number): string
    {
        $padding = (int) config('dts.reference_padding', 4);

        return sprintf('%s-%s-%d-%s', config('dts.office_code'), $type, $year, str_pad((string) $number, $padding, '0', STR_PAD_LEFT));
    }

    /** Preview of the next number without reserving it. */
    public static function preview(string $type, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $last = ReferenceSequence::where('type', $type)->where('year', $year)->value('last_number') ?? 0;

        return self::format($type, $year, $last + 1);
    }
}
