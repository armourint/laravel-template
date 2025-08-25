<?php

namespace App\Livewire\Items;

use App\Models\Item;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';

    public function updatingSearch(){ $this->resetPage(); }
    public function updatingStatus(){ $this->resetPage(); }

    public function render()
    {
        $query = Item::query()
            ->when($this->search, fn($q) => $q->where('title','like',"%{$this->search}%"))
            ->when($this->status, fn($q) => $q->where('status',$this->status))
            ->orderByDesc('id');

        return view('livewire.items.table', [
            'items' => $query->paginate(10),
        ]);
    }
}
