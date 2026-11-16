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
