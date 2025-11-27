@if ($errors->any())
<div class="alert alert-danger mb-3">
    <strong>Se encontraron algunos problemas:</strong>
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li class="text-sm">{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if (session('status'))
<div class="alert alert-success mb-3">
    {{ session('status') }}
</div>
@endif