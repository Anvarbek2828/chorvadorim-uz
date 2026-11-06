{{--
    PWA skripti va uning uslublari (</body> oldidan). Mantiq: public/pwa.js, service worker: public/sw.js.
    Admin sahifalarida "ilovani o'rnating" paneli chiqmaydi.
--}}
@php
    $pwaScript = public_path('pwa.js');
    $pwaConfig = [
        'sw' => '/sw.js',
        'icon' => asset('icons/icon-192.png'),
        'locale' => app()->getLocale(),
        'installPanel' => ! request()->is('admin', 'admin/*'),
        't' => [
            'offlineBar' => __("Internet yo'q — oxirgi saqlangan ma'lumot ko'rsatilmoqda"),
            'offlineForm' => __("Internet yo'q. Ma'lumot hozircha saqlanmaydi, internet tiklangach qayta yuboring."),
            'cachedForm' => __("Bu sahifa internetsiz ochilgan edi. Sahifani yangilang, so'ng qayta yuboring."),
            'update' => __('Yangi versiya tayyor'),
            'updateBtn' => __('Yangilash'),
            'installTitle' => __("Ilovani o'rnatish"),
            'installText' => __("Chorvadorim'ni ilova sifatida o'rnating — tezroq ochiladi"),
            'installBtn' => __("O'rnatish"),
            'later' => __('Keyinroq'),
            // iPhone/iPad: 3 qadam. ":share" va ":add" o'rniga pwa.js belgilarni qo'yadi.
            // Menyu bandi nomi («Добавить на экран «Домой»», «Add to Home Screen») tarjima qilinmaydi — telefon shunday ko'rsatadi.
            'iosStep1Other' => __("Tepada, «chorvadorim.uz» yozuvi yonidagi :share tugmasini bosing."),
            'iosStep1SafariPhone' => __('Ekran pastidagi :share tugmasini bosing.'),
            'iosStep1SafariPad' => __('Ekran tepasidagi :share tugmasini bosing.'),
            'iosStep2' => __("Ochilgan ro'yxatni pastga suring."),
            'iosStep3Other' => __(":add «Добавить на экран «Домой»» ni bosing (telefon inglizcha bo'lsa — «Add to Home Screen»)."),
            'iosStep3Safari' => __(':add «Добавить на экран «Домой»» ni bosing (inglizcha — «Add to Home Screen»).'),
            // Android: beforeinstallprompt bo'lmaganda brauzerga mos 3 qadam. ":menu" — uch nuqta, ":burger" — uch chiziq.
            // Menyu bandlari nomi ruscha (qavsda inglizcha) — tarjima qilinmaydi; versiyaga qarab farq qilgani uchun ikki variant.
            'android' => [
                'chrome' => [
                    __('Tepadagi :menu (uch nuqta) tugmasini bosing.'),
                    __("Ochilgan ro'yxatdan quyidagi bandni toping."),
                    __('«Установить приложение» yoki «Добавить на главный экран» ni bosing («Install app» / «Add to Home screen»).'),
                ],
                'yandex' => [
                    __('Pastdagi :menu tugmasini bosing.'),
                    __("Ochilgan ro'yxatni pastga suring."),
                    __('«Добавить ярлык» yoki «Добавить на главный экран» ni bosing.'),
                ],
                'samsung' => [
                    __('Pastdagi :burger (uch chiziq) tugmasini bosing.'),
                    __('«Добавить страницу на» ni tanlang («Add page to»).'),
                    __('«Главный экран» ni bosing («Home screen»).'),
                ],
                'xiaomi' => [
                    __('Pastdagi :burger yoki :menu tugmasini bosing.'),
                    __('«Добавить в» yoki «Добавить на рабочий стол» ni toping.'),
                    __("Qo'shishni tasdiqlang."),
                ],
                'opera' => [
                    __(':menu tugmasini bosing.'),
                    __("Ro'yxatdan «Добавить на» bandini toping."),
                    __('«Домашний экран» yoki «Главный экран» ni bosing («Home screen»).'),
                ],
                'firefox' => [
                    __(':menu tugmasini bosing.'),
                    __("Ochilgan ro'yxatdan quyidagi bandni toping."),
                    __('«Установить» yoki «Добавить на главный экран» ni bosing («Install» / «Add to Home screen»).'),
                ],
            ],
            'androidNote' => __("Brauzeringiz boshqacha bo'lsa — menyudan «главный экран» so'zini qidiring."),
        ],
    ];
