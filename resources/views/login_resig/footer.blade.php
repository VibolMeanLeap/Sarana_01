<script src="{{ asset('../../assets/vendors/js/vendor.bundle.base.js') }}"></script>
<script src="{{ asset('../../assets/vendors/js/vendor.bundle.addons.js') }}"></script>
<!-- endinject -->
<!-- inject:js -->
<script src="{{ asset('../../assets/js/shared/off-canvas.js') }}"></script>
<script src="{{ asset('../../assets/js/shared/misc.js') }}"></script>
<!-- endinject -->
<script src="{{ asset('../../assets/js/shared/jquery.cookie.js') }}" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- Password visibility is handled once by the login form toggle. -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
    // Find all session toasts on the page
    const toastElements = document.querySelectorAll('.js-auto-toast');
    
    toastElements.forEach(function (toastEl) {
    // Initialize Bootstrap toast with a 0.5s auto-hide delay
    const toast = new bootstrap.Toast(toastEl, {
    autohide: true,
    delay: 5000 // 500 milliseconds = 0.5 seconds
    });
    
    // Show the toast
    toast.show();
    });
    });
</script>
<script>window.CoffeeCornerValidationErrors = @json($errors->toArray());</script>
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
</body>

</html>