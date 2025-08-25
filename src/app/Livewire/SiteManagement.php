<?php

namespace App\Livewire;

use App\Models\Site;
use Livewire\Component;
use Livewire\WithPagination;

class SiteManagement extends Component
{
    use WithPagination;

    public $search = '';

    public $isModalOpen = false;

    public $confirmingSiteDeletion = false;

    public $site_id;

    public $name = '';

    public $code = '';

    public $description = '';

    public $latitude = '';

    public $longitude = '';

    protected $paginationTheme = 'tailwind';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Site::visibleTo(auth()->user());

        if ($this->search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")
            );
        }

        $sites = $query->orderBy('name')->paginate(10);

        $allSites = Site::visibleTo(auth()->user())
            ->select('id', 'name', 'code', 'description', 'latitude', 'longitude')
            ->get();

        return view('livewire.site-management', compact('sites', 'allSites'));
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    private function resetForm()
    {
        $this->site_id = null;
        $this->name = '';
        $this->description = '';
        $this->code = '';
        $this->latitude = '';
        $this->longitude = '';
    }

    public function createSite()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:sites,name',
            'description' => 'nullable|string',
            'code' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        Site::create([
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ]);

        session()->flash('status', 'Site created successfully.');
        $this->closeModal();
        $this->resetPage();
        $this->dispatch('sites-updated');
    }

    public function editSite($id)
    {
        $site = Site::findOrFail($id);

        $this->site_id = $id;
        $this->name = $site->name;
        $this->code = $site->code;
        $this->description = $site->description;
        $this->latitude = $site->latitude;
        $this->longitude = $site->longitude;

        $this->resetValidation();
        $this->isModalOpen = true;
    }

    public function updateSite()
    {
        $this->validate([
            'site_id' => 'required|exists:sites,id',
            'name' => "required|string|max:255|unique:sites,name,{$this->site_id}",
            'code' => "required|string|unique:sites,code,{$this->site_id}",
            'description' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $site = Site::findOrFail($this->site_id);
        $site->update([
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ]);

        session()->flash('status', 'Site updated successfully.');
        $this->closeModal();
        $this->resetPage();
        $this->dispatch('sites-updated');
    }

    public function confirmDeletion($id)
    {
        $this->confirmingSiteDeletion = $id;
    }

    public function deleteSite($id = null)
    {
        $id = $id ?? $this->confirmingSiteDeletion;
        Site::findOrFail($id)->delete();

        session()->flash('status', 'Site deleted successfully.');
        $this->confirmingSiteDeletion = false;
        $this->resetPage();
    }

    public function getAllSitesProperty()
    {
        return Site::visibleTo(auth()->user())
            ->select('id', 'name', 'code', 'description', 'latitude', 'longitude')
            ->get();
    }
}
