<?php

namespace App\Http\Controllers;

use App\Services\GeoService;
use Illuminate\Http\Request;

class GeoController extends Controller
{
    public function __construct(
        private GeoService $geo,
    ) {}

    /** GET /api/geo/detect — wykrycie języka i waluty na podstawie IP. */
    public function detect(Request $request)
    {
        $data = $this->geo->detect($request->ip());

        // Zapis w sesji dla niezalogowanych
        session([
            'locale'   => $data['language'],
            'currency' => $data['currency'],
        ]);

        // Nie zwracamy IP klientowi (RODO)
        return response()->json([
            'country'  => $data['country'],
            'language' => $data['language'],
            'currency' => $data['currency'],
        ]);
    }
}
