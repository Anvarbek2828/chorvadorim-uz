{{--
    Bosh ekrandagi telefon: ilovaning soddalashtirilgan ko'rinishi (HTML/CSS, screenshot emas).
    Haqiqiy rasmga almashtirish uchun .lp-phone__screen ichini <img> bilan almashtirish kifoya.
--}}
<figure class="lp-phone-wrap">
    <div class="lp-phone" role="img" aria-label="{{ __('Ilova ko\'rinishi namunasi: eslatma, hayvonlar ro\'yxati va foyda') }}">
        <div class="lp-phone__screen" aria-hidden="true">
            <div class="lp-phone__status">
                <span>9:41</span>
                <span class="lp-phone__signal"><i></i><i></i><i></i><i></i></span>
            </div>

            <div class="lp-phone__appbar">
                <span class="lp-phone__logo">@include('landing.partials.icon', ['name' => 'leaf'])</span>
                <span>Chorvadorim</span>
            </div>

            <div class="lp-mock-card lp-mock-card--reminder">
                <div class="lp-mock-label">@include('landing.partials.icon', ['name' => 'bell']) {{ __('Bugungi eslatma') }}</div>
                <div class="lp-mock-title">{{ __('Malla — emlash') }}</div>
                <span class="lp-mock-btn">@include('landing.partials.icon', ['name' => 'check']) {{ __('Bajarildi') }}</span>
            </div>

            <div class="lp-mock-card">
                <div class="lp-mock-label">{{ __('Mening hayvonlarim') }}</div>
                <ul class="lp-mock-list">
                    <li><span class="lp-mock-dot"></span><b>{{ __('Malla') }}</b><em>{{ __('Sut') }}</em><span>420 {{ __('kg') }}</span></li>
                    <li><span class="lp-mock-dot lp-mock-dot--b"></span><b>{{ __('Qashqa') }}</b><em>{{ __("Go'sht") }}</em><span>385 {{ __('kg') }}</span></li>
                    <li><span class="lp-mock-dot lp-mock-dot--c"></span><b>{{ __('Oqbosh') }}</b><em>{{ __('Sut') }}</em><span>460 {{ __('kg') }}</span></li>
                </ul>
            </div>

            <div class="lp-mock-card lp-mock-card--money">
                <div>
                    <div class="lp-mock-label">{{ __('Foyda') }}</div>
                    <div class="lp-mock-money">+ 700 000 <small>{{ __("so'm") }}</small></div>
                </div>
                <div class="lp-mock-bars"><i style="height:45%"></i><i style="height:60%"></i><i style="height:52%"></i><i style="height:78%"></i><i style="height:90%"></i></div>
            </div>
        </div>
    </div>

    <div class="lp-toast" aria-hidden="true">
        <span class="lp-toast__icon">@include('landing.partials.icon', ['name' => 'telegram'])</span>
        <span>
            <b>Chorvadorim bot</b>
            <span>{{ __('Eslatma: Malla — emlash, bugun') }}</span>
        </span>
    </div>

    <figcaption class="lp-phone-caption">{{ __('Ilova ko\'rinishi — namuna') }}</figcaption>
</figure>
