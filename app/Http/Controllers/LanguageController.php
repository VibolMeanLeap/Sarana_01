<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(['en', 'km'])],
        ]);

        $request->session()->put('locale', $validated['locale']);

        return redirect()->back()->withHeaders([
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
