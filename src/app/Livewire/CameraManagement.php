<?php

namespace App\Livewire;

use App\Models\Agent;
use App\Models\Camera;
use Livewire\Component;
use Livewire\WithPagination;

class CameraManagement extends Component
{
    use WithPagination;

    // Table state
    public string $search = '';

    public int $perPage = 15;

    public string $sortField = 'created_at';

    public string $sortDir = 'desc';

    // Modals / UI state
    public bool $showForm = false;

    public ?int $deleteId = null;

    // Form
    public ?int $cameraId = null;

    public string $label = '';

    public string $ip = '';

    public string $api_key = '';

    public bool $enabled = true;

    public ?string $assigned_agent_uuid = '';

    public ?string $notes = '';

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDir' => ['except' => 'desc'],
        'perPage' => ['except' => 15],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }

        $this->resetPage();
    }

    public function openForm(?int $id = null): void
    {
        $this->resetValidation();

        if ($id) {
            $c = Camera::findOrFail($id);
            $this->cameraId = $c->id;
            $this->label = $c->label;
            $this->ip = $c->ip;
            $this->api_key = ''; // blank means keep existing
            $this->enabled = (bool) $c->enabled;
            $this->assigned_agent_uuid = $c->assigned_agent_uuid ?? '';
            $this->notes = $c->notes ?? '';
        } else {
            $this->cameraId = null;
            $this->label = $this->ip = $this->api_key = '';
            $this->enabled = true;
            $this->assigned_agent_uuid = '';
            $this->notes = '';
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        $rules = [
            'label' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'ip'],
            'enabled' => ['boolean'],
            'assigned_agent_uuid' => ['nullable', 'uuid'],
            'notes' => ['nullable', 'string'],
        ];

        if ($this->cameraId) {
            if ($this->api_key !== '') {
                $rules['api_key'] = ['string'];
            }

            $data = $this->validate($rules);

            $cam = Camera::findOrFail($this->cameraId);

            if ($this->api_key === '') {
                unset($data['api_key']);
            } else {
                $data['api_key'] = $this->api_key;
            }

            $cam->update($data);

            session()->flash('success', 'Camera updated.');
        } else {
            $rules['api_key'] = ['required', 'string'];
            $data = $this->validate($rules);
            Camera::create($data);

            session()->flash('success', 'Camera created.');
        }

        $this->showForm = false;
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            Camera::findOrFail($this->deleteId)->delete();
            $this->deleteId = null;
            session()->flash('success', 'Camera deleted.');
            $this->resetPage();
        }
    }

    public function render()
    {
        $q = trim($this->search);

        $cameras = Camera::query()
            ->when($q, fn ($s) => $s->where(function ($x) use ($q) {
                $x->where('label', 'like', "%$q%")
                    ->orWhere('ip', 'like', "%$q%")
                    ->orWhere('assigned_agent_uuid', 'like', "%$q%");
            }))
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        $agents = Agent::orderBy('name')->get(['uuid', 'name']);

        return view('livewire.camera-management', compact('cameras', 'agents'));
    }
}
