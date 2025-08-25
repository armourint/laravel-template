<?php

namespace App\Livewire;

use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    // Search & pagination
    public $search = '';

    protected $paginationTheme = 'tailwind';

    // Modal & deletion state
    public $isModalOpen = false;

    public $confirmingUserDeletion = false;

    // Form fields
    public $user_id;

    public $name = '';

    public $email = '';

    public $role = 'engineer';

    public $password = '';

    public $password_confirmation = '';

    // Site assignment (engineers only)
    public $selected_sites = [];

    public $allSites = [];

    public function mount(): void
    {
        $this->allSites = Site::orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * When switching to admin clear sites + related validation errors.
     */
    public function updatedRole($value): void
    {
        if ($value === 'admin') {
            $this->selected_sites = [];
            $this->resetValidation(['selected_sites', 'selected_sites.*']);
        } else {
            echo '1234';
        }
    }

    public function render()
    {
        $query = User::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(10);

        return view('livewire.user-management', compact('users'));
    }

    public function openModal(): void
    {
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
    }

    private function resetForm(): void
    {
        $this->user_id = null;
        $this->name = '';
        $this->email = '';
        $this->role = 'engineer';
        $this->password = '';
        $this->password_confirmation = '';
        $this->selected_sites = [];
    }

    public function createUser(): void
    {
        $this->validate($this->rules());

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'password' => Hash::make($this->password),
        ]);

        if ($this->role === 'engineer') {
            $user->sites()->sync($this->selected_sites);
        }

        session()->flash('status', 'User created successfully.');
        $this->closeModal();
        $this->resetPage();
    }

    public function editUser($id): void
    {
        $user = User::findOrFail($id);

        $this->user_id = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;

        $this->selected_sites = $user->role === 'engineer'
            ? $user->sites()->pluck('sites.id')->toArray()
            : [];

        $this->resetValidation(['password', 'password_confirmation']);
        $this->isModalOpen = true;
    }

    public function updateUser(): void
    {
        $this->validate($this->rules(update: true));

        $user = User::findOrFail($this->user_id);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ]);

        if ($this->role === 'engineer') {
            $user->sites()->sync($this->selected_sites);
        } else {
            $user->sites()->detach();
        }

        session()->flash('status', 'User updated successfully.');
        $this->closeModal();
        $this->resetPage();
    }

    public function confirmDeletion($id): void
    {
        $this->confirmingUserDeletion = $id;
    }

    public function deleteUser($id = null): void
    {
        $id = $id ?? $this->confirmingUserDeletion;
        $user = User::findOrFail($id);

        $user->sites()->detach();
        $user->delete();

        session()->flash('status', 'User deleted successfully.');
        $this->confirmingUserDeletion = false;
        $this->resetPage();
    }

    /**
     * Validation rules helper.
     */
    protected function rules(bool $update = false): array
    {
        $base = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email'.($update ? ','.$this->user_id : ''),
            'role' => 'required|in:admin,engineer',
        ];

        // Password rules
        if ($update) {
            // only validate if field filled (you can add password change UI later)
            if ($this->password) {
                $base['password'] = 'nullable|min:8|confirmed';
            }
        } else {
            $base['password'] = 'required|min:8|confirmed';
        }

        // Site rules (engineer must have >=1)
        $base['selected_sites'] = 'exclude_if:role,admin|required_if:role,engineer|array|min:1';
        $base['selected_sites.*'] = 'exists:sites,id';

        return $base;
    }
}
