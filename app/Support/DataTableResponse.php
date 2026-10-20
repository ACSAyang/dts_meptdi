<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Server-side processing for DataTables (paging, sorting and search done by the database,
 * so registers stay fast however many years of records they hold).
 *
 * @see https://datatables.net/manual/server-side
 */
class DataTableResponse
{
    private const MAX_PAGE = 500;

    /**
     * @param  Builder  $query  base query, already narrowed by the page's own filters
     * @param  array<int, string|null>  $orderable  column index => database column (null: not sortable)
     * @param  Closure(Builder, string): void  $search  applies the search box text
     * @param  Closure(mixed): array{cells: array<int, string>, href?: string}  $row  renders one record
     */
    public static function make(Request $request, Builder $query, array $orderable, Closure $search, Closure $row): JsonResponse
    {
        $total = (clone $query)->count();

        $term = trim((string) $request->input('search.value'));
        if ($term !== '') {
            $search($query, $term);
        }
        $filtered = $term !== '' ? (clone $query)->count() : $total;

        foreach ((array) $request->input('order', []) as $order) {
            $column = $orderable[(int) ($order['column'] ?? -1)] ?? null;
            if ($column) {
                $query->orderBy($column, ($order['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc');
            }
        }
        $query->orderByDesc($query->getModel()->getQualifiedKeyName());

        $length = (int) $request->input('length', 25);
        $length = $length < 1 ? self::MAX_PAGE : min($length, self::MAX_PAGE);

        $data = $query->skip(max(0, (int) $request->input('start', 0)))->take($length)->get()
            ->map(function ($record) use ($row) {
                $rendered = $row($record);
                $out = array_map('strval', $rendered['cells']);
                if (! empty($rendered['href'])) {
                    $out['DT_RowAttr'] = ['data-href' => $rendered['href']];
                }

                return $out;
            });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }
}
