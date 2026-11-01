{{-- Import sahifalari uchun umumiy: jadval uslublari, admin rejimida kimning nomidan yuklanayotgani va xabarlar --}}
<style>
    .imp-scroll { overflow-x: auto; }
    .imp-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    .imp-table th, .imp-table td { padding: .5rem .75rem; text-align: left; border-bottom: 1px solid #e5e7eb; vertical-align: top; white-space: nowrap; }
    .imp-table th { font-weight: 600; color: #4b5563; background: #f9fafb; }
    .imp-table .imp-wrap { white-space: normal; min-width: 16rem; }
    .imp-table .imp-bad { color: #b91c1c; font-weight: 600; }
    .imp-stat { font-size: 1.5rem; line-height: 2rem; font-weight: 600; }
    .imp-steps { list-style: decimal; padding-left: 1.25rem; }
    html.dark .imp-table th { background: #273548; color: #cbd5e1; }
    html.dark .imp-table th, html.dark .imp-table td { border-color: #3e4c63; }
    html.dark .imp-table .imp-bad { color: #fca5a5; }
</style>

@if ($adminMode)
    <div class="bg-blue-50 text-blue-700 p-3 rounded">
        {{ __('Foydalanuvchi nomidan yuklash') }}: <strong>{{ $target->name }}</strong>
        <span class="text-sm">(#{{ $target->id }} · {{ $target->email }})</span>
        <div class="text-sm mt-1">{{ __('Hayvonlar shu foydalanuvchiga qo\'shiladi. Amal audit jurnaliga yoziladi.') }}</div>
    </div>
@endif

@if (session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="bg-red-100 text-red-700 p-3 rounded">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="bg-red-100 text-red-700 p-3 rounded">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
