@if (session('success'))
    <div class="mb-4 bg-green-100 text-green-700 p-3 rounded">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="mb-4 bg-red-100 text-red-700 p-3 rounded">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="mb-4 bg-red-100 text-red-700 p-3 rounded">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
