<?php

namespace App\Http\Controllers;

use App\Models\OrderCorrection;
use App\Models\User;
use App\Support\AdminDateFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderCorrectionAuditController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters);

        $summary = [
            'correction_events' => (clone $query)->count(),
            'affected_receipts' => (clone $query)->distinct()->count('order_id'),
            'original_value' => (float) (clone $query)->sum('original_total'),
            'corrected_value' => (float) (clone $query)->sum('corrected_total'),
            // This is the amount staff explicitly confirmed as collected in the existing
            // correction workflow; it is not a bank/terminal-verified payment record.
            'cash_collected' => (float) (clone $query)
                ->where('payment_adjustment_status', 'settled')
                ->where('payment_adjustment_type', 'collected')
                ->sum('difference_amount'),
            'cash_refunded' => (float) (clone $query)
                ->where('payment_adjustment_status', 'settled')
                ->where('payment_adjustment_type', 'refunded')
                ->sum(DB::raw('ABS(difference_amount)')),
            'pending_collection' => (float) (clone $query)
                ->where('payment_adjustment_status', 'pending')
                ->where('difference_amount', '>', 0)
                ->sum('difference_amount'),
            'pending_refund' => (float) (clone $query)
                ->where('payment_adjustment_status', 'pending')
                ->where('difference_amount', '<', 0)
                ->sum(DB::raw('ABS(difference_amount)')),
        ];

        $corrections = (clone $query)
            ->with([
                'order.customer',
                'order.cashier',
                'order.paymentMethod',
                'createdBy.role',
                'adjustedBy.role',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('correction_id')
            ->paginate(20)
            ->withQueryString();

        // Include all users who have actually authored a correction. This keeps old
        // or deactivated staff available for historical filtering.
        $staffOptions = User::query()
            ->whereIn('user_id', function ($subquery) {
                $subquery->from('order_corrections')
                    ->select('created_by')
                    ->whereNotNull('created_by')
                    ->distinct();
            })
            ->orderBy('name')
            ->get(['user_id', 'name', 'status']);

        return view('admin.order-corrections.index', compact(
            'filters',
            'summary',
            'corrections',
            'staffOptions'
        ));
    }

    public function csv(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $corrections = $this->filteredQuery($filters)
            ->with(['order.customer', 'order.cashier', 'createdBy', 'adjustedBy'])
            ->orderByDesc('created_at')
            ->orderByDesc('correction_id')
            ->get();

        $filename = 'order-correction-audit-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($corrections): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM makes Khmer/Unicode names open correctly in common spreadsheet apps.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Correction ID', 'Receipt / Order ID', 'Correction date', 'Customer',
                'Original cashier', 'Corrected by', 'Reason', 'Notes',
                'Original total (USD)', 'Corrected total (USD)', 'Difference (USD)',
                'Adjustment status', 'Adjustment type', 'Confirmed amount (USD)',
                'Adjustment confirmed by', 'Adjustment confirmed at', 'Adjustment note',
                'Before items', 'After items',
            ]);

            foreach ($corrections as $correction) {
                $order = $correction->order;
                $difference = (float) $correction->difference_amount;
                $confirmedAmount = $correction->payment_adjustment_status === 'settled'
                    ? abs($difference)
                    : 0;

                fputcsv($handle, [
                    $correction->correction_id,
                    $correction->order_id,
                    optional($correction->created_at)->format('Y-m-d H:i:s'),
                    $order?->customer?->name ?? data_get($correction->before_snapshot, 'customer.name', 'Walk-in customer'),
                    $order?->cashier?->name ?? '',
                    $correction->createdBy?->name ?? ($correction->created_by ? 'Deleted staff #'.$correction->created_by : 'Unknown'),
                    $correction->reason,
                    $correction->notes,
                    number_format((float) $correction->original_total, 2, '.', ''),
                    number_format((float) $correction->corrected_total, 2, '.', ''),
                    number_format($difference, 2, '.', ''),
                    $correction->payment_adjustment_status,
                    $correction->payment_adjustment_type,
                    number_format($confirmedAmount, 2, '.', ''),
                    $correction->adjustedBy?->name ?? '',
                    optional($correction->payment_adjusted_at)->format('Y-m-d H:i:s'),
                    $correction->payment_adjustment_note,
                    json_encode(data_get($correction->before_snapshot, 'items', []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    json_encode(data_get($correction->after_snapshot, 'items', []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** Render a dedicated, readable before/after receipt-correction report. */
    public function pdf(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $corrections = $this->filteredQuery($filters)
            ->with(['order.customer', 'order.cashier', 'createdBy', 'adjustedBy'])
            ->orderByDesc('created_at')
            ->orderByDesc('correction_id')
            ->get();

        $normaliseItems = static function ($items): array {
            return collect(is_array($items) ? $items : [])->filter(fn ($line) => is_array($line))->map(function ($line): array {
                $quantity = max(0, (int) ($line['quantity'] ?? 0));
                $unitPrice = max(0, (float) ($line['unit_price'] ?? $line['price'] ?? 0));
                $discountAmount = array_key_exists('discount_amount', $line)
                    ? max(0, (float) $line['discount_amount'])
                    : max(0, round($quantity * $unitPrice * ((float) ($line['discount_percent'] ?? 0) / 100), 2));
                $lineSubtotal = round($quantity * $unitPrice, 2);

                return [
                    'product_name' => trim((string) ($line['product_name'] ?? 'Product')) ?: 'Product',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountAmount,
                    'line_total' => max(0, round($lineSubtotal - $discountAmount, 2)),
                ];
            })->values()->all();
        };

        $records = $corrections->map(function ($correction) use ($normaliseItems): array {
            $difference = round((float) $correction->difference_amount, 2);
            $status = (string) ($correction->payment_adjustment_status ?? 'not_required');
            $type = (string) ($correction->payment_adjustment_type ?? '');
            $confirmedAmount = $status === 'settled' ? abs($difference) : 0.0;
            $beforeSnapshot = (array) ($correction->before_snapshot ?? []);
            $afterSnapshot = (array) ($correction->after_snapshot ?? []);

            return [
                'id' => (int) $correction->correction_id,
                'order_id' => (int) $correction->order_id,
                'created_at' => optional($correction->created_at)->format('d M Y · h:i A') ?? '—',
                'customer_before' => data_get($beforeSnapshot, 'customer.name') ?: 'Walk-in customer',
                'customer_after' => data_get($afterSnapshot, 'customer.name') ?: 'Walk-in customer',
                'cashier' => $correction->order?->cashier?->name ?? 'Not recorded',
                'corrected_by' => $correction->createdBy?->name
                    ?? ($correction->created_by ? 'Staff #'.$correction->created_by : 'Unknown staff'),
                'reason' => ucwords(str_replace('_', ' ', (string) $correction->reason)),
                'notes' => trim((string) ($correction->notes ?? '')),
                'before_items' => $normaliseItems(data_get($beforeSnapshot, 'items', [])),
                'after_items' => $normaliseItems(data_get($afterSnapshot, 'items', [])),
                'original_total' => (float) $correction->original_total,
                'corrected_total' => (float) $correction->corrected_total,
                'difference' => $difference,
                'adjustment_status' => $status,
                'adjustment_type' => $type,
                'adjustment_note' => trim((string) ($correction->payment_adjustment_note ?? '')),
                'confirmed_amount' => $confirmedAmount,
                'confirmed_by' => $correction->adjustedBy?->name ?? '—',
                'confirmed_at' => optional($correction->payment_adjusted_at)->format('d M Y · h:i A') ?: '—',
            ];
        })->values();

        $originalTotal = (float) $corrections->sum('original_total');
        $correctedTotal = (float) $corrections->sum('corrected_total');
        $differenceTotal = (float) $corrections->sum('difference_amount');
        $confirmedTotal = (float) $corrections->sum(function ($correction) {
            return $correction->payment_adjustment_status === 'settled'
                ? abs((float) $correction->difference_amount)
                : 0;
        });
        $cashCollected = (float) $corrections->filter(fn ($row) =>
            $row->payment_adjustment_status === 'settled' && $row->payment_adjustment_type === 'collected'
        )->sum('difference_amount');
        $cashRefunded = (float) $corrections->filter(fn ($row) =>
            $row->payment_adjustment_status === 'settled' && $row->payment_adjustment_type === 'refunded'
        )->sum(fn ($row) => abs((float) $row->difference_amount));
        $affectedReceipts = $corrections->pluck('order_id')->unique()->count();

        $staffLabel = $filters['staff_id']
            ? (User::query()->where('user_id', $filters['staff_id'])->value('name') ?: 'Staff #'.$filters['staff_id'])
            : 'All staff';
        $dateLabel = ($filters['date']['from'] || $filters['date']['to'])
            ? (($filters['date']['from'] ?: 'Beginning').' to '.($filters['date']['to'] ?: 'Today'))
            : 'All dates';
        $filterSummary = collect([
            'Date: '.$dateLabel,
            'Staff: '.$staffLabel,
            $filters['adjustment_status'] ? 'Adjustment status: '.ucwords(str_replace('_', ' ', $filters['adjustment_status'])) : null,
            $filters['adjustment_type'] ? 'Cash action: '.ucwords($filters['adjustment_type']) : null,
            $filters['search'] !== '' ? 'Search: '.$filters['search'] : null,
        ])->filter()->implode(' · ');

        return view('admin.exports.audit-print', [
            'reportType' => 'corrections',
            'title' => 'Receipt Correction Audit',
            'records' => $records,
            'totalRows' => $records->count(),
            'summaryItems' => [
                'Correction events' => number_format($corrections->count()),
                'Receipts affected' => number_format($affectedReceipts),
                'Original value' => '$'.number_format($originalTotal, 2),
                'Corrected value' => '$'.number_format($correctedTotal, 2),
                'Net difference' => ($differenceTotal > 0 ? '+' : '').'$'.number_format($differenceTotal, 2),
                'Confirmed cash movement' => '$'.number_format($confirmedTotal, 2),
                'Cash collected' => '$'.number_format($cashCollected, 2),
                'Cash refunded' => '$'.number_format($cashRefunded, 2),
            ],
            'generatedAt' => now(),
            'filterSummary' => $filterSummary ?: 'All receipt correction records',
            'shopSettings' => app('shop.settings'),
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:160'],
            'staff_id' => ['nullable', 'integer', 'exists:users,user_id'],
            'adjustment_status' => ['nullable', Rule::in(['not_required', 'pending', 'settled'])],
            'adjustment_type' => ['nullable', Rule::in(['collected', 'refunded'])],
        ]);

        return [
            'date' => AdminDateFilter::resolve($request, 'start_date', 'end_date'),
            'search' => trim((string) ($validated['search'] ?? '')),
            'staff_id' => $validated['staff_id'] ?? null,
            'adjustment_status' => $validated['adjustment_status'] ?? null,
            'adjustment_type' => $validated['adjustment_type'] ?? null,
        ];
    }

    private function filteredQuery(array $filters): Builder
    {
        $query = OrderCorrection::query();
        AdminDateFilter::apply($query, 'created_at', $filters['date']);

        if (! empty($filters['staff_id'])) {
            $query->where('created_by', (int) $filters['staff_id']);
        }

        if (! empty($filters['adjustment_status'])) {
            $query->where('payment_adjustment_status', $filters['adjustment_status']);
        }

        if (! empty($filters['adjustment_type'])) {
            $query->where('payment_adjustment_type', $filters['adjustment_type']);
        }

        $search = $filters['search'];
        if ($search !== '') {
            $query->where(function (Builder $nested) use ($search) {
                if (ctype_digit($search)) {
                    $nested->where('correction_id', (int) $search)
                        ->orWhere('order_id', (int) $search);
                }

                $nested->orWhere('reason', 'like', '%'.$search.'%')
                    ->orWhere('notes', 'like', '%'.$search.'%')
                    ->orWhere('payment_adjustment_note', 'like', '%'.$search.'%')
                    ->orWhereHas('createdBy', fn (Builder $staff) => $staff->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('adjustedBy', fn (Builder $staff) => $staff->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('order.customer', function (Builder $customer) use ($search) {
                        $customer->where('name', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%');
                    });
            });
        }

        return $query;
    }
}
