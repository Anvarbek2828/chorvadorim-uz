{{-- Admin sahifalari sarlavhasi va bo'limlar menyusi. $title — sahifa nomi. --}}
<style>
    .adm-scroll { overflow-x: auto; }
    .adm-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    .adm-table th, .adm-table td { padding: .5rem .75rem; text-align: left; border-bottom: 1px solid #e5e7eb; vertical-align: top; white-space: nowrap; }
    .adm-table th { font-weight: 600; color: #4b5563; background: #f9fafb; }
    .adm-table .adm-num { text-align: right; }
    .adm-table .adm-wrap { white-space: normal; min-width: 14rem; }
    .adm-tabs { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .75rem; }
    .adm-tab { display: inline-block; padding: .375rem .75rem; border-radius: .375rem; font-size: .875rem; color: #4b5563; }
    .adm-tab:hover { background: #f3f4f6; }
    .adm-tab-active, .adm-tab-active:hover { background: #1f2937; color: #fff; }
    .adm-stat { font-size: 1.5rem; line-height: 2rem; font-weight: 600; }
    .adm-input { padding: .25rem .5rem; font-size: .875rem; border-radius: .375rem; border: 1px solid #d1d5db; max-width: 100%; }
    .adm-bar { height: .5rem; border-radius: 9999px; background: #16a34a; }
    .adm-badge { display: inline-block; padding: .125rem .5rem; border-radius: .375rem; font-size: .75rem; font-weight: 600; }
    .adm-badge-faol { background: #dcfce7; color: #15803d; }
    .adm-badge-imtiyoz { background: #fef3c7; color: #92400e; }
    .adm-badge-faqat_oqish { background: #fee2e2; color: #b91c1c; }
    .adm-badge-muddatsiz { background: #dbeafe; color: #1d4ed8; }
    .adm-badge-free { background: #f3f4f6; color: #4b5563; }
    .adm-pre { white-space: pre-wrap; word-break: break-word; font-size: .8125rem; }
    html.dark .adm-table th { background: #273548; color: #cbd5e1; }
    html.dark .adm-table th, html.dark .adm-table td { border-color: #3e4c63; }
    html.dark .adm-tab { color: #cbd5e1; }
    html.dark .adm-badge { background: #273548 !important; color: #e2e8f0 !important; }
    html.dark .adm-tab:hover { background: #273548; }
    html.dark .adm-tab-active, html.dark .adm-tab-active:hover { background: #334155; color: #f8fafc; }
</style>

<h2 class="font-semibold text-xl text-gray-800 leading-tight">
    {{ __('Admin panel') }} — {{ $title }}
</h2>

<nav class="adm-tabs">
    @foreach ([
        'admin.dashboard' => __('Bosh sahifa'),
        'admin.users.index' => __('Foydalanuvchilar'),
        'admin.ai-costs' => __('AI xarajatlari'),
        'admin.plans.index' => __('Tariflar'),
        'admin.payments.index' => __("To'lovlar"),
        'admin.audit-log' => __('Audit jurnali'),
    ] as $routeName => $label)
        <a href="{{ route($routeName) }}" class="adm-tab {{ request()->routeIs($routeName) ? 'adm-tab-active' : '' }}">{{ $label }}</a>
    @endforeach
</nav>
