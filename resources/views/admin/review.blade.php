@extends('layout.admin')

@section('content')
<div class="card mb-3">
    <div class="card-body d-flex align-items-center gap-2">
        <h5 class="mb-0">Average Rating:</h5>
        <span class="fs-4 text-warning">
            @for($i = 1; $i <= 5; $i++)
                <i class="bi bi-star{{ $i <= round($avgRating) ? '-fill' : '' }}"></i>
            @endfor
        </span>
        <span class="text-muted">({{ $avgRating }} / 5)</span>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Customer Reviews</h5>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @include('admin.partials.export-actions', ['exportType' => 'reviews'])
            <a href="{{ route('reviews.create') }}" class="btn btn-cc btn-sm"><i class="bi bi-plus-lg me-1"></i>Add review</a>
        </div>
        <form method="GET" action="{{ route('reviews.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search customer, order, comment">
            <select name="rating" class="form-select form-select-sm">
                <option value="">All ratings</option>
                @for($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" @selected(request('rating') == $i)>{{ $i }} star</option>
                @endfor
            </select>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm" aria-label="From date">
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm" aria-label="To date">
            <select name="sort_dir" class="form-select form-select-sm" aria-label="Sort by date"><option value="desc" @selected(request('sort_dir', 'desc') === 'desc')>Newest first</option><option value="asc" @selected(request('sort_dir') === 'asc')>Oldest first</option></select>
            <button class="btn btn-outline-secondary btn-sm" type="submit">Filter</button>
            <a href="{{ route('reviews.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </form>
    </div>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Order date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $r)
                    <tr>
                        <td>{{ $r->review_id }}</td>
                        <td>#{{ $r->order_id }}</td>
                        <td>{{ $r->customer->name ?? '-' }}</td>
                        <td>
                            @for($i = 1; $i <= 5; $i++)
                                <i class="bi bi-star{{ $i <= $r->rating ? '-fill' : '' }} text-warning"></i>
                            @endfor
                        </td>
                        <td>{{ $r->comment }}</td>
                        <td>{{ optional($r->order?->order_date)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="text-end">
                            <form action="{{ route('reviews.destroy', $r->review_id) }}" method="POST" onsubmit="return confirm('Remove this review?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No reviews yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{ $reviews->links() }}
    </div>
</div>
@endsection