<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11">

    @if (Session::has('user_status'))
    <div class="toast js-auto-toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <strong>Success:</strong> {{ session('user_status') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                aria-label="Close"></button>
        </div>
    </div>

    @elseif(Session::has('delete_success'))
    <div class="toast js-auto-toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <strong>Delete:</strong> {{ session('delete_success') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                aria-label="Close"></button>
        </div>
    </div>

    @elseif(Session::has('update_success'))
    <div class="toast js-auto-toast align-items-center text-bg-warning border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <strong>Update:</strong> {{ session('update_success') }}
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    @elseif(Session::has('success_login'))
    <div class="toast js-auto-toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <strong>Welcome:</strong> {{ session('success_login') }}
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
{{-- Supplier message --}}

    @elseif(Session::has('supplier_status'))
    <div class="toast js-auto-toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <strong>Success:</strong> {{ session('supplier_status') }}
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    @elseif(Session::has('delete_supplier'))
    <div class="toast js-auto-toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <strong>Delete:</strong> {{ session('delete_supplier') }}
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    @elseif(Session::has('supplier_update'))
    <div class="toast js-auto-toast align-items-center text-bg-warning border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <strong>Update:</strong> {{ session('supplier_update') }}
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    @elseif(Session::has('error'))
    <div class="toast js-auto-toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <strong>Error:</strong> {{ session('error') }}
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    @endif

</div>