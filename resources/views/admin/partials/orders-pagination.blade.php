<div class="orders-pagination">
  <div class="orders-pagination-info">
    Showing {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} of {{ number_format($orders->total()) }} orders
  </div>
  {{ $orders->links('pagination::bootstrap-5') }}
</div>
