<?php



namespace App\Http\Middleware;



use Closure;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\App;



class SetLocale

{

    // Sessiya va users.locale dagi til kodlari (bot uch tilli matnni shu tartibda yozadi)
    public const LOCALES = ['uz', 'uz_cyrl', 'ru'];

    public function handle(Request $request, Closure $next)

    {

        $locale = session('locale', 'uz');



        if (in_array($locale, self::LOCALES)) {

            App::setLocale($locale);

        }



        return $next($request);

    }

}
