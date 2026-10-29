<?php

namespace App\Http\Controllers;

use App\Models\Lk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LkController extends Controller
{
    public function index(): View
    {
        return view('manage.lks', [
            'lks' => Lk::withCount('evidences')->orderBy('scope')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:lks,name'],
            'scope' => ['required', 'in:internal,external'],
        ]);

        Lk::create($validated);

        return back()->with('status', 'Lembar Kerja berhasil ditambahkan.');
    }

    public function destroy(Lk $lk): RedirectResponse
    {
        if ($lk->evidences()->exists()) {
            return back()->withErrors(['lk' => 'Hapus atau pindahkan bukti dukung pada LK ini terlebih dahulu.']);
        }

        $lk->delete();

        return back()->with('status', 'Lembar Kerja berhasil dihapus.');
    }
}