@endphp
<style>
    [data-chv-install][hidden], .chv-toast[hidden] { display: none !important; }
    .chv-offline-bar { position: sticky; top: 0; z-index: 9990; padding: .375rem 1rem; background: #FDE68A; color: #422006 !important; font-size: .875rem; line-height: 1.4; text-align: center; font-weight: 600; }
    .chv-toast { position: fixed; left: 50%; bottom: 1rem; z-index: 9995; transform: translateX(-50%); width: calc(100% - 2rem); max-width: 26rem; padding: .875rem 1rem; border-radius: .75rem; background: #7C2D12; color: #fff !important; font-size: .9375rem; line-height: 1.45; box-shadow: 0 8px 24px rgba(0, 0, 0, .25); }
    .chv-update { position: fixed; left: 50%; top: .75rem; z-index: 9996; transform: translateX(-50%); display: flex; align-items: center; gap: .75rem; width: calc(100% - 2rem); max-width: 26rem; padding: .5rem .5rem .5rem 1rem; border-radius: .75rem; background: #1F2937; color: #fff; font-size: .9375rem; box-shadow: 0 8px 24px rgba(0, 0, 0, .25); }
    .chv-update span { flex: 1; color: #fff !important; }
    .chv-install { position: fixed; left: .75rem; right: .75rem; bottom: .75rem; z-index: 9980; display: flex; gap: .75rem; align-items: flex-start; max-width: 30rem; margin: 0 auto; padding: .875rem; border-radius: 1rem; background: #2F4B33; color: #fff; box-shadow: 0 10px 30px rgba(0, 0, 0, .3); }
    .chv-install__icon { flex-shrink: 0; width: 44px; height: 44px; border-radius: 10px; box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .25); }
    .chv-install__body { flex: 1; min-width: 0; }
    .chv-install__text { margin: 0; color: #fff !important; font-size: .9375rem; line-height: 1.4; font-weight: 600; }
    /* iPhone/iPad va Android: 3 qadamli ko'rsatma (ekranning yarmidan oshmaydi, kerak bo'lsa ichida suriladi) */
    .chv-install--ios, .chv-install--android { flex-wrap: wrap; align-items: center; max-height: 50vh; overflow-y: auto; -webkit-overflow-scrolling: touch; }
    .chv-install--ios .chv-install__icon, .chv-install--android .chv-install__icon { width: 36px; height: 36px; border-radius: 9px; }
    .chv-install--ios > .chv-install__steps, .chv-install--ios > .chv-install__actions,
    .chv-install--android > .chv-install__steps, .chv-install--android > .chv-install__note, .chv-install--android > .chv-install__actions { flex: 0 0 100%; }
    .chv-install--ios > .chv-install__actions, .chv-install--android > .chv-install__actions { margin-top: 0; }
    .chv-install__note { margin: 0; color: #E3EBDC !important; font-size: .8125rem; line-height: 1.4; }
    /* Android: matnlar uzunroq — ixchamroq qadamlar, "Keyinroq" tugmasi panel pastida doim ko'rinadi */
    .chv-install--android { gap: .375rem .625rem; padding: .75rem; }
    .chv-install--android .chv-install__steps { gap: .3125rem; }
    .chv-install--android .chv-install__step { padding: .3125rem .5rem; font-size: .9rem; line-height: 1.4; }
    .chv-install--android > .chv-install__actions { position: sticky; bottom: -.75rem; margin: 0 -.75rem -.75rem; padding: .5rem .75rem .75rem; background: #2F4B33; }
    .chv-install__steps { list-style: none; margin: 0; padding: 0; display: grid; gap: .5rem; }
    .chv-install__step { display: flex; gap: .625rem; align-items: flex-start; margin: 0; padding: .5rem .625rem; border-radius: .625rem; background: rgba(255, 255, 255, .08); color: #fff !important; font-size: .9375rem; line-height: 1.5; }
    .chv-install__num { flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; width: 1.5rem; height: 1.5rem; margin-top: .0625rem; border-radius: 50%; background: #FAF6EE; color: #2F4B33 !important; font-size: .8125rem; font-weight: 700; line-height: 1; }
    .chv-install__step-text { flex: 1; min-width: 0; color: #fff !important; overflow-wrap: break-word; }
    .chv-step-icon { display: inline-block; box-sizing: border-box; width: 20px; height: 20px; margin: 0 .15em; vertical-align: -4px; padding: 1px; border-radius: 4px; background: rgba(255, 255, 255, .18); fill: none; stroke: #fff; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .chv-step-icon .chv-icon-dot { fill: #fff; stroke: none; }
    .chv-install__actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .625rem; }
    .chv-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 2.75rem; padding: .5rem 1rem; border-radius: .625rem; font: inherit; font-size: .9375rem; font-weight: 600; cursor: pointer; border: 2px solid transparent; }
    .chv-btn--light { background: #FAF6EE; color: #2F4B33 !important; }
    .chv-btn--ghost { background: transparent; color: #fff !important; border-color: rgba(255, 255, 255, .45); }
    .chv-btn:disabled { opacity: .6; cursor: default; }
    body.chv-has-install { padding-bottom: 8.5rem; }
</style>
<script type="application/json" id="chv-pwa-config">@json($pwaConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)</script>
<script src="{{ asset('pwa.js') }}?v={{ is_file($pwaScript) ? filemtime($pwaScript) : 1 }}" defer></script>
