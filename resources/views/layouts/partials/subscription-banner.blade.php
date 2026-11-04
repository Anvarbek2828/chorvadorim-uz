{{-- Obuna holati banneri (yopib bo'lmaydi): imtiyoz — sariq (ega/menejer), cheklangan — qizil (ega/menejer) yoki kulrang (boshqalar). Admin sahifalarida yo'q --}}
@php
    // Admin panel (/admin/...) obuna cheklovidan tashqarida: u yerda bannerlar ko'rsatilmaydi
    $bannerFarm = request()->routeIs('admin.*') ? null : Auth::user()?->activeFarm();
    $bannerSubscriptions = app(\App\Services\SubscriptionService::class);
    $bannerStatus = $bannerFarm ? $bannerSubscriptions->status($bannerFarm) : null;
    $bannerManages = Gate::allows('plan.view');
    $bannerError = session(\App\Http\Middleware\EnsureFarmWritable::FLASH);
@endphp

<style>
    .obn-banner { padding: .625rem 1rem; text-align: center; font-size: .875rem; font-weight: 600; }
    .obn-banner-sariq { background: #fef3c7; color: #92400e; border-bottom: 1px solid #fcd34d; }
    .obn-banner-qizil { background: #dc2626; color: #fff; }
    .obn-banner-kulrang { background: #e5e7eb; color: #374151; font-weight: 500; }
    .obn-flash { max-width: 80rem; margin: 1rem auto 0; padding: .75rem 1rem; border-radius: .375rem; background: #fee2e2; color: #b91c1c; font-size: .875rem; }
    html.dark .obn-banner-sariq, html.dark .obn-banner-sariq * { background: #3a2313; color: #fde68a !important; border-color: #78350f; }
    html.dark .obn-banner-qizil, html.dark .obn-banner-qizil * { background: #991b1b; color: #fff !important; }
    html.dark .obn-banner-kulrang, html.dark .obn-banner-kulrang * { background: #334155; color: #e2e8f0 !important; }
    html.dark .obn-flash, html.dark .obn-flash * { background: #3f1d2b; color: #fca5a5 !important; }
</style>

@if ($bannerStatus === \App\Services\SubscriptionService::STATUS_GRACE && $bannerManages)
    <div class="obn-banner obn-banner-sariq" role="alert">
        {{ __("Obunangiz muddati tugadi. Iltimos, to'lovni amalga oshiring. Agar :days kun ichida to'lov qilinmasa, saytdagi faoliyatingizni to'lov qilinguncha cheklashga majbur bo'lamiz.", ['days' => $bannerSubscriptions->daysUntilReadOnly($bannerFarm)]) }}
    </div>
@elseif ($bannerStatus === \App\Services\SubscriptionService::STATUS_READ_ONLY)
    @if ($bannerManages)
        <div class="obn-banner obn-banner-qizil" role="alert">
            {{ __("To'lov qilinmagani sababli saytdagi faoliyatingiz cheklandi. Ma'lumotlaringizni ko'rishingiz mumkin, lekin yangi ma'lumot kiritib bo'lmaydi. To'lov qilinishi bilan hammasi darhol tiklanadi.") }}
        </div>
    @else
        <div class="obn-banner obn-banner-kulrang" role="status">
            {{ __("Fermada yangi ma'lumot kiritish vaqtincha cheklangan. Batafsil ma'lumot uchun ferma egasiga murojaat qiling.") }}
        </div>
    @endif
@endif

@if ($bannerError)
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="obn-flash" role="alert">{{ $bannerError }}</div>
    </div>
@endif
