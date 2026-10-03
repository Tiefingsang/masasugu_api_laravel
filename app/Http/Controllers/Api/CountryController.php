<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;

/**
 * CountryController — Liste des pays supportés par Masasugu
 */
class CountryController extends Controller
{
    /**
     * 🌍 GET /api/countries
     *
     * Retourne tous les pays actifs avec leur devise et drapeau.
     * Utilisé par Flutter pour le dropdown "Choisir mon pays".
     */
    public function index(Request $request)
    {
        $countries = Country::active()
            ->orderBy('region')
            ->orderBy('name')
            ->get()
            ->map(function ($country) {
                return [
                    'code' => $country->code,
                    'name' => $country->name,
                    'name_en' => $country->name_en,
                    'currency_code' => $country->currency_code,
                    'phone_code' => $country->phone_code,
                    'flag' => $country->flag,
                    'region' => $country->region,
                ];
            });

        // Grouper par région pour un meilleur affichage
        $grouped = $countries->groupBy('region');

        return response()->json([
            'countries' => $countries,
            'grouped_by_region' => $grouped,
            'total' => $countries->count(),
        ]);
    }
}
