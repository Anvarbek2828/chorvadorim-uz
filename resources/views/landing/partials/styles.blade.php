{{-- Bosh sahifa uslublari. Alohida CSS fayl yuklanmaydi (sekin internet uchun bitta so'rov). Klasslar "lp-" bilan boshlanadi. --}}
<style>
    :root {
        --lp-bg: #FAF6EE;
        --lp-bg-alt: #F2ECDF;
        --lp-surface: #FFFFFF;
        --lp-ink: #201C16;
        --lp-muted: #5C5447;
        --lp-line: #E2D9C6;
        --lp-accent: #2F4B33;
        --lp-accent-soft: #E3EBDC;
        --lp-btn: #2F4B33;
        --lp-btn-hover: #24392A;
        --lp-btn-ink: #FAF6EE;
        --lp-earth: #A4572F;
        --lp-earth-soft: #F5E4D6;
        --lp-ochre: #B98A2E;
        --lp-band: #263D2A;
        --lp-band-ink: #F2EDE1;
        --lp-band-muted: #C3CDB9;
        --lp-band-line: #3C5640;
        --lp-tg: #229ED9;
        --lp-tg-hover: #1B84B6;
        --lp-shadow: 0 1px 2px rgba(32, 28, 22, .05), 0 8px 24px rgba(32, 28, 22, .06);
        color-scheme: light;
    }

    html.dark {
        --lp-bg: #14130F;
        --lp-bg-alt: #1B1915;
        --lp-surface: #221F1A;
        --lp-ink: #EEE8DA;
        --lp-muted: #B4AA97;
        --lp-line: #37322A;
        --lp-accent: #A3CC9C;
        --lp-accent-soft: #24311F;
        --lp-btn: #9BC893;
        --lp-btn-hover: #B2D6AB;
        --lp-btn-ink: #12200F;
        --lp-earth: #E39A72;
        --lp-earth-soft: #3A251B;
        --lp-ochre: #D8AE5B;
        --lp-band: #1A2A1C;
        --lp-band-ink: #EEE8DA;
        --lp-band-muted: #B6C2AB;
        --lp-band-line: #2E4430;
        --lp-shadow: 0 1px 2px rgba(0, 0, 0, .3), 0 8px 24px rgba(0, 0, 0, .25);
        color-scheme: dark;
    }

    @media (prefers-color-scheme: dark) {
        html:not(.lp-theme-light) {
            --lp-bg: #14130F;
            --lp-bg-alt: #1B1915;
            --lp-surface: #221F1A;
            --lp-ink: #EEE8DA;
            --lp-muted: #B4AA97;
            --lp-line: #37322A;
            --lp-accent: #A3CC9C;
            --lp-accent-soft: #24311F;
            --lp-btn: #9BC893;
            --lp-btn-hover: #B2D6AB;
            --lp-btn-ink: #12200F;
            --lp-earth: #E39A72;
            --lp-earth-soft: #3A251B;
            --lp-ochre: #D8AE5B;
            --lp-band: #1A2A1C;
            --lp-band-ink: #EEE8DA;
            --lp-band-muted: #B6C2AB;
            --lp-band-line: #2E4430;
            --lp-shadow: 0 1px 2px rgba(0, 0, 0, .3), 0 8px 24px rgba(0, 0, 0, .25);
            color-scheme: dark;
        }
    }

    *, *::before, *::after { box-sizing: border-box; }
    html { -webkit-text-size-adjust: 100%; }
    @media (prefers-reduced-motion: no-preference) { html { scroll-behavior: smooth; } }
    body { margin: 0; background: var(--lp-bg); color: var(--lp-ink); font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; font-size: 1.0625rem; line-height: 1.6; -webkit-font-smoothing: antialiased; }
    img, svg { display: block; }
    a { color: inherit; }
    h1, h2, h3, p, ul, ol, figure { margin: 0; }
    ul, ol { padding: 0; list-style: none; }
    [id] { scroll-margin-top: 80px; }
    :focus-visible { outline: 3px solid var(--lp-ochre); outline-offset: 2px; border-radius: 4px; }

    .lp-serif { font-family: 'Spectral', Georgia, 'Times New Roman', serif; }
    .lp-icon { width: 1.5rem; height: 1.5rem; flex-shrink: 0; }
    .lp-wrap { width: 100%; max-width: 1160px; margin: 0 auto; padding: 0 1rem; }
    @media (min-width: 640px) { .lp-wrap { padding: 0 1.5rem; } }
    .lp-skip { position: absolute; left: -999px; top: .5rem; background: var(--lp-btn); color: var(--lp-btn-ink); padding: .75rem 1rem; border-radius: 8px; z-index: 100; }
    .lp-skip:focus { left: .5rem; }

    /* Tugmalar: kamida 48px balandlik */
    .lp-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 3rem; padding: .75rem 1.375rem; border-radius: 10px; font-weight: 600; font-size: 1.0625rem; line-height: 1.2; text-decoration: none; border: 2px solid transparent; transition: background-color .15s ease, border-color .15s ease, color .15s ease; cursor: pointer; }
    .lp-btn .lp-icon { width: 1.25rem; height: 1.25rem; }
    .lp-btn--primary { background: var(--lp-btn); color: var(--lp-btn-ink); }
    .lp-btn--primary:hover { background: var(--lp-btn-hover); }
    .lp-btn--ghost { border-color: var(--lp-line); color: var(--lp-ink); background: var(--lp-surface); }
    .lp-btn--ghost:hover { border-color: var(--lp-accent); }
    .lp-btn--tg { background: var(--lp-tg); color: #fff; }
    .lp-btn--tg:hover { background: var(--lp-tg-hover); }
    .lp-btn--light { background: var(--lp-band-ink); color: var(--lp-band); }
    .lp-btn--light:hover { background: #fff; }
    .lp-btn--sm { min-height: 2.75rem; padding: .5rem 1rem; font-size: .9375rem; }
    .lp-btn--block { width: 100%; }
    @media (min-width: 480px) { .lp-btn--block { width: auto; } }

    /* Yuqori navigatsiya */
    .lp-header { position: sticky; top: 0; z-index: 50; background: var(--lp-bg); border-bottom: 1px solid var(--lp-line); }
    .lp-header__row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; height: 4rem; }
    .lp-brand { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.75rem; text-decoration: none; font-weight: 700; font-size: 1.1875rem; letter-spacing: -.01em; }
    .lp-brand__mark { display: inline-flex; align-items: center; justify-content: center; width: 2.125rem; height: 2.125rem; border-radius: 9px; background: var(--lp-btn); color: var(--lp-btn-ink); }
    .lp-brand__mark .lp-icon { width: 1.25rem; height: 1.25rem; }
    .lp-brand__tld { color: var(--lp-earth); }
    .lp-nav { display: none; align-items: center; gap: 1.5rem; }
    .lp-nav__link { display: inline-flex; align-items: center; min-height: 2.75rem; text-decoration: none; font-weight: 500; color: var(--lp-muted); }
    .lp-nav__link:hover { color: var(--lp-ink); }
    .lp-header__actions { display: flex; align-items: center; gap: .5rem; }
    .lp-header__desktop { display: none; align-items: center; gap: .5rem; }
    .lp-lang { display: inline-flex; border: 1px solid var(--lp-line); border-radius: 9px; overflow: hidden; }
    .lp-lang a { display: inline-flex; align-items: center; justify-content: center; min-width: 2.75rem; min-height: 2.75rem; padding: 0 .5rem; font-size: .875rem; font-weight: 600; text-decoration: none; color: var(--lp-muted); }
    .lp-lang a[aria-current="true"] { background: var(--lp-accent-soft); color: var(--lp-accent); }
    .lp-iconbtn { display: inline-flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; border-radius: 9px; border: 1px solid var(--lp-line); background: var(--lp-surface); color: var(--lp-ink); cursor: pointer; padding: 0; }
    .lp-iconbtn .lp-icon { width: 1.375rem; height: 1.375rem; }
    .lp-theme .lp-icon--sun { display: none; }
    html.dark .lp-theme .lp-icon--sun { display: block; }
    html.dark .lp-theme .lp-icon--moon { display: none; }
    @media (prefers-color-scheme: dark) {
        html:not(.lp-theme-light) .lp-theme .lp-icon--sun { display: block; }
        html:not(.lp-theme-light) .lp-theme .lp-icon--moon { display: none; }
    }

    /* Mobil menyu (JS'siz <details>) */
    .lp-menu { position: static; }
    .lp-menu > summary { list-style: none; }
    .lp-menu > summary::-webkit-details-marker { display: none; }
    .lp-menu .lp-icon--close { display: none; }
    .lp-menu[open] .lp-icon--close { display: block; }
    .lp-menu[open] .lp-icon--menu { display: none; }
    .lp-menu__panel { position: absolute; left: 0; right: 0; top: 100%; background: var(--lp-bg); border-bottom: 1px solid var(--lp-line); box-shadow: var(--lp-shadow); padding: .5rem 1rem 1.25rem; }
    .lp-menu__panel a.lp-menu__link { display: flex; align-items: center; min-height: 3rem; padding: 0 .25rem; font-size: 1.125rem; font-weight: 600; text-decoration: none; border-bottom: 1px solid var(--lp-line); }
    .lp-menu__foot { display: grid; gap: .75rem; margin-top: 1rem; }
    .lp-menu__foot .lp-lang { justify-self: start; }

    @media (min-width: 960px) {
        .lp-nav, .lp-header__desktop { display: flex; }
        .lp-menu { display: none; }
    }

    /* Bo'limlar */
    .lp-section { padding: 3.5rem 0; }
    .lp-section--alt { background: var(--lp-bg-alt); border-top: 1px solid var(--lp-line); border-bottom: 1px solid var(--lp-line); }
    @media (min-width: 768px) { .lp-section { padding: 5.5rem 0; } }
    .lp-kicker { display: inline-flex; align-items: center; gap: .5rem; font-size: .875rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--lp-earth); margin-bottom: .75rem; }
    .lp-kicker::before { content: ''; width: 1.5rem; height: 2px; background: currentColor; border-radius: 2px; }
    .lp-h2 { font-family: 'Spectral', Georgia, serif; font-weight: 700; font-size: clamp(1.75rem, 1.2rem + 2.2vw, 2.625rem); line-height: 1.15; letter-spacing: -.01em; }
    .lp-lead { margin-top: .875rem; font-size: 1.125rem; color: var(--lp-muted); max-width: 40rem; }
    .lp-head { margin-bottom: 2rem; }
    @media (min-width: 768px) { .lp-head { margin-bottom: 3rem; } }
    .lp-h3 { font-size: 1.1875rem; font-weight: 700; line-height: 1.3; }
    .lp-text { color: var(--lp-muted); margin-top: .375rem; }

    /* Bosh ekran */
    .lp-hero { position: relative; overflow: hidden; padding: 2.5rem 0 3.5rem; }
    .lp-hero::before { content: ''; position: absolute; right: -20%; top: -10%; width: 70vw; max-width: 760px; aspect-ratio: 1; border-radius: 50%; background: radial-gradient(closest-side, var(--lp-accent-soft), transparent); pointer-events: none; }
    .lp-hero__grid { position: relative; display: grid; gap: 2.5rem; align-items: center; }
    @media (min-width: 960px) {
        .lp-hero { padding: 4.5rem 0 5.5rem; }
        .lp-hero__grid { grid-template-columns: 1.1fr .9fr; gap: 3rem; }
    }
    .lp-hero__eyebrow { display: inline-flex; align-items: center; gap: .5rem; padding: .375rem .75rem; border-radius: 999px; background: var(--lp-accent-soft); color: var(--lp-accent); font-size: .9375rem; font-weight: 600; margin-bottom: 1.25rem; }
    .lp-hero__eyebrow .lp-icon { width: 1.125rem; height: 1.125rem; }
    .lp-h1 { font-family: 'Spectral', Georgia, serif; font-weight: 700; font-size: clamp(2.25rem, 1.4rem + 4vw, 3.75rem); line-height: 1.06; letter-spacing: -.02em; }
    .lp-h1 em { font-style: normal; color: var(--lp-accent); }
    .lp-hero__text { margin-top: 1.25rem; font-size: 1.1875rem; line-height: 1.6; color: var(--lp-muted); max-width: 34rem; }
    .lp-hero__cta { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 2rem; }
    .lp-hero__cta .lp-btn { flex: 1 1 100%; }
    @media (min-width: 480px) { .lp-hero__cta .lp-btn { flex: 0 1 auto; } }
    .lp-ticks { display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; margin-top: 1.5rem; font-size: .9375rem; color: var(--lp-muted); }
    .lp-ticks li { display: inline-flex; align-items: center; gap: .375rem; }
    .lp-ticks .lp-icon { width: 1.125rem; height: 1.125rem; color: var(--lp-accent); }

    /* Telefon maketi */
    .lp-phone-wrap { position: relative; justify-self: center; width: 100%; max-width: 330px; padding: 0 0 0 0; }
    .lp-phone { position: relative; margin: 0 auto; width: 280px; max-width: 86%; border-radius: 38px; padding: 10px; background: #1E1C18; box-shadow: 0 30px 60px -20px rgba(32, 28, 22, .45), inset 0 0 0 1.5px #3B3730; }
    .lp-phone::before { content: ''; position: absolute; top: 18px; left: 50%; width: 70px; height: 18px; margin-left: -35px; border-radius: 999px; background: #1E1C18; z-index: 2; }
    html.dark .lp-phone, html.dark .lp-phone::before { background: #36322B; }
    @media (prefers-color-scheme: dark) { html:not(.lp-theme-light) .lp-phone, html:not(.lp-theme-light) .lp-phone::before { background: #36322B; } }
    .lp-phone__screen { border-radius: 29px; overflow: hidden; background: var(--lp-bg-alt); padding: 12px 12px 16px; display: grid; gap: 10px; font-size: 13px; line-height: 1.35; min-height: 540px; align-content: start; }
    .lp-phone__status { display: flex; justify-content: space-between; align-items: center; padding: 2px 10px 6px; font-size: 11px; font-weight: 600; color: var(--lp-ink); }
    .lp-phone__signal { display: inline-flex; align-items: flex-end; gap: 2px; height: 10px; }
    .lp-phone__signal i { display: block; width: 3px; background: currentColor; border-radius: 1px; }
    .lp-phone__signal i:nth-child(1) { height: 4px; } .lp-phone__signal i:nth-child(2) { height: 6px; } .lp-phone__signal i:nth-child(3) { height: 8px; } .lp-phone__signal i:nth-child(4) { height: 10px; }
    .lp-phone__appbar { display: flex; align-items: center; gap: 8px; padding: 4px 4px 2px; font-weight: 700; font-size: 15px; }
    .lp-phone__logo { display: inline-flex; width: 26px; height: 26px; border-radius: 7px; align-items: center; justify-content: center; background: var(--lp-btn); color: var(--lp-btn-ink); }
    .lp-phone__logo .lp-icon { width: 16px; height: 16px; }
    .lp-mock-card { background: var(--lp-surface); border: 1px solid var(--lp-line); border-radius: 14px; padding: 12px; }
    .lp-mock-card--reminder { border-left: 4px solid var(--lp-earth); }
    .lp-mock-label { display: flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--lp-muted); }
    .lp-mock-label .lp-icon { width: 14px; height: 14px; color: var(--lp-earth); }
    .lp-mock-title { margin-top: 4px; font-weight: 700; font-size: 15px; }
    .lp-mock-btn { display: inline-flex; align-items: center; gap: 4px; margin-top: 10px; padding: 6px 10px; border-radius: 8px; background: var(--lp-btn); color: var(--lp-btn-ink); font-weight: 600; font-size: 12px; }
    .lp-mock-btn .lp-icon { width: 14px; height: 14px; }
    .lp-mock-list { margin-top: 6px; }
    .lp-mock-list li { display: grid; grid-template-columns: 10px 1fr auto auto; align-items: center; gap: 8px; padding: 7px 0; border-top: 1px solid var(--lp-line); }
    .lp-mock-list li:first-child { border-top: 0; }
    .lp-mock-list b { font-weight: 600; }
    .lp-mock-list em { font-style: normal; font-size: 11px; padding: 2px 7px; border-radius: 999px; background: var(--lp-accent-soft); color: var(--lp-accent); font-weight: 600; }
    .lp-mock-list span:last-child { color: var(--lp-muted); font-size: 12px; min-width: 46px; text-align: right; }
    .lp-mock-dot { width: 10px; height: 10px; border-radius: 50%; background: #B58456; }
    .lp-mock-dot--b { background: #6E4B33; } .lp-mock-dot--c { background: #D9CDB4; }
    .lp-mock-card--money { display: flex; align-items: flex-end; justify-content: space-between; gap: 10px; }
    .lp-mock-money { margin-top: 4px; font-weight: 700; font-size: 18px; color: var(--lp-accent); white-space: nowrap; }
    .lp-mock-money small { font-size: 12px; font-weight: 600; }
    .lp-mock-bars { display: flex; align-items: flex-end; gap: 4px; height: 40px; }
    .lp-mock-bars i { display: block; width: 8px; border-radius: 3px 3px 0 0; background: var(--lp-accent); opacity: .85; }
    .lp-toast { position: absolute; left: 0; right: 0; bottom: 3.25rem; margin: 0 auto; width: 92%; max-width: 300px; display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 14px; background: var(--lp-surface); border: 1px solid var(--lp-line); box-shadow: var(--lp-shadow); font-size: 13px; line-height: 1.35; }
    .lp-toast b { display: block; font-size: 13px; }
    .lp-toast > span:last-child > span { color: var(--lp-muted); }
    .lp-toast__icon { display: inline-flex; flex-shrink: 0; width: 32px; height: 32px; border-radius: 50%; align-items: center; justify-content: center; background: var(--lp-tg); color: #fff; }
    .lp-toast__icon .lp-icon { width: 18px; height: 18px; }
    @media (min-width: 480px) { .lp-toast { left: auto; right: -6%; width: 250px; bottom: 5rem; } }
    .lp-phone-caption { margin-top: .875rem; text-align: center; font-size: .8125rem; color: var(--lp-muted); }
    @media (prefers-reduced-motion: no-preference) {
        .lp-toast { animation: lp-pop .5s ease-out .4s both; }
        @keyframes lp-pop { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    }

    /* Muammo -> yechim */
    .lp-ps { display: grid; gap: 1rem; }
    @media (min-width: 860px) { .lp-ps { grid-template-columns: repeat(3, 1fr); gap: 1.25rem; } }
    .lp-ps__item { background: var(--lp-surface); border: 1px solid var(--lp-line); border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; }
    .lp-ps__problem, .lp-ps__solution { padding: 1.25rem 1.25rem; }
    .lp-ps__problem { display: flex; gap: .75rem; align-items: flex-start; color: var(--lp-muted); }
    .lp-ps__problem .lp-icon { color: var(--lp-earth); margin-top: .125rem; }
    .lp-ps__tag { display: block; font-size: .8125rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; margin-bottom: .125rem; }
    .lp-ps__problem .lp-ps__tag { color: var(--lp-earth); }
    .lp-ps__solution .lp-ps__tag { color: var(--lp-accent); }
    .lp-ps__arrow { display: flex; align-items: center; gap: .75rem; padding: 0 1.25rem; color: var(--lp-line); }
    .lp-ps__arrow::before, .lp-ps__arrow::after { content: ''; flex: 1; border-top: 1px dashed var(--lp-line); }
    .lp-ps__arrow .lp-icon { color: var(--lp-accent); width: 1.25rem; height: 1.25rem; }
    .lp-ps__solution { flex: 1; background: var(--lp-accent-soft); margin-top: .75rem; }
    .lp-ps__solution p { font-size: 1.125rem; font-weight: 600; line-height: 1.4; color: var(--lp-ink); }

    /* Imkoniyatlar */
    .lp-features { display: grid; gap: 1rem; }
    @media (min-width: 600px) { .lp-features { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .lp-features { grid-template-columns: repeat(4, 1fr); gap: 1.25rem; } }
    .lp-feature { background: var(--lp-surface); border: 1px solid var(--lp-line); border-radius: 16px; padding: 1.125rem; display: grid; grid-template-columns: 3rem 1fr; column-gap: 1rem; align-items: start; }
    .lp-feature__icon { grid-row: span 2; display: inline-flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 12px; background: var(--lp-accent-soft); color: var(--lp-accent); }
    @media (min-width: 600px) {
        .lp-feature { display: block; padding: 1.375rem; }
        .lp-feature__icon { margin-bottom: 1rem; }
    }
    .lp-feature:nth-child(3n+2) .lp-feature__icon { background: var(--lp-earth-soft); color: var(--lp-earth); }
    .lp-feature p { color: var(--lp-muted); margin-top: .375rem; font-size: 1rem; }

    /* Qanday ishlaydi */
    .lp-steps { display: grid; gap: 1.5rem; counter-reset: lp-step; }
    @media (min-width: 860px) { .lp-steps { grid-template-columns: repeat(3, 1fr); gap: 2rem; } }
    .lp-step { position: relative; padding-left: 4rem; }
    @media (min-width: 860px) { .lp-step { padding-left: 0; padding-top: 4.25rem; } }
    .lp-step__num { position: absolute; left: 0; top: 0; display: flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 50%; background: var(--lp-btn); color: var(--lp-btn-ink); font-family: 'Spectral', Georgia, serif; font-weight: 700; font-size: 1.375rem; }
    @media (min-width: 860px) {
        .lp-step:not(:last-child)::after { content: ''; position: absolute; top: 1.5rem; left: 4rem; right: -1rem; border-top: 2px dashed var(--lp-line); }
    }
    .lp-step p { color: var(--lp-muted); margin-top: .375rem; }

    /* Katta fermalar (to'q yashil bo'lak) */
    .lp-band { background: var(--lp-band); color: var(--lp-band-ink); }
    .lp-band .lp-kicker { color: var(--lp-ochre); }
    .lp-band .lp-lead { color: var(--lp-band-muted); }
    .lp-roles { display: grid; gap: .75rem; }
    @media (min-width: 600px) { .lp-roles { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .lp-roles { grid-template-columns: repeat(4, 1fr); } }
    .lp-role { border: 1px solid var(--lp-band-line); border-radius: 14px; padding: 1.25rem; }
    .lp-role__name { display: flex; align-items: center; gap: .5rem; font-weight: 700; font-size: 1.125rem; }
    .lp-role__name .lp-icon { color: var(--lp-ochre); }
    .lp-role p { color: var(--lp-band-muted); margin-top: .375rem; font-size: 1rem; }
    .lp-band__extra { display: grid; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--lp-band-line); }
    @media (min-width: 860px) { .lp-band__extra { grid-template-columns: repeat(3, 1fr); gap: 2rem; } }
    .lp-band__extra li { display: flex; gap: .75rem; align-items: flex-start; }
    .lp-band__extra .lp-icon { color: var(--lp-ochre); margin-top: .125rem; }
    .lp-band__extra b { display: block; font-size: 1.0625rem; }
    .lp-band__extra span { color: var(--lp-band-muted); font-size: 1rem; }

    /* Telegram bot */
    .lp-bot { display: grid; gap: 2rem; align-items: start; }
    @media (min-width: 960px) { .lp-bot { grid-template-columns: .9fr 1.1fr; gap: 4rem; } }
    .lp-bot__list { display: grid; gap: .75rem; }
    .lp-bot__item { display: flex; gap: 1rem; align-items: flex-start; background: var(--lp-surface); border: 1px solid var(--lp-line); border-radius: 14px; padding: 1.125rem 1.25rem; }
    .lp-bot__cmd { flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; min-width: 2.75rem; height: 2.75rem; padding: 0 .5rem; border-radius: 10px; background: #E1F1FA; color: #146C96; font-weight: 700; font-size: .875rem; font-family: ui-monospace, 'SFMono-Regular', Menlo, Consolas, monospace; }
    html.dark .lp-bot__cmd { background: #133243; color: #8CCDEB; }
    @media (prefers-color-scheme: dark) { html:not(.lp-theme-light) .lp-bot__cmd { background: #133243; color: #8CCDEB; } }
    .lp-bot__cmd .lp-icon { width: 1.375rem; height: 1.375rem; }
    .lp-bot__item b { display: block; font-size: 1.0625rem; }
    .lp-bot__item code { font-family: ui-monospace, 'SFMono-Regular', Menlo, Consolas, monospace; font-size: .9375rem; color: var(--lp-ink); background: var(--lp-bg-alt); padding: .05rem .35rem; border-radius: 5px; }
    .lp-bot__item p { color: var(--lp-muted); margin-top: .25rem; font-size: 1rem; }
    .lp-bot__cta { margin-top: 1.75rem; display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; }
    .lp-bot__handle { color: var(--lp-muted); font-weight: 600; }

    /* Tariflar */
    .lp-plans { display: grid; gap: 1rem; }
    @media (min-width: 600px) { .lp-plans { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .lp-plans--4 { grid-template-columns: repeat(4, 1fr); } .lp-plans--3 { grid-template-columns: repeat(3, 1fr); } }
    .lp-plan { display: flex; flex-direction: column; background: var(--lp-surface); border: 1px solid var(--lp-line); border-radius: 16px; padding: 1.5rem; }
    .lp-plan--free { border-color: var(--lp-accent); box-shadow: inset 0 0 0 1px var(--lp-accent); }
    .lp-plan__name { font-size: 1.25rem; font-weight: 700; }
    .lp-plan__price { margin-top: .5rem; font-family: 'Spectral', Georgia, serif; font-size: 1.625rem; font-weight: 700; line-height: 1.2; }
    .lp-plan__price--ask { font-family: inherit; font-size: 1rem; font-weight: 600; color: var(--lp-muted); line-height: 1.4; }
    .lp-plan ul { display: grid; align-content: start; gap: .625rem; margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--lp-line); flex: 1; }
    .lp-plan li { display: flex; gap: .5rem; align-items: flex-start; font-size: 1rem; }
    .lp-plan li .lp-icon { width: 1.25rem; height: 1.25rem; color: var(--lp-accent); margin-top: .125rem; }
    .lp-plan .lp-btn { margin-top: 1.5rem; }
    .lp-contact { display: grid; gap: 1rem; margin-top: 2rem; padding: 1.5rem; border-radius: 16px; background: var(--lp-surface); border: 1px dashed var(--lp-line); }
    @media (min-width: 860px) { .lp-contact { grid-template-columns: 1fr auto; align-items: center; } }
    .lp-contact p { color: var(--lp-muted); }
    .lp-contact__btns { display: flex; flex-wrap: wrap; gap: .75rem; }
    .lp-contact__btns .lp-btn { flex: 1 1 13rem; }
    @media (min-width: 640px) { .lp-contact__btns .lp-btn { flex: 0 1 auto; } }
    .lp-btn--phone { background: var(--lp-btn); color: var(--lp-btn-ink); }
    .lp-btn--phone:hover { background: var(--lp-btn-hover); }

    /* Savol-javob */
    .lp-faq { display: grid; gap: .75rem; max-width: 50rem; }
    @media (min-width: 960px) { .lp-faq-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 3rem; align-items: start; } }
    .lp-faq details { background: var(--lp-surface); border: 1px solid var(--lp-line); border-radius: 14px; }
    .lp-faq summary { display: flex; align-items: center; justify-content: space-between; gap: 1rem; min-height: 3.5rem; padding: 1rem 1.25rem; cursor: pointer; font-weight: 600; font-size: 1.0625rem; list-style: none; }
    .lp-faq summary::-webkit-details-marker { display: none; }
    .lp-faq summary::after { content: '+'; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 50%; background: var(--lp-accent-soft); color: var(--lp-accent); font-size: 1.375rem; font-weight: 500; line-height: 1; }
    .lp-faq details[open] summary::after { content: '\2212'; }
    .lp-faq__body { padding: 0 1.25rem 1.25rem; color: var(--lp-muted); }
    .lp-faq__body p + p { margin-top: .625rem; }

    /* Yakuniy chaqiriq */
    .lp-final { text-align: center; }
    .lp-final .lp-lead { margin-left: auto; margin-right: auto; }
    .lp-final__cta { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin-top: 2rem; }
    .lp-final__cta .lp-btn { flex: 1 1 100%; }
    @media (min-width: 480px) { .lp-final__cta .lp-btn { flex: 0 1 auto; } }
    .lp-final__contact { display: flex; flex-wrap: wrap; justify-content: center; gap: .25rem 1.25rem; margin-top: 1.5rem; color: var(--lp-muted); }
    .lp-final__contact a { display: inline-flex; align-items: center; gap: .375rem; min-height: 2.75rem; font-weight: 600; color: var(--lp-ink); text-decoration: none; }
    .lp-final__contact a:hover { text-decoration: underline; }
    .lp-final__contact .lp-icon { width: 1.125rem; height: 1.125rem; color: var(--lp-accent); }

    /* Pastki qism va yordam qatori (support-footer partiali) */
    .lp-foot { padding: 1.5rem 0 0; text-align: center; color: var(--lp-muted); font-size: .875rem; }
    /* "html body.lp-page" — partial ichidagi html.dark qoidalaridan ustun turishi uchun */
    html body.lp-page .chv-support { margin-top: 1rem; border-color: var(--lp-line); color: var(--lp-muted); }
    html body.lp-page .chv-support span { color: var(--lp-muted); }
    html body.lp-page .chv-support .chv-support__link, html body.lp-page .chv-support .chv-support__link span { color: var(--lp-accent); }
</style>
