{{-- Har sahifa pastidagi yordam qatori (asosiy va guest layout). Aloqa: config/chorvadorim.php --}}
@php
    $supportTelegramUrl = 'https://t.me/' . config('chorvadorim.contact.telegram');
@endphp

<style>
    .chv-support { width: 100%; margin-top: 2rem; padding: 1rem 1rem 1.25rem; border-top: 1px solid #e5e7eb; text-align: center; font-size: .8125rem; line-height: 1.6; color: #6b7280; }
    .chv-support__link { display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .125rem; color: #0284c7; font-weight: 600; text-decoration: none; white-space: nowrap; }
    .chv-support__link:hover, .chv-support__link:focus { color: #0369a1; text-decoration: underline; }
    .chv-support__icon { width: 1.125rem; height: 1.125rem; flex-shrink: 0; }
    html.dark .chv-support { border-color: #3e4c63; color: #94a3b8; }
    html.dark .chv-support span { color: #94a3b8; }
    html.dark .chv-support .chv-support__link, html.dark .chv-support .chv-support__link span { color: #7dd3fc; }
</style>

<footer class="chv-support">
    <span>{{ __("Yordam kerak bo'lsa, Telegram orqali yozing:") }}</span>
    <a href="{{ $supportTelegramUrl }}" target="_blank" rel="noopener" class="chv-support__link">
        <svg class="chv-support__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <circle cx="12" cy="12" r="12" fill="#229ED9"/>
            <path fill="#fff" d="M5.4 11.8l11.3-4.4c.5-.2 1 .1.8.9l-1.9 9c-.1.6-.5.8-1 .5l-2.9-2.1-1.4 1.3c-.2.2-.3.3-.6.3l.2-3 5.4-4.9c.2-.2 0-.3-.3-.1l-6.7 4.2-2.9-.9c-.6-.2-.6-.6.1-.8z"/>
        </svg>
        <span>{{ __('Chorvadorim yordam xizmati') }}</span>
    </a>
    <span>{{ __('Imkon qadar tez javob beramiz.') }}</span>
</footer>
