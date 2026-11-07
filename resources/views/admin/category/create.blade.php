@extends('layout.admin')

@section('content')

<style>
    .cat-page-bg {
        background: #f3f4f6;
        min-height: 100vh;
        padding: 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .cat-form-wrap {
        width: 100%;
        max-width: 720px;
    }

    .cat-form-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 16px;
        flex-wrap: wrap;
    }

    .cat-form-title {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }

    .btn-back-list {
        background: #fff;
        border: 1px solid #e5e7eb;
        color: #374151;
        padding: 9px 16px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background .15s ease;
    }
    .btn-back-list:hover {
        background: #f9fafb;
        color: #111827;
    }

    .cat-card {
        background: #fff;
        border-radius: 14px;
        border: none;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        padding: 24px;
    }

    .cat-form-alert {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
        border-radius: 10px;
        padding: 14px 16px;
        font-size: 13.5px;
        margin-bottom: 20px;
    }
    .cat-form-alert ul {
        margin: 0;
        padding-left: 18px;
    }

    .cat-form-group {
        margin-bottom: 18px;
    }
    .cat-form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }
    .cat-form-control {
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 14px;
        color: #111827;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .cat-form-control:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    textarea.cat-form-control {
        resize: vertical;
        min-height: 100px;
    }

    .cat-form-actions {
        display: flex;
        gap: 10px;
        margin-top: 24px;
    }

    .btn-cancel {
        background: #fff;
        border: 1px solid #e5e7eb;
        color: #374151;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    .btn-cancel:hover {
        background: #f9fafb;
        color: #111827;
    }

    .btn-save {
        background: #2563eb;
        border: none;
        color: #fff;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background .15s ease;
    }
    .btn-save:hover {
        background: #1d4ed8;
    }
</style>

<div class="cat-page-bg">

    <div class="cat-form-wrap">

        <div class="cat-form-topbar">

            <h4 class="cat-form-title ">Create New Category</h4>
           
        </div>

        <div class="cat-card">

            @if ($errors->any())
                <div class="cat-form-alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('categories.store') }}" method="POST">
                @csrf

                <div class="cat-form-group">
                    <label for="category_name" class="cat-form-label">Category Name</label>
                    <input type="text" name="category_name" id="category_name" class="cat-form-control" placeholder="Enter category name" value="{{ old('category_name') }}" required>
                </div>

                <div class="cat-form-group">
                    <label for="description" class="cat-form-label">Description</label>
                    <textarea name="description" id="description" class="cat-form-control" rows="4" placeholder="Enter description">{{ old('description') }}</textarea>
                </div>

                <div class="cat-form-actions d-flex justify-content-center align-items-center gap-3 mt-3">
                    <a href="{{ route('categories.index') }}" class="btn-cancel">
                        Cancel
                    </a>

                    <button type="submit" class="btn-save">
                        <i class="fa fa-save"></i> Save Category
                    </button>
                </div>
            </form>

        </div>

    </div>

</div>

@endsection