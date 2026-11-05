@php
    $locale = app()->getLocale();
    $htmlLang = ['uz' => 'uz', 'uz_cyrl' => 'uz-Cyrl', 'ru' => 'ru'][$locale] ?? 'uz';
    $ogLocale = $locale === 'ru' ? 'ru_RU' : 'uz_UZ';
    $languages = [
        'uz' => ['UZ', "O'zbekcha"],
        'uz_cyrl' => ['ЎЗБ', 'Ўзбекча'],
        'ru' => ['РУС', 'Русский'],
    ];

    $isAuth = auth()->check();
    $startUrl = $isAuth ? route('dashboard') : route('register');
    $startLabel = $isAuth ? __("Kabinetga o'tish") : __('Bepul boshlash');

    $botUrl = 'https://t.me/' . config('chorvadorim.telegram_bot');
    $contactPhone = config('chorvadorim.contact.phone');
    $contactPhoneDisplay = config('chorvadorim.contact.phone_display');
    $contactTelegram = config('chorvadorim.contact.telegram');
    $contactTelegramUrl = 'https://t.me/' . $contactTelegram;

    $pageTitle = __('Chorvadorim — chorvangiz hisobi telefoningizda');
    $pageDescription = __("Har bir hayvon, emlash, xarajat va daromad bir joyda. Eslatmalar Telegram orqali keladi, AI veterinar maslahat beradi. Bepul boshlash mumkin.");

    $problems = [
        ['icon' => 'notebook', 'problem' => __("Daftar yo'qoladi, yozuvlar chalkashib ketadi."), 'solution' => __("Hammasi internetda saqlanadi — telefon almashsa ham yo'qolmaydi.")],
        ['icon' => 'calendar', 'problem' => __('Emlash va davolash kuni esdan chiqadi.'), 'solution' => __("Vaqti kelganda Telegram'ga eslatma keladi.")],
        ['icon' => 'coins', 'problem' => __("Xarajat ko'p, lekin foyda bormi — noma'lum."), 'solution' => __("Har bir hayvonning foydasi yoki zarari aniq ko'rinadi.")],
    ];

    $features = [
        ['icon' => 'card', 'title' => __('Hayvon kartochkasi va pasport'), 'text' => __("Zoti, jinsi, yoshi, vazni, pasport va chip raqami. Yo'nalishi: sut, go'sht yoki nasl.")],
        ['icon' => 'bell', 'title' => __('Eslatmalar'), 'text' => __("Emlash va davolash kunini belgilang. Vaqti kelganda Telegram'ga xabar keladi, «Bajarildi» tugmasi bilan yopasiz.")],
        ['icon' => 'chart', 'title' => __('Xarajat va daromad'), 'text' => __("Ozuqa, dori va sotuvdan tushumni yozing — har bir hayvonning foydasi o'zi hisoblanadi.")],
        ['icon' => 'chat', 'title' => __('AI maslahat'), 'text' => __("Ozuqa, parvarish va kasallik haqida o'z tilingizda so'rang. Javob hayvon ma'lumotlarini hisobga oladi.")],
        ['icon' => 'camera', 'title' => __('Rasm tahlili'), 'text' => __("Hayvon rasmini yuklang — semizlik bahosi (1–5) va taxminiy vazni chiqadi.")],
        ['icon' => 'users', 'title' => __('Xodimlar va rollar'), 'text' => __("Menejer, ishchi va kuzatuvchi qo'shing. Har kim faqat o'ziga ruxsat berilgan ishni qiladi.")],
        ['icon' => 'upload', 'title' => __("Ro'yxatni fayldan yuklash"), 'text' => __("Ko'p hayvonni bittalab kiritmang: tayyor shablonni to'ldirib, bir yo'la yuklang.")],
        ['icon' => 'globe', 'title' => __('Uch til, qulay ko\'rinish'), 'text' => __("O'zbek (lotin va kirill) va rus tilida. Qorong'i rejim va katta shrift bor.")],
    ];

    $steps = [
        ['title' => __("Ro'yxatdan o'ting"), 'text' => __('Ism, email va parol bilan hisob oching — bir daqiqa ham ketmaydi.')],
        ['title' => __("Hayvonlarni qo'shing"), 'text' => __("Birma-bir kiriting yoki tayyor shablon bo'yicha ro'yxatni fayldan yuklang.")],
        ['title' => __('Eslatma va hisobotlarni oling'), 'text' => __("Telegram botni ulang: eslatmalar o'z vaqtida keladi, foyda va xarajat saytda ko'rinadi.")],
    ];

    $roles = [
        ['icon' => 'key', 'name' => __('Ega'), 'text' => __("Hamma narsani boshqaradi: xodimlar, tarif, to'lovlar va sozlamalar.")],
        ['icon' => 'list', 'name' => __('Menejer'), 'text' => __('Hayvonlar, eslatmalar va hisobni yuritadi, ro\'yxatni fayldan yuklaydi.')],
        ['icon' => 'check', 'name' => __('Ishchi'), 'text' => __("Hayvon qo'shadi, ma'lumotni yangilaydi, ishni «Bajarildi» deb belgilaydi.")],
        ['icon' => 'eye', 'name' => __('Kuzatuvchi'), 'text' => __("Faqat ko'radi, hech narsani o'zgartira olmaydi.")],
    ];

    // Tarif narxi: Free va 0 so'm — "Bepul", narx bo'sh — "murojaat qiling" (/tariflar bilan bir xil)
    $planPrice = function ($plan) {
        if ($plan->kod === \App\Models\Plan::FREE || $plan->narx_som === 0) {
            return ['text' => __('Bepul'), 'ask' => false];
        }
        if (! $plan->hasPrice()) {
            return ['text' => __("Narx bo'yicha murojaat qiling"), 'ask' => true];
        }

        return ['text' => __(":price so'm / oy", ['price' => number_format($plan->narx_som, 0, '.', ' ')]), 'ask' => false];
    };
    $planStaff = function ($plan) {
        if ($plan->max_staff === null) {
            return __('Xodimlar soni cheklanmagan');
        }

        return $plan->max_staff === 0 ? __("Xodimsiz, faqat ferma egasi") : __(':count tagacha xodim', ['count' => $plan->max_staff]);
    };

    if ($freePlan->max_staff === null) {
        $freeStaffText = __('Xodimlar soni cheklanmagan.');
    } elseif ($freePlan->max_staff === 0) {
        $freeStaffText = __("Xodim qo'shish pullik tariflarda mavjud.");
    } else {
        $freeStaffText = __(":count tagacha xodim qo'shish mumkin.", ['count' => $freePlan->max_staff]);
    }
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ url('/') }}">
    <meta name="theme-color" content="#2F4B33">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Chorvadorim.uz">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:locale" content="{{ $ogLocale }}">

    <script>
        // Kabinetdagi bilan bir xil sozlama (chorvadorimTheme); tanlanmagan bo'lsa telefon sozlamasi
        (function () {
            try {
                var theme = localStorage.getItem('chorvadorimTheme');
                if (theme === 'dark') { document.documentElement.classList.add('dark'); }
                else if (theme === 'light') { document.documentElement.classList.add('lp-theme-light'); }
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Spectral:wght@700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @include('landing.partials.styles')
</head>
<body class="lp-page">
    <a class="lp-skip" href="#asosiy">{{ __("Asosiy matnga o'tish") }}</a>

    {{-- Yuqori navigatsiya --}}
    <header class="lp-header">
        <div class="lp-wrap lp-header__row">
            <a href="{{ url('/') }}" class="lp-brand" aria-label="{{ __('Chorvadorim — bosh sahifa') }}">
                <span class="lp-brand__mark">@include('landing.partials.icon', ['name' => 'leaf'])</span>
                <span>Chorvadorim<span class="lp-brand__tld">.uz</span></span>
            </a>

            <nav class="lp-nav" aria-label="{{ __("Sahifa bo'limlari") }}">
                <a class="lp-nav__link" href="#imkoniyatlar">{{ __('Imkoniyatlar') }}</a>
                <a class="lp-nav__link" href="#tariflar">{{ __('Tariflar') }}</a>
                <a class="lp-nav__link" href="#savollar">{{ __('Savollar') }}</a>
            </nav>

            <div class="lp-header__actions">
                <div class="lp-header__desktop">
                    <div class="lp-lang" role="group" aria-label="{{ __('Tilni tanlash') }}">
                        @foreach ($languages as $code => [$short, $full])
                            <a href="{{ route('lang.switch', $code) }}" lang="{{ $code === 'uz_cyrl' ? 'uz-Cyrl' : $code }}" title="{{ $full }}" aria-label="{{ $full }}" @if ($locale === $code) aria-current="true" @endif>{{ $short }}</a>
                        @endforeach
                    </div>
                    @if ($isAuth)
                        <a href="{{ route('dashboard') }}" class="lp-btn lp-btn--primary lp-btn--sm">{{ __("Kabinetga o'tish") }}</a>
                    @else
                        <a href="{{ route('login') }}" class="lp-btn lp-btn--ghost lp-btn--sm">{{ __('Kirish') }}</a>
                        <a href="{{ route('register') }}" class="lp-btn lp-btn--primary lp-btn--sm">{{ __("Ro'yxatdan o'tish") }}</a>
                    @endif
                </div>

                <button type="button" class="lp-iconbtn lp-theme" data-lp-theme aria-label="{{ __("Yorug' yoki qorong'i rejim") }}">
                    @include('landing.partials.icon', ['name' => 'moon', 'class' => 'lp-icon--moon'])
                    @include('landing.partials.icon', ['name' => 'sun', 'class' => 'lp-icon--sun'])
                </button>

                <details class="lp-menu">
                    <summary class="lp-iconbtn" aria-label="{{ __('Menyu') }}">
                        @include('landing.partials.icon', ['name' => 'menu', 'class' => 'lp-icon--menu'])
                        @include('landing.partials.icon', ['name' => 'close', 'class' => 'lp-icon--close'])
                    </summary>
                    <div class="lp-menu__panel">
                        <a class="lp-menu__link" href="#imkoniyatlar">{{ __('Imkoniyatlar') }}</a>
                        <a class="lp-menu__link" href="#tariflar">{{ __('Tariflar') }}</a>
                        <a class="lp-menu__link" href="#savollar">{{ __('Savollar') }}</a>
                        <div class="lp-menu__foot">
                            <div class="lp-lang" role="group" aria-label="{{ __('Tilni tanlash') }}">
                                @foreach ($languages as $code => [$short, $full])
                                    <a href="{{ route('lang.switch', $code) }}" lang="{{ $code === 'uz_cyrl' ? 'uz-Cyrl' : $code }}" title="{{ $full }}" aria-label="{{ $full }}" @if ($locale === $code) aria-current="true" @endif>{{ $short }}</a>
                                @endforeach
                            </div>
                            @if ($isAuth)
                                <a href="{{ route('dashboard') }}" class="lp-btn lp-btn--primary">{{ __("Kabinetga o'tish") }}</a>
                            @else
                                <a href="{{ route('register') }}" class="lp-btn lp-btn--primary">{{ __("Ro'yxatdan o'tish") }}</a>
                                <a href="{{ route('login') }}" class="lp-btn lp-btn--ghost">{{ __('Kirish') }}</a>
                            @endif
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main id="asosiy">
        {{-- 1. Bosh ekran --}}
        <section class="lp-hero" aria-labelledby="lp-hero-title">
            <div class="lp-wrap lp-hero__grid">
                <div>
                    <p class="lp-hero__eyebrow">@include('landing.partials.icon', ['name' => 'leaf']) {{ __("O'zbek fermerlari uchun") }}</p>
                    <h1 id="lp-hero-title" class="lp-h1">{{ __('Chorvangiz hisobi — telefoningizda') }}</h1>
                    <p class="lp-hero__text">{{ __("Har bir hayvon, emlash, xarajat va daromad bir joyda. Eslatmalar Telegram orqali keladi, AI veterinar maslahat beradi.") }}</p>

                    <div class="lp-hero__cta">
                        <a href="{{ $startUrl }}" class="lp-btn lp-btn--primary">
                            {{ $startLabel }}
                            @include('landing.partials.icon', ['name' => 'arrow'])
                        </a>
                        <a href="{{ $botUrl }}" target="_blank" rel="noopener" class="lp-btn lp-btn--tg">
                            @include('landing.partials.icon', ['name' => 'telegram'])
                            {{ __('Telegram bot') }}
                        </a>
                    </div>

                    <ul class="lp-ticks">
                        <li>@include('landing.partials.icon', ['name' => 'check']) {{ __('Bepul tarif bor') }}</li>
                        <li>@include('landing.partials.icon', ['name' => 'check']) {{ __("Ilova o'rnatish shart emas") }}</li>
                        <li>@include('landing.partials.icon', ['name' => 'check']) {{ __("O'zbek va rus tilida") }}</li>
                    </ul>
                </div>

                @include('landing.partials.phone-mockup')
            </div>
        </section>

        {{-- 2. Muammo -> yechim --}}
        <section class="lp-section lp-section--alt" aria-labelledby="lp-ps-title">
            <div class="lp-wrap">
                <div class="lp-head">
                    <p class="lp-kicker">{{ __("Nima o'zgaradi") }}</p>
                    <h2 id="lp-ps-title" class="lp-h2">{{ __("Daftar o'rniga — tartibli hisob") }}</h2>
                </div>
                <div class="lp-ps">
                    @foreach ($problems as $item)
                        <article class="lp-ps__item">
                            <div class="lp-ps__problem">
                                @include('landing.partials.icon', ['name' => $item['icon']])
                                <p><span class="lp-ps__tag">{{ __('Muammo') }}</span>{{ $item['problem'] }}</p>
                            </div>
                            <div class="lp-ps__arrow">@include('landing.partials.icon', ['name' => 'arrow-down'])</div>
                            <div class="lp-ps__solution">
                                <span class="lp-ps__tag">{{ __('Yechim') }}</span>
                                <p>{{ $item['solution'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 3. Imkoniyatlar --}}
        <section id="imkoniyatlar" class="lp-section" aria-labelledby="lp-features-title">
            <div class="lp-wrap">
                <div class="lp-head">
                    <p class="lp-kicker">{{ __('Imkoniyatlar') }}</p>
                    <h2 id="lp-features-title" class="lp-h2">{{ __('Ferma uchun kerakli hamma narsa — bir joyda') }}</h2>
                    <p class="lp-lead">{{ __("Oddiy telefonda ishlaydi, murakkab sozlash kerak emas.") }}</p>
                </div>
                <div class="lp-features">
                    @foreach ($features as $feature)
                        <article class="lp-feature">
                            <span class="lp-feature__icon">@include('landing.partials.icon', ['name' => $feature['icon']])</span>
                            <h3 class="lp-h3">{{ $feature['title'] }}</h3>
                            <p>{{ $feature['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 4. Qanday ishlaydi --}}
        <section id="qanday-ishlaydi" class="lp-section lp-section--alt" aria-labelledby="lp-steps-title">
            <div class="lp-wrap">
                <div class="lp-head">
                    <p class="lp-kicker">{{ __('Qanday ishlaydi') }}</p>
                    <h2 id="lp-steps-title" class="lp-h2">{{ __('Uch qadamda boshlaysiz') }}</h2>
                </div>
                <ol class="lp-steps">
                    @foreach ($steps as $i => $step)
                        <li class="lp-step">
                            <span class="lp-step__num" aria-hidden="true">{{ $i + 1 }}</span>
                            <h3 class="lp-h3">{{ $step['title'] }}</h3>
                            <p>{{ $step['text'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- 5. Katta fermalar uchun --}}
        <section id="katta-fermalar" class="lp-section lp-band" aria-labelledby="lp-team-title">
            <div class="lp-wrap">
                <div class="lp-head">
                    <p class="lp-kicker">{{ __('Katta fermalar uchun') }}</p>
                    <h2 id="lp-team-title" class="lp-h2">{{ __('Butun jamoa — bitta tizimda') }}</h2>
                    <p class="lp-lead">{{ __("Har bir xodim o'z login va paroli bilan kiradi va faqat o'z ishini ko'radi. Xodimlar soni tarifga bog'liq.") }}</p>
                </div>
                <ul class="lp-roles">
                    @foreach ($roles as $role)
                        <li class="lp-role">
                            <h3 class="lp-role__name">@include('landing.partials.icon', ['name' => $role['icon']]) {{ $role['name'] }}</h3>
                            <p>{{ $role['text'] }}</p>
                        </li>
                    @endforeach
                </ul>
                <ul class="lp-band__extra">
                    <li>
                        @include('landing.partials.icon', ['name' => 'upload'])
                        <div><b>{{ __('Ommaviy yuklash') }}</b><span>{{ __("Ko'p hayvonni bitta fayl bilan qo'shing. Xatolar saqlashdan oldin ko'rsatiladi.") }}</span></div>
                    </li>
                    <li>
                        @include('landing.partials.icon', ['name' => 'key'])
                        <div><b>{{ __('Har xodimga alohida kirish') }}</b><span>{{ __("Ega vaqtinchalik parol beradi, xodim birinchi kirishda uni o'zgartiradi.") }}</span></div>
                    </li>
                    <li>
                        @include('landing.partials.icon', ['name' => 'list'])
                        <div><b>{{ __('Ferma jurnali') }}</b><span>{{ __("Kim, qachon va nimani o'zgartirgani yozib boriladi.") }}</span></div>
                    </li>
                </ul>
            </div>
        </section>

        {{-- 6. Telegram bot --}}
        <section id="telegram-bot" class="lp-section" aria-labelledby="lp-bot-title">
            <div class="lp-wrap lp-bot">
                <div>
                    <p class="lp-kicker">{{ __('Telegram bot') }}</p>
                    <h2 id="lp-bot-title" class="lp-h2">{{ __("Fermangiz — Telegram'da ham") }}</h2>
                    <p class="lp-lead">{{ __("Saytga kirmasdan ham eng muhim narsalar qo'lingizda.") }}</p>
                    <div class="lp-bot__cta">
                        <a href="{{ $botUrl }}" target="_blank" rel="noopener" class="lp-btn lp-btn--tg lp-btn--block">
                            @include('landing.partials.icon', ['name' => 'telegram'])
                            {{ __('Botni ochish') }}
                        </a>
                        <span class="lp-bot__handle">{{ '@' . config('chorvadorim.telegram_bot') }}</span>
                    </div>
                </div>
                <ul class="lp-bot__list">
                    <li class="lp-bot__item">
                        <span class="lp-bot__cmd">@include('landing.partials.icon', ['name' => 'key'])</span>
                        <div><b>{{ __('Hisobingizni ulang') }}</b><p>{{ __('Saytdagi 6 xonali kodni botga yuboring — bot sizni taniydi.') }}</p></div>
                    </li>
                    <li class="lp-bot__item">
                        <span class="lp-bot__cmd">@include('landing.partials.icon', ['name' => 'bell'])</span>
                        <div><b>{{ __('Eslatmalar keladi') }}</b><p>{{ __('Emlash yoki davolash kuni xabar keladi. Bitta tugma bilan «Bajarildi» deb belgilaysiz.') }}</p></div>
                    </li>
                    <li class="lp-bot__item">
                        <span class="lp-bot__cmd">@include('landing.partials.icon', ['name' => 'list'])</span>
                        <div><b>{{ __('Hayvonlaringiz holati') }}</b><p><code>/hayvonlarim</code> — {{ __("ro'yxat. Hayvon nomini yozsangiz, uning holati chiqadi.") }}</p></div>
                    </li>
                    <li class="lp-bot__item">
                        <span class="lp-bot__cmd">@include('landing.partials.icon', ['name' => 'shield'])</span>
                        <div><b>{{ __('Bank va hamkorlar uchun kuzatuv') }}</b><p><code>/kuzatish</code> — {{ __("bank vakili pasport yoki chip raqami bilan so'rov yuboradi. Ma'lumotni faqat siz ruxsat bersangiz ko'radi.") }} <code>/kuzatuvchilar</code> — {{ __('ruxsatni bekor qilish.') }}</p></div>
                    </li>
                </ul>
            </div>
        </section>

        {{-- 7. Tariflar (plans jadvalidan, faol tariflar) --}}
        <section id="tariflar" class="lp-section lp-section--alt" aria-labelledby="lp-plans-title">
            <div class="lp-wrap">
                <div class="lp-head">
                    <p class="lp-kicker">{{ __('Tariflar') }}</p>
                    <h2 id="lp-plans-title" class="lp-h2">{{ __("Bepul boshlang, kerak bo'lsa kengaytiring") }}</h2>
                    <p class="lp-lead">{{ __('Tarif fermangizdagi hayvonlar va xodimlar soniga qarab tanlanadi.') }}</p>
                </div>

                @if ($plans->isEmpty())
                    <p class="lp-lead">{{ __("Tariflar haqida ma'lumot olish uchun biz bilan bog'laning.") }}</p>
                @else
                    <div class="lp-plans {{ $plans->count() >= 4 ? 'lp-plans--4' : ($plans->count() === 3 ? 'lp-plans--3' : '') }}">
                        @foreach ($plans as $plan)
                            @php($price = $planPrice($plan))
                            @php($isFree = $plan->kod === \App\Models\Plan::FREE)
                            <article class="lp-plan {{ $isFree ? 'lp-plan--free' : '' }}">
                                <h3 class="lp-plan__name">{{ $plan->nomi }}</h3>
                                <p class="lp-plan__price {{ $price['ask'] ? 'lp-plan__price--ask' : '' }}">{{ $price['text'] }}</p>
                                <ul>
                                    <li>@include('landing.partials.icon', ['name' => 'check']) {{ __(':count tagacha hayvon', ['count' => $plan->max_hayvon]) }}</li>
                                    <li>@include('landing.partials.icon', ['name' => 'check']) {{ __('Oyiga :count ta AI maslahat', ['count' => $plan->oylik_chat_limit]) }}</li>
                                    <li>@include('landing.partials.icon', ['name' => 'check']) {{ __('Oyiga :count ta rasm tahlili', ['count' => $plan->oylik_vision_limit]) }}</li>
                                    <li>@include('landing.partials.icon', ['name' => 'check']) {{ $planStaff($plan) }}</li>
                                </ul>
                                @if ($isFree)
                                    <a href="{{ $startUrl }}" class="lp-btn lp-btn--primary">{{ $startLabel }}</a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif

                <div class="lp-contact">
                    <p>{{ __("Pullik tarifga o'tish yoki narxni bilish uchun biz bilan bog'laning:") }}</p>
                    <div class="lp-contact__btns">
                        <a href="tel:{{ $contactPhone }}" class="lp-btn lp-btn--phone">
                            @include('landing.partials.icon', ['name' => 'phone'])
                            <span>{{ $contactPhoneDisplay }}</span>
                        </a>
                        <a href="{{ $contactTelegramUrl }}" target="_blank" rel="noopener" class="lp-btn lp-btn--tg">
                            @include('landing.partials.icon', ['name' => 'telegram'])
                            <span>{{ '@' . $contactTelegram }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- 8. Savol-javob (JS'siz <details>) --}}
        <section id="savollar" class="lp-section" aria-labelledby="lp-faq-title">
            <div class="lp-wrap lp-faq-layout">
                <div class="lp-head">
                    <p class="lp-kicker">{{ __('Savollar') }}</p>
                    <h2 id="lp-faq-title" class="lp-h2">{{ __("Ko'p beriladigan savollar") }}</h2>
                </div>
                <div class="lp-faq">
                    <details>
                        <summary>{{ __("Ma'lumotlarim xavfsizmi?") }}</summary>
                        <div class="lp-faq__body">
                            <p>{{ __("Har bir fermaning ma'lumotlari alohida saqlanadi — boshqa foydalanuvchilar ularni ko'ra olmaydi. Hisobingizga faqat parolingiz bilan kiriladi.") }}</p>
                            <p>{{ __("Xodimlar faqat o'z roliga ruxsat berilgan narsani ko'radi. Bank yoki boshqa tashkilot hayvoningiz holatini faqat siz ruxsat bersangiz ko'radi.") }}</p>
                        </div>
                    </details>
                    <details>
                        <summary>{{ __("Internet sekin bo'lsa ishlaydimi?") }}</summary>
                        <div class="lp-faq__body">
                            <p>{{ __("Ha. Sahifalar yengil, og'ir rasm va videolarsiz — oddiy telefonda ham ochiladi. Eslatmalar Telegram orqali keladi, u sekin internetda ham yaxshi ishlaydi.") }}</p>
                            <p>{{ __("Ma'lumot kiritish va ko'rish uchun internet bo'lishi kerak.") }}</p>
                        </div>
                    </details>
                    <details>
                        <summary>{{ __("To'lov qanday qilinadi?") }}</summary>
                        <div class="lp-faq__body">
                            <p>{{ __("Bepul tarif uchun hech narsa to'lanmaydi. Pullik tarifga o'tmoqchi bo'lsangiz, biz bilan telefon yoki Telegram orqali bog'laning.") }}</p>
                            <p>{{ __("To'lov bank o'tkazmasi, to'lov tizimlari yoki naqd pul bilan qabul qilinadi. To'lov qabul qilingach, tarifingizni o'zimiz yoqib beramiz.") }}</p>
                        </div>
                    </details>
                    <details>
                        <summary>{{ __("Xodimlarim nimani ko'radi?") }}</summary>
                        <div class="lp-faq__body">
                            <p>{{ __("Bu ularning roliga bog'liq. Menejer hayvonlar, eslatmalar va hisobni yuritadi. Ishchi hayvon qo'shadi va ma'lumotni yangilaydi, lekin o'chira olmaydi. Kuzatuvchi faqat ko'radi.") }}</p>
                            <p>{{ __("Xodimlar ro'yxati, to'lovlar va ferma sozlamalarini faqat ferma egasi ko'radi.") }}</p>
                        </div>
                    </details>
                    <details>
                        <summary>{{ __('Bepul tarifda nima bor?') }}</summary>
                        <div class="lp-faq__body">
                            <p>{{ __("Bepul tarifda :animals tagacha hayvon, oyiga :chat ta AI maslahat va :vision ta rasm tahlili bor. Eslatmalar, Telegram bot, xarajat va daromad hisobi ham ishlaydi.", ['animals' => $freePlan->max_hayvon, 'chat' => $freePlan->oylik_chat_limit, 'vision' => $freePlan->oylik_vision_limit]) }}</p>
                            <p>{{ $freeStaffText }}</p>
                        </div>
                    </details>
                    <details>
                        <summary>{{ __('AI maslahatiga ishonsa bo\'ladimi?') }}</summary>
                        <div class="lp-faq__body">
                            <p>{{ __("AI ozuqa, parvarish va kasallik belgilari bo'yicha umumiy maslahat beradi va hayvoningiz ma'lumotlarini hisobga oladi. Lekin u veterinarni almashtirmaydi: jiddiy holatda albatta veterinarga murojaat qiling.") }}</p>
                        </div>
                    </details>
                    <details>
                        <summary>{{ __("Telefonga ilova o'rnatish kerakmi?") }}</summary>
                        <div class="lp-faq__body">
                            <p>{{ __("Yo'q. Chorvadorim brauzerda ochiladi — telefonda ham, kompyuterda ham. Eslatmalar uchun faqat Telegram kerak.") }}</p>
                        </div>
                    </details>
                </div>
            </div>
        </section>

        {{-- 9. Yakuniy chaqiriq --}}
        <section class="lp-section lp-section--alt lp-final" aria-labelledby="lp-final-title">
            <div class="lp-wrap">
                <h2 id="lp-final-title" class="lp-h2">{{ __('Bugun bepul boshlang') }}</h2>
                <p class="lp-lead">{{ __("Ro'yxatdan o'tish bir daqiqa oladi. Savolingiz bo'lsa — qo'ng'iroq qiling yoki Telegram'da yozing.") }}</p>
                <div class="lp-final__cta">
                    <a href="{{ $startUrl }}" class="lp-btn lp-btn--primary">
                        {{ $startLabel }}
                        @include('landing.partials.icon', ['name' => 'arrow'])
                    </a>
                </div>
                <div class="lp-final__contact">
                    <a href="tel:{{ $contactPhone }}">@include('landing.partials.icon', ['name' => 'phone']) {{ $contactPhoneDisplay }}</a>
                    <a href="{{ $contactTelegramUrl }}" target="_blank" rel="noopener">@include('landing.partials.icon', ['name' => 'telegram']) {{ '@' . $contactTelegram }}</a>
                </div>
            </div>
        </section>
    </main>

    <div class="lp-foot">&copy; {{ date('Y') }} Chorvadorim.uz</div>

    {{-- 10. Yordam qatori (har sahifada bir xil) --}}
    @include('layouts.partials.support-footer')

    <script>
        (function () {
            var root = document.documentElement;

            function isDark() {
                if (root.classList.contains('dark')) { return true; }
                if (root.classList.contains('lp-theme-light')) { return false; }
                return !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            }

            var themeButtons = document.querySelectorAll('[data-lp-theme]');
            for (var i = 0; i < themeButtons.length; i++) {
                themeButtons[i].addEventListener('click', function () {
                    var dark = !isDark();
                    root.classList.toggle('dark', dark);
                    root.classList.toggle('lp-theme-light', !dark);
                    try { localStorage.setItem('chorvadorimTheme', dark ? 'dark' : 'light'); } catch (e) {}
                });
            }

            // Mobil menyu: havola bosilganda yopiladi
            var menu = document.querySelector('.lp-menu');
            if (menu) {
                var links = menu.querySelectorAll('a[href^="#"]');
                for (var j = 0; j < links.length; j++) {
                    links[j].addEventListener('click', function () { menu.removeAttribute('open'); });
                }
            }
        })();
    </script>
</body>
</html>
