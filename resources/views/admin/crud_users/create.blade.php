@extends('layout.admin')
@section('content')
<div class="d-flex justify-content-center align-items-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="card-title">Add Users</div>
                <hr>
                <form id="fromCreateUser" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control form-control-rounded" id="name" name="name"
                            placeholder="Enter Your Name">
                        <p class="invalid-feedback m-0"></p>
                    </div>

                    <div class="form-group">
                        <label for="role_id">Choose Role</label>
                        <select id="role_id" name="role_id" class="form-control">
                            @foreach ($role as $r)
                            <option value="{{ $r->role_id }}">{{ $r->role_name }}</option>
                            @endforeach
                        </select>
                        <p class="invalid-feedback m-0"></p>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" class="form-control form-control-rounded" id="phone" name="phone"
                            placeholder="Enter Your Mobile Number">
                        <p class="invalid-feedback m-0"></p>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control form-control-rounded" id="username" name="username"
                            placeholder="Enter username">
                        <p class="invalid-feedback m-0"></p>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control form-control-rounded" id="email" name="email"
                            placeholder="Enter Your Email">
                        <p class="invalid-feedback m-0"></p>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control form-control-rounded" id="password" name="password"
                            placeholder="Enter Your Password">
                        <p class="invalid-feedback m-0"></p>
                    </div>

                    <div class="form-group py-2">
                        <div class="icheck-material-white">
                            <input type="hidden" name="status" value="inactive">
                            <input type="checkbox" id="status" name="status" value="active" checked />
                            <label for="status">Status (Active)</label>
                        </div>
                        <p class="invalid-feedback m-0"></p>
                    </div>

                    <div class="">
                        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); StoreUser(this)" id="submituser"
                            class="btn btn-primary btn-round px-5">
                            Create
                        </button>
                        <a href="{{ route('auth.list') }}" class="btn btn-sm btn-light">Cancel</a>
                        
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const StoreUser = (buttonElement) => {
        let $form = $(buttonElement).closest('form');
        let payloads = new FormData($form[0]); 
        $(buttonElement).prop('disabled', true).text('Creating...');

        $.ajax({
            type: "POST",
            url: "{{ route('auth.store') }}",
            data: payloads,
            dataType: "json",
            contentType: false,
            processData: false,
            success: function (response) {
                $(buttonElement).prop('disabled', false).text('Create');
                $form.find('.form-control').removeClass('is-invalid');
                $form.find('.invalid-feedback').removeClass('text-danger').text('');
                if (response.status == 201 || response.status == 200) {
                    $form.trigger('reset');
                    window.location.href = "{{ route('auth.list') }}";
                }
            },
            error: function (xhr) {
                $(buttonElement).prop('disabled', false).text('Create');
                $form.find('.form-control').removeClass('is-invalid');
                $form.find('.invalid-feedback').removeClass('text-danger').text('');

                if (xhr.status === 422 || (xhr.responseJSON && xhr.responseJSON.errors)) {
                    let errors = xhr.responseJSON.errors;
                    
                    $.each(errors, function (key, value) {
                        let inputField = $form.find(`[name="${key}"]`);
                        inputField.addClass('is-invalid');
                        inputField.siblings('.invalid-feedback').addClass('text-danger').text(value[0] || value);
                    });
                } else {
                   
                    alert('Server error (500). Please check your storage/logs/laravel.log file.');
                    console.error(xhr.responseText);
                }
            }
        });
    }
</script>
@endsection