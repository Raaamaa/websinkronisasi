<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use App\Models\Lk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvidenceController extends Controller
{
    public function index(): View
    {
        return view('manage.evidence', [
            'lks' => Lk::query()->orderBy('scope')->orderBy('name')->get(),
            'evidences' => Evidence::with(['lk', 'uploader'])->latest()->paginate(25),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lk_id' => ['required', 'integer', 'exists:lks,id'],
            'title' => ['required', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'drive_url' => ['required', 'url', 'max:2048'],
            'file' => ['required', 'file', 'mimes:pdf,docx,xlsx,jpg,jpeg,png', 'max:20480'],
        ]);

        $file = $request->file('file');
        $sha256 = hash_file('sha256', $file->getRealPath());

        if (! is_string($sha256)) {
            return back()->withErrors(['file' => 'File gagal dibaca untuk pemeriksaan hash.']);
        }

        $evidence = Evidence::firstOrCreate(
            [
                'lk_id' => $validated['lk_id'],
                'sha256' => $sha256,
            ],
            [
                'uploaded_by' => $request->user()->id,
                'title' => $validated['title'],
                'document_number' => $validated['document_number'] ?? null,
                'original_filename' => $file->getClientOriginalName(),
                'drive_url' => $validated['drive_url'],
            ],
        );

        if (! $evidence->wasRecentlyCreated) {
            return back()->withErrors(['file' => 'File identik sudah terdaftar pada LK tersebut.']);
        }

        return back()->with('status', 'Bukti dukung berhasil dicatat. File unggahan tidak disimpan di aplikasi.');
    }

    public function destroy(Evidence $evidence): RedirectResponse
    {
        $evidence->delete();

        return back()->with('status', 'Catatan bukti dukung berhasil dihapus.');
    }
}
