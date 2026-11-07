@extends('layout.admin')

@section('content')

<style>
    .cat-page-bg {
        background: #f3f4f6;
        min-height: 100vh;
        padding: 24px;
    }

    .cat-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 16px;
        flex-wrap: wrap;
    }

    .cat-page-title {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }

    .btn-add-category {
        background: #2563eb;
        border: none;
        color: #fff;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background .15s ease;
    }
    .btn-add-category:hover {
        background: #1d4ed8;
        color: #fff;
    }

    .cat-card {
        background: #fff;
        border-radius: 14px;
        border: none;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        padding: 24px;
    }

    .cat-search-form {
        display: flex;
        justify-content: flex-end;
        gap: 0;
        margin-bottom: 18px;
    }
    .cat-search-form .form-control {
        border-radius: 8px 0 0 8px;
        border: 1px solid #e5e7eb;
        font-size: 14px;
        height: 42px;
        flex: 0 0 auto;
        width: 260px;
        max-width: 260px;
    }
    .cat-search-form .btn-search {
        border-radius: 0 8px 8px 0;
        background: #2563eb;
        border: 1px solid #2563eb;
        color: #fff;
        font-size: 14px;
        padding: 0 20px;
        height: 42px;
        flex: 0 0 auto;
    }
    .cat-search-form .btn-search:hover { background: #1d4ed8; }
    .cat-search-form .btn-clear {
        border-radius: 8px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        color: #374151;
        font-size: 14px;
        padding: 0 16px;
        height: 42px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        margin-left: 8px;
        white-space: nowrap;
    }
    .cat-search-form .btn-clear:hover { background: #e5e7eb; color: #111827; }

    .cat-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    .cat-table thead th {
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .03em;
        padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
        background: transparent;
    }
    .cat-table tbody td {
        padding: 14px 12px;
        font-size: 14px;
        color: #111827;
        border-bottom: 1px solid #f1f2f4;
        vertical-align: middle;
    }
    .cat-table tbody tr:hover {
        background: #f9fafb;
    }

    /* Icon-style action buttons (matching Order List / Order Item List / Product List design) */
    .cat-action-group { display: flex; align-items: center; gap: 6px; }
    .btn-icon-edit,
    .btn-icon-delete {
        background: transparent;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        padding: 0;
    }
    .btn-icon-edit { color: #d97706; }
    .btn-icon-edit:hover { background: #fef3c7; color: #b45309; }
    .btn-icon-delete { color: #dc2626; }
    .btn-icon-delete:hover { background: #fee2e2; color: #b91c1c; }

    .class-pagination nav p { display: none !important; }
    .class-pagination {
        display: flex;
        justify-content: flex-end;
        margin-top: 18px;
    }
    .class-pagination .pagination {
        gap: 4px;
    }
    .class-pagination .page-link {
        border-radius: 8px !important;
        border: 1px solid #e5e7eb;
        color: #374151;
        font-size: 13px;
        min-width: 32px;
        text-align: center;
    }
    .class-pagination .page-item.active .page-link {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }
</style>

<div class="cat-page-bg">

    <div class="cat-topbar">
        <h2 class="cat-page-title">Category List</h2>

        <a href="{{ route('categories.create') }}" class="btn-add-category">
            <i class="fa fa-plus"></i> Add New Category
        </a>
    </div>

    <div class="cat-card">

        <form action="{{ route('categories.index') }}" method="GET" class="cat-search-form">
            <input type="text" name="search" class="form-control" placeholder="Search category..." value="{{ request('search') }}">
            <button class="btn-search" type="submit">Search</button>

            @if(request('search'))
                <a href="{{ route('categories.index') }}" class="btn-clear">Cancel</a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="cat-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th style="width:90px; text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $row)
                        <tr>
                            <td>{{ $row->category_id }}</td>
                            <td>{{ $row->category_name }}</td>
                            <td>{{ $row->description }}</td>
                            <td>
                                <div class="cat-action-group">
                                    <a href="{{ route('categories.edit', $row->category_id) }}" class="btn-icon-edit" title="Edit category">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </a>

                                    <form action="{{ route('categories.destroy', $row->category_id) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn-icon-delete btn-delete" title="Delete category">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                                <path d="M10 11v6"></path>
                                                <path d="M14 11v6"></path>
                                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="class-pagination">
            {{ $categories->links('pagination::simple-bootstrap-5') }}
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // 1. សម្រាប់បង្ហាញការជូនដំណឹង (Create / Edit / Delete ជោគជ័យ) រក្សាទុក 3 វិនាទី
        @if(session('success'))
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: 'success',
                title: "{{ session('success') }}"
            });
        @endif

        // 2. សម្រាប់បង្ហាញផ្ទាំងសួរមុននឹងលុប (SweetAlert2 Confirm Dialog)
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const form = this.closest('.delete-form');

                Swal.fire({
                     title: 'Are you sure?',
                    text: "Do you want delete Category !",
                    icon: 'delete',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit(); // បញ្ជូន Form ទៅលុបក្នុង Database បើចុច Yes
                    }
                });
            });
        });
    });
</script>

@endsection