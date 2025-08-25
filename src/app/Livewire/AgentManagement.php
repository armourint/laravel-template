<?php

namespace App\Livewire;

use App\Models\Agent;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class AgentManagement extends Component
{
    use WithPagination;

    // Table state
    public string $search = '';

    public int $perPage = 15;

    public string $sortField = 'last_seen_at';

    public string $sortDir = 'desc';

    // Modals / UI state
    public bool $showForm = false;

    public ?int $deleteId = null;

    // Form fields
    public ?int $agentId = null;

    public ?string $name = null;

    public ?string $host_label = null;

    public ?string $notes = null;

    // One-time token display after create/rotate
    public ?string $tokenOnce = null;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'last_seen_at'],
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
        $this->tokenOnce = null;

        if ($id) {
            $a = Agent::findOrFail($id);
            $this->agentId = $a->id;
            $this->name = $a->name;
            $this->host_label = $a->host_label;
            $this->notes = $a->notes;
        } else {
            $this->agentId = null;
            $this->name = $this->host_label = $this->notes = null;
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'host_label' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($this->agentId) {
            $a = Agent::findOrFail($this->agentId);
            $a->update($data);
            session()->flash('success', 'Agent updated.');
        } else {
            $a = new Agent($data);
            $a->uuid = (string) Str::uuid();
            $this->tokenOnce = $a->rotateToken(); // also saves
            session()->flash('success', 'Agent created. Copy the token now — it will not be shown again.');
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
            Agent::findOrFail($this->deleteId)->delete();
            $this->deleteId = null;
            session()->flash('success', 'Agent deleted.');
            $this->resetPage();
        }
    }

    public function rotateToken(int $id): void
    {
        $a = Agent::findOrFail($id);
        $this->tokenOnce = $a->rotateToken();
        session()->flash('success', 'Token rotated. Copy it now — it will not be shown again.');
    }

    public function render()
    {
        $q = trim($this->search);

        $agents = Agent::query()
            ->when($q, fn ($s) => $s->where(function ($x) use ($q) {
                $x->where('name', 'like', "%$q%")
                    ->orWhere('uuid', 'like', "%$q%")
                    ->orWhere('host_label', 'like', "%$q%");
            }))
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        return view('livewire.agent-management', compact('agents'));
    }
}
