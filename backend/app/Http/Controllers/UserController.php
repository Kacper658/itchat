<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function settings(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'name'     => $user->name,
            'email'    => $user->email,
            'language' => $user->language,
            'currency' => $user->currency,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'language' => ['sometimes', Rule::in(['pl', 'en', 'de', 'fr', 'es'])],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $user->update($data);
        app()->setLocale($user->language);

        return response()->json([
            'user'    => $user->fresh(),
            'message' => 'Ustawienia zapisane.',
        ]);
    }

    /** Zmiana hasła. */
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Bieżące hasło jest nieprawidłowe.'], 422);
        }

        $user->update(['password' => $data['password']]);
        return response()->json(['message' => 'Hasło zmienione.']);
    }

    /** Usunięcie konta (RODO) — soft delete + anonimizacja. */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Hasło jest nieprawidłowe.'], 422);
        }

        // Anonimizacja danych osobowych
        $user->forceFill([
            'name'  => 'Usunięto konto',
            'email' => 'deleted-' . $user->id . '@anonymous.local',
        ])->save();

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Konto zostało usunięte.']);
    }
}
