{{-- Sariq va qizil obuna bannerlaridagi aloqa qatori (kulrangda yo'q). Aloqa: config/chorvadorim.php --}}
<div class="obn-banner-contact">
    {{ __("Bog'lanish:") }}
    <a href="tel:{{ config('chorvadorim.contact.phone') }}">{{ config('chorvadorim.contact.phone_display') }}</a>
    <span aria-hidden="true">·</span>
    <a href="https://t.me/{{ config('chorvadorim.contact.telegram') }}" target="_blank" rel="noopener">{{ '@' . config('chorvadorim.contact.telegram') }}</a>
</div>
