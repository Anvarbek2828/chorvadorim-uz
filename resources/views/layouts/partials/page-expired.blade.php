{{-- 419 dan keyingi xabar (App\Support\PageExpired). Tailwind klasslarisiz — build o'zgarmasin. --}}
@if (session(\App\Support\PageExpired::FLASH_KEY))
    <div role="alert" class="chv-page-expired" style="box-sizing:border-box;width:100%;max-width:42rem;margin:.75rem auto;padding:.75rem 1rem;border:1px solid #F59E0B;border-radius:.625rem;background:#FEF3C7;color:#78350F;font-size:.9375rem;line-height:1.45;font-weight:600;text-align:center;">
        <span style="color:#78350F;">{{ session(\App\Support\PageExpired::FLASH_KEY) }}</span>
    </div>
@endif
