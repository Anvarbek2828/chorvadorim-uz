<!DOCTYPE html>

<html lang="uz">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Chorvadorim.uz — Chorva reestri va sun'iy intellektli boshqaruv</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Spectral:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

</head>

<body style="margin:0; background:#FAF6EE; color:#201C16; font-family:'Inter',sans-serif; -webkit-font-smoothing:antialiased;">



<style>

    .serif { font-family: 'Spectral', serif; }

    .mono { font-family: 'IBM Plex Mono', monospace; }

    .rule { border-color: #DDD3BE; }

    .btn-primary { background:#2F4B33; color:#FAF6EE; transition:background .15s ease; }

    .btn-primary:hover { background:#25392A; }

    .btn-ghost { border:1px solid #DDD3BE; color:#201C16; transition:border-color .15s ease, background .15s ease; }

    .btn-ghost:hover { border-color:#B98A2E; background:#F3EDDD; }

    .card { border-top:3px solid #A13D2B; background:#fff; }

    .eyebrow { font-family:'IBM Plex Mono', monospace; letter-spacing:.08em; text-transform:uppercase; font-size:12px; color:#6B6255; }

    .stamp {

        width:150px; height:150px; border-radius:9999px;

        border:2.5px solid #A13D2B; position:relative;

        display:flex; align-items:center; justify-content:center;

        transform:rotate(-8deg); flex-shrink:0;

    }

    .stamp::before {

        content:''; position:absolute; inset:8px; border-radius:9999px; border:1px dashed #A13D2B; opacity:.6;

    }

    .stamp-text { text-align:center; color:#A13D2B; font-family:'IBM Plex Mono', monospace; font-size:10.5px; letter-spacing:.06em; line-height:1.5; text-transform:uppercase; }

    @media (max-width: 767px) { .hide-mobile { display:none; } }

</style>



    <nav class="rule" style="border-bottom:1px solid; position:sticky; top:0; background:#FAF6EE; z-index:50;">

        <div style="max-width:1120px; margin:0 auto; padding:0 24px; display:flex; justify-content:space-between; align-items:center; height:68px;">

            <div class="serif" style="font-weight:600; font-size:19px;">Chorvadorim<span style="color:#A13D2B;">.</span>uz</div>

            <div style="display:flex; gap:20px; align-items:center;">

                <a href="{{ route('login') }}" style="color:#201C16; text-decoration:none; font-size:14px;">Kirish</a>

                <a href="{{ route('register') }}" class="btn-primary" style="padding:9px 18px; border-radius:4px; text-decoration:none; font-size:14px; font-weight:500;">Ro'yxatdan o'tish</a>

            </div>

        </div>

    </nav>



    <section style="max-width:1120px; margin:0 auto; padding:88px 24px 72px; display:flex; align-items:center; gap:56px;">

        <div style="flex:1; min-width:0;">

            <div class="eyebrow" style="margin-bottom:16px;">Chorva reestri &middot; №1 O'zbekiston fermerlari uchun</div>

            <h1 class="serif" style="font-size:44px; line-height:1.15; font-weight:700; margin:0 0 22px; letter-spacing:-0.01em;">

                Har bir molingiz — <br><span style="color:#2F4B33;">ro'yxatda, nazoratda,</span><br>o'z vaqtida yordamda

            </h1>

            <p style="font-size:17px; line-height:1.6; color:#4A443B; max-width:480px; margin:0 0 32px;">

                Zoti, vazni, pasporti — bir joyda. Xarajat va daromadi — hisobda. Savolingizga — sun'iy intellekt javob beradi. Telegram orqali — doim xabardor bo'lasiz.

            </p>

            <div style="display:flex; gap:14px;">

                <a href="{{ route('register') }}" class="btn-primary" style="padding:13px 26px; border-radius:5px; text-decoration:none; font-weight:500; font-size:15px;">Bepul boshlash</a>

                <a href="#imkoniyatlar" class="btn-ghost" style="padding:13px 26px; border-radius:5px; text-decoration:none; font-weight:500; font-size:15px;">Imkoniyatlar</a>

            </div>

        </div>

        <div class="hide-mobile" style="flex-shrink:0; display:flex; align-items:center; justify-content:center; width:280px; height:280px; position:relative;">

            <div class="stamp">

                <div class="stamp-text">&#x1F404;<br>Chorvadorim<br>Rasmiy Reestr</div>

            </div>

        </div>

    </section>



    <section id="imkoniyatlar" style="background:#F3EDDD; padding:80px 0; border-top:1px solid #DDD3BE; border-bottom:1px solid #DDD3BE;">

        <div style="max-width:1080px; margin:0 auto; padding:0 24px;">

            <div style="margin-bottom:48px;">

                <div class="eyebrow" style="margin-bottom:10px;">Bitta tizim</div>

                <h2 class="serif" style="font-size:30px; font-weight:600; margin:0;">Chorvani boshqarish uchun kerak bo'lgan hamma narsa</h2>

            </div>



            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">

                <div class="card" style="padding:26px; border-radius:2px;">

                    <div style="font-size:26px; margin-bottom:10px;">&#x1F404;</div>

                    <h3 style="font-weight:600; font-size:16px; margin:0 0 8px;">Hayvon reestri</h3>

                    <p style="font-size:14px; color:#6B6255; margin:0; line-height:1.55;">Zoti, vazni, pasport va chip raqami — har bir hayvon uchun alohida. O'chirilganlar arxivda saqlanadi.</p>

                </div>

                <div class="card" style="padding:26px; border-radius:2px;">

                    <div style="font-size:26px; margin-bottom:10px;">&#x1F4AC;</div>

                    <h3 style="font-weight:600; font-size:16px; margin:0 0 8px;">AI maslahat</h3>

                    <p style="font-size:14px; color:#6B6255; margin:0; line-height:1.55;">Ozuqa, kasallik, parvarish bo'yicha savol bering — 700+ ilmiy manbaga asoslangan javob olasiz.</p>

                </div>

                <div class="card" style="padding:26px; border-radius:2px;">

                    <div style="font-size:26px; margin-bottom:10px;">&#x1F4F7;</div>

                    <h3 style="font-weight:600; font-size:16px; margin:0 0 8px;">Rasm tahlili</h3>

                    <p style="font-size:14px; color:#6B6255; margin:0; line-height:1.55;">Hayvon rasmini yuklang — tana holati (BCS) va taxminiy vazn avtomatik hisoblanadi.</p>

                </div>

                <div class="card" style="padding:26px; border-radius:2px;">

                    <div style="font-size:26px; margin-bottom:10px;">&#x1F4B0;</div>

                    <h3 style="font-weight:600; font-size:16px; margin:0 0 8px;">Moliyaviy hisob</h3>

                    <p style="font-size:14px; color:#6B6255; margin:0; line-height:1.55;">Xarajat va daromadni har bir hayvon bo'yicha alohida kuzating, foydani aniq ko'ring.</p>

                </div>

                <div class="card" style="padding:26px; border-radius:2px;">

                    <div style="font-size:26px; margin-bottom:10px;">&#x2708;&#xFE0F;</div>

                    <h3 style="font-weight:600; font-size:16px; margin:0 0 8px;">Telegram nazorati</h3>

                    <p style="font-size:14px; color:#6B6255; margin:0; line-height:1.55;">Bank/kredit tashkilotlari uchun garov nazorati — hayvon sotilsa, avtomatik ogohlantirish keladi.</p>

                </div>

                <div class="card" style="padding:26px; border-radius:2px;">

                    <div style="font-size:26px; margin-bottom:10px;">&#x1F310;</div>

                    <h3 style="font-weight:600; font-size:16px; margin:0 0 8px;">Uch til</h3>

                    <p style="font-size:14px; color:#6B6255; margin:0; line-height:1.55;">O'zbek (lotin/kirill) va rus tillarida, shrift kattalashtirish imkoniyati bilan.</p>

                </div>

            </div>

        </div>

    </section>



    <section style="padding:80px 0;">

        <div style="max-width:900px; margin:0 auto; padding:0 24px;">

            <div style="margin-bottom:48px;">

                <div class="eyebrow" style="margin-bottom:10px;">Ishga tushirish</div>

                <h2 class="serif" style="font-size:30px; font-weight:600; margin:0;">Uch qadamda boshlaysiz</h2>

            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:36px;">

                <div>

                    <div class="mono" style="color:#A13D2B; font-weight:600; font-size:13px; margin-bottom:10px;">01</div>

                    <h3 style="font-weight:600; font-size:16px; margin:0 0 8px;">Ro'yxatdan o'tasiz</h3>

                    <p style="font-size:14px; color:#6B6255; margin:0; line-height:1.55;">Ism, email va parol — bir necha soniyada hisob tayyor.</p>

                </div>

                <div>

                    <div class="mono" style="color:#A13D2B; font-weight:600; font-size:13px; margin-bottom:10px;">02</div>

                    <h3 style="font-weight:600; font-size:16px; margin:0 0 8px;">Hayvon qo'shasiz</h3>

                    <p style="font-size:14px; color:#6B6255; margin:0; line-height:1.55;">Asosiy ma'lumotlarni kiritasiz — bir necha daqiqa ketadi.</p>

                </div>

                <div>

                    <div class="mono" style="color:#A13D2B; font-weight:600; font-size:13px; margin-bottom:10px;">03</div>

                    <h3 style="font-weight:600; font-size:16px; margin:0 0 8px;">AI yordamida boshqarasiz</h3>

                    <p style="font-size:14px; color:#6B6255; margin:0; line-height:1.55;">Savol bering, rasm yuklang, Telegram botni bog'lang.</p>

                </div>

            </div>

        </div>

    </section>



    <section style="background:#F3EDDD; border-top:1px solid #DDD3BE; border-bottom:1px solid #DDD3BE; padding:64px 0; text-align:center;">

        <div style="max-width:560px; margin:0 auto; padding:0 24px;">

            <div class="eyebrow" style="margin-bottom:10px;">Narxlar</div>

            <h2 class="serif" style="font-size:26px; font-weight:600; margin:0 0 14px;">Hozircha sinov bosqichida — bepul</h2>

            <p style="font-size:15px; color:#6B6255; line-height:1.6; margin:0;">Platforma hozirda test rejimida, barcha imkoniyatlar bepul. Rasmiy narxlar keyinroq, qulay va shaffof shartlar bilan e'lon qilinadi.</p>

        </div>

    </section>



    <section style="background:#201C16; color:#FAF6EE; padding:64px 0; text-align:center;">

        <div style="max-width:520px; margin:0 auto; padding:0 24px;">

            <h2 class="serif" style="font-size:26px; font-weight:600; margin:0 0 12px;">Chorvangizni bugun raqamlashtiring</h2>

            <p style="color:#B8AF9E; margin:0 0 26px; font-size:15px;">Ro'yxatdan o'tish bepul va bir necha daqiqa vaqt oladi.</p>

            <a href="{{ route('register') }}" style="background:#A13D2B; color:#fff; padding:13px 28px; border-radius:5px; text-decoration:none; font-weight:500; font-size:15px; display:inline-block;">Ro'yxatdan o'tish</a>

        </div>

    </section>



    <footer style="padding:28px 0; text-align:center; font-size:13px; color:#6B6255; border-top:1px solid #DDD3BE;">

        &copy; {{ date('Y') }} Chorvadorim.uz. Barcha huquqlar himoyalangan.

    </footer>



</body>

</html>

