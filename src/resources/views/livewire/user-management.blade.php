<div>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">User Management</h2>
        <button
            wire:click="openModal"
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
            Add User
        </button>
    </div>

    <div class="mb-4">
        <input
            wire:model.debounce.500ms="search"
            type="text"
            placeholder="Search users..."
            class="w-full p-2 border rounded"
        />
    </div>

    <table class="min-w-full bg-white border">
        <thead>
            <tr class="text-left">
                <th class="px-4 py-2 border-b">Name</th>
                <th class="px-4 py-2 border-b">Email</th>
                <th class="px-4 py-2 border-b">Role</th>
                <th class="px-4 py-2 border-b">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border-b">{{ $user->name }}</td>
                    <td class="px-4 py-2 border-b">{{ $user->email }}</td>
                    <td class="px-4 py-2 border-b">{{ ucfirst($user->role) }}</td>
                    <td class="px-4 py-2 border-b flex space-x-2">
                        <button
                            wire:click="editUser({{ $user->id }})"
                            class="text-indigo-600 hover:text-indigo-900"
                        >
                            Edit
                        </button>
                        <button
                            wire:click="confirmDeletion({{ $user->id }})"
                            class="text-red-600 hover:text-red-900"
                        >
                            Delete
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    {{-- Modal --}}
    @if($isModalOpen)
        <div
            wire:ignore.self
            x-data="{ role: @entangle('role'), filter: '' }"
            class="fixed inset-0 flex items-center justify-center bg-black/50 z-[9999]"
        >
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
                <h3 class="text-lg font-semibold mb-4">
                    {{ $user_id ? 'Edit User' : 'Add User' }}
                </h3>

                {{-- Name --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Name</label>
                    <input
                        wire:model.defer="name"
                        type="text"
                        class="w-full p-2 border rounded"
                    />
                    @error('name')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input
                        wire:model.defer="email"
                        type="email"
                        class="w-full p-2 border rounded"
                    />
                    @error('email')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Role --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Role</label>
                    <select
                        wire:model="role"
                        x-model="role"
                        class="w-full p-2 border rounded"
                    >
                        <option value="admin">Admin</option>
                        <option value="engineer">Engineer</option>
                    </select>
                    @error('role')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Sites picker (engineer only) --}}
                <div
                    class="mb-4"
                    x-show="role === 'engineer'"
                    x-transition.opacity
                    wire:key="sites-picker"
                >
                    <label class="block text-sm font-medium mb-1">Sites</label>

                    <div class="flex items-center justify-between mb-2 gap-2">
                        <input
                            x-model="filter"
                            type="text"
                            placeholder="Filter sites..."
                            class="flex-1 p-2 border rounded text-sm"
                        />
                        <div class="flex gap-2 text-xs">
                            <button
                                type="button"
                                class="underline text-blue-600"
                                @click="$wire.set('selected_sites', {{ json_encode(array_keys($allSites)) }})"
                            >
                                Select all
                            </button>
                            <button
                                type="button"
                                class="underline text-gray-600"
                                @click="$wire.set('selected_sites', [])"
                            >
                                Clear
                            </button>
                        </div>
                    </div>

                    <div class="max-h-48 overflow-y-auto border rounded p-2 space-y-1">
                        @foreach($allSites as $id => $siteName)
                            <label
                                class="flex items-center gap-2 text-sm"
                                x-show="`{{ strtolower($siteName) }}`.includes(filter.toLowerCase())"
                            >
                                <input
                                    type="checkbox"
                                    value="{{ $id }}"
                                    wire:model.defer="selected_sites"
                                    class="rounded"
                                />
                                <span>{{ $siteName }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        @foreach($selected_sites as $sid)
                            @php $label = $allSites[$sid] ?? 'Unknown'; @endphp
                            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full">
                                {{ $label }}
                            </span>
                        @endforeach
                    </div>

                    @error('selected_sites')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                    @error('selected_sites.*')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Password (create only) --}}
                @if(!$user_id)
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Password</label>
                        <input
                            wire:model.defer="password"
                            type="password"
                            class="w-full p-2 border rounded"
                        />
                        @error('password')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Confirm Password</label>
                        <input
                            wire:model.defer="password_confirmation"
                            type="password"
                            class="w-full p-2 border rounded"
                        />
                    </div>
                @endif

                <div class="flex justify-end space-x-2">
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="px-4 py-2 rounded border hover:bg-gray-100"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        wire:click.prevent="{{ $user_id ? 'updateUser' : 'createUser' }}"
                        class="px-4 py-2 rounded text-white {{ $user_id ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700' }}"
                    >
                        {{ $user_id ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation --}}
    @if($confirmingUserDeletion)
        <div
            wire:ignore.self
            class="fixed inset-0 flex items-center justify-center bg-black/50 z-[9999]"
        >
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm">
                <h3 class="text-lg font-semibold mb-4">Confirm Deletion</h3>
                <p class="mb-4">Are you sure you want to delete this user?</p>
                <div class="flex justify-end space-x-2">
                    <button
                        wire:click="$set('confirmingUserDeletion', false)"
                        class="px-4 py-2 border rounded hover:bg-gray-100"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="deleteUser({{ $confirmingUserDeletion }})"
                        class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
