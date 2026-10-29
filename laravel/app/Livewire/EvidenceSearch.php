<?php

namespace App\Livewire;

use App\Models\Evidence;
use App\Models\Lk;
use Livewire\Attributes\Url;
use Livewire\Component;

class EvidenceSearch extends Component
{
    #[Url(as: 'q')]
    public string $query = '';

    public function render()
    {
        $lks = Lk::query()->orderBy('scope')->orderBy('name')->get();
        $groups = collect();
        $term = mb_strtolower(trim($this->query));

        if ($term !== '') {
            $hashes = Evidence::query()
                ->where(function ($query) use ($term): void {
                    foreach (['title', 'document_number', 'original_filename'] as $column) {
                        $query->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", ['%'.$term.'%']);
                    }
                })
                ->select('sha256')
                ->distinct()
                ->limit(100)
                ->pluck('sha256');

            if ($hashes->isNotEmpty()) {
                $groups = Evidence::with('lk')
                    ->whereIn('sha256', $hashes)
                    ->orderBy('title')
                    ->get()
                    ->groupBy('sha256');
            }
        }

        return view('livewire.evidence-search', compact('groups', 'lks'));
    }
}
