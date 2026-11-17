<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AdminDateFilter;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Read-only audit report for administrator-authorised POS price overrides
 * and discounts. Event payloads are stored as JSON in order_status_history.note.
 */
class PosPriceDiscountAuditController extends Controller
{
    private const PRICE_EVENT = 'pos_price_override';
    private const DISCOUNT_EVENT = 'pos_discount_applied';

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters);

        // MariaDB 10.4+ supports JSON_VALID / JSON_EXTRACT. Guard every read so
        // older non-JSON notes cannot break the report.
        $summary = (clone $query)->selectRaw(<<<'SQL'
            COUNT(*) AS total_events,
            COUNT(DISTINCT h.order_id) AS affected_receipts,
            SUM(CASE WHEN h.event_type = 'pos_price_override' THEN 1 ELSE 0 END) AS price_override_events,
            SUM(CASE WHEN h.event_type = 'pos_discount_applied' THEN 1 ELSE 0 END) AS discount_events,
            SUM(CASE
                WHEN h.event_type = 'pos_discount_applied' AND JSON_VALID(h.note)
                THEN CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(h.note, '$.discount_amount')), '0') AS DECIMAL(14,2))
                ELSE 0
            END) AS discount_total,
            SUM(CASE
                WHEN h.event_type = 'pos_price_override' AND JSON_VALID(h.note)
                THEN (
                    CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(h.note, '$.charged_unit_price')), '0') AS DECIMAL(14,2))
                    - CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(h.note, '$.catalogue_unit_price')), '0') AS DECIMAL(14,2))
                ) * CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(h.note, '$.quantity')), '0') AS DECIMAL(14,2))
                ELSE 0
            END) AS price_override_net_change
        SQL)->first();

        $events = (clone $query)
            ->select('h.*')
            ->orderByDesc('h.created_at')
            ->orderByDesc('h.history_id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $events->getCollection()->transform(function (object $event): object {
            $payload = json_decode((string) ($event->note ?? ''), true);
            $event->payload = is_array($payload) ? $payload : [];
            $event->is_price_override = $event->event_type === self::PRICE_EVENT;
            $event->is_discount = $event->event_type === self::DISCOUNT_EVENT;
            $event->reason = trim((string) ($event->payload['reason'] ?? ''));
            return $event;
        });

        $staffOptions = User::query()
            ->whereIn('user_id', function ($subquery) {
                $subquery->from('order_status_history')
                    ->select('actor_id')
                    ->whereIn('event_type', [self::PRICE_EVENT, self::DISCOUNT_EVENT])
                    ->whereNotNull('actor_id')
                    ->distinct();
            })
            ->with('role')
            ->orderBy('name')
            ->get(['user_id', 'name', 'role_id', 'status']);

        return view('admin.pos-price-discount-audit.index', compact(
            'filters',
            'summary',
            'events',
            'staffOptions'
        ));
    }

    public function csv(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $events = $this->filteredQuery($filters)
            ->select('h.*')
            ->orderByDesc('h.created_at')
            ->orderByDesc('h.history_id')
            ->get();

        $filename = 'pos-price-discount-audit-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($events): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM supports Khmer names in common spreadsheet apps.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'History ID', 'Order ID', 'Date', 'Staff', 'Staff ID', 'Event',
                'Product', 'Product ID', 'Quantity', 'Catalogue unit price (USD)',
                'Charged unit price (USD)', 'Unit price difference (USD)',
                'Price override total difference (USD)', 'Discount percent',
                'Discount amount (USD)', 'Reason',
            ]);

            foreach ($events as $event) {
                $payload = json_decode((string) ($event->note ?? ''), true);
                $payload = is_array($payload) ? $payload : [];
                $isPrice = $event->event_type === self::PRICE_EVENT;
                $cataloguePrice = $isPrice ? (float) ($payload['catalogue_unit_price'] ?? 0) : null;
                $chargedPrice = $isPrice ? (float) ($payload['charged_unit_price'] ?? 0) : null;
                $quantity = $isPrice ? (int) ($payload['quantity'] ?? 0) : null;
                $unitDifference = $isPrice ? round($chargedPrice - $cataloguePrice, 2) : null;
                $totalDifference = $isPrice ? round($unitDifference * $quantity, 2) : null;

                fputcsv($handle, [
                    $event->history_id,
                    $event->order_id,
                    $event->created_at,
                    $this->csvSafe($event->actor_label ?: ($event->actor_id ? 'Staff #' . $event->actor_id : 'Unknown staff')),
                    $event->actor_id,
                    $isPrice ? 'Price override' : 'Discount applied',
                    $this->csvSafe($payload['product_name'] ?? ''),
                    $payload['product_id'] ?? '',
                    $quantity ?? '',
                    $cataloguePrice === null ? '' : number_format($cataloguePrice, 2, '.', ''),
                    $chargedPrice === null ? '' : number_format($chargedPrice, 2, '.', ''),
                    $unitDifference === null ? '' : number_format($unitDifference, 2, '.', ''),
                    $totalDifference === null ? '' : number_format($totalDifference, 2, '.', ''),
                    $isPrice ? '' : ($payload['discount_percent'] ?? ''),
                    $isPrice ? '' : number_format((float) ($payload['discount_amount'] ?? 0), 2, '.', ''),
                    $this->csvSafe($payload['reason'] ?? ''),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:160'],
            'staff_id' => ['nullable', 'integer', 'exists:users,user_id'],
            'event_type' => ['nullable', Rule::in(['price_override', 'discount'])],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50, 100])],
        ]);

        return [
            'date' => AdminDateFilter::resolve($request, 'start_date', 'end_date'),
            'search' => trim((string) ($validated['search'] ?? '')),
            'staff_id' => $validated['staff_id'] ?? null,
            'event_type' => $validated['event_type'] ?? '',
            'per_page' => (int) ($validated['per_page'] ?? 25),
        ];
    }

    private function filteredQuery(array $filters): Builder
    {
        $query = DB::table('order_status_history as h')
            ->whereIn('h.event_type', [self::PRICE_EVENT, self::DISCOUNT_EVENT]);

        AdminDateFilter::apply($query, 'h.created_at', $filters['date']);

        if (!empty($filters['staff_id'])) {
            $query->where('h.actor_id', (int) $filters['staff_id']);
        }

        if ($filters['event_type'] === 'price_override') {
            $query->where('h.event_type', self::PRICE_EVENT);
        } elseif ($filters['event_type'] === 'discount') {
            $query->where('h.event_type', self::DISCOUNT_EVENT);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function (Builder $nested) use ($search) {
                if (ctype_digit($search)) {
                    $nested->where('h.order_id', (int) $search)
                        ->orWhere('h.history_id', (int) $search);
                }

                $nested->orWhere('h.actor_label', 'like', '%' . $search . '%')
                    ->orWhere('h.note', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    /** Prevent spreadsheet formula execution from untrusted text fields. */
    private function csvSafe(mixed $value): string
    {
        $text = (string) $value;
        if ($text !== '' && preg_match('/^[\s]*[=+\-@\t\r]/u', $text)) {
            return "'" . $text;
        }

        return $text;
    }
}
