<?php

namespace App\Livewire;

use App\Models\Alert;
use Livewire\Component;
use Livewire\WithPagination;

class AlertManagement extends Component
{
    use WithPagination;

    public $search = '';

    // For flashing new rows
    public $seenMaxId = 0;

    public $flashIds = [];

    // For per-row status select
    public $statuses = [];

    public $statusFilters = ['Pending', 'Acknowledged', 'Resolved'];   // e.g. ['Pending', 'Resolved']

    public $userSiteIds = [];

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->seenMaxId = Alert::max('id') ?? 0;

        if (auth()->user()->role === 'engineer') {
            $this->userSiteIds = auth()->user()->sites()->pluck('sites.id')->toArray();
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatuses($value, $key): void
    {
        $this->changeStatus((int) $key, $value);
    }

    public function changeStatus(int $id, string $status): void
    {
        $valid = [
            Alert::STATUS_PENDING,
            Alert::STATUS_ACKNOWLEDGED,
            Alert::STATUS_RESOLVED,
        ];

        if (! in_array($status, $valid, true)) {
            return; // or throw validation exception
        }

        $alert = Alert::findOrFail($id);
        $alert->update(['status' => $status]);

        // keep local state in sync
        $this->statuses[$id] = $status;
    }

    public function toggleStatus(string $status): void
    {
        if (in_array($status, $this->statusFilters, true)) {
            $this->statusFilters = array_values(array_diff($this->statusFilters, [$status]));
        } else {
            $this->statusFilters[] = $status;
        }

        $this->resetPage();
    }

    public function render()
    {
        $alerts = Alert::query()
            ->visibleTo(auth()->user())
            ->with(['acknowledgedByUser', 'resolvedByUser']) // ← Add this
            ->when($this->search, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('camera_ip', 'like', "%{$term}%")
                        ->orWhere('label', 'like', "%{$term}%")
                        ->orWhere('box_id', 'like', "%{$term}%")
                        ->orWhere('maxT', 'like', "%{$term}%");
                });
            })
            ->when($this->statusFilters, fn ($q) => $q->whereIn('status', $this->statusFilters))
            ->orderByDesc('created_at')
            ->paginate(10);

        // Always sync the select‑model to the DB status for each alert
        foreach ($alerts as $a) {
            $this->statuses[$a->id] = $a->status ?? Alert::STATUS_PENDING;
        }

        // New rows flash
        $currentMax = $alerts->max('id') ?? 0;
        $this->flashIds = $alerts->pluck('id')
            ->filter(fn ($id) => $id > $this->seenMaxId)
            ->values()
            ->all();
        $this->seenMaxId = max($this->seenMaxId, $currentMax);

        return view('livewire.alert-management', compact('alerts'));
    }
}
