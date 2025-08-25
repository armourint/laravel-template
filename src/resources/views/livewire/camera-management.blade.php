<div class="space-y-4">
    @if (session('success'))
        <div class="rounded bg-green-100 text-green-800 px-4 py-2">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-xl font-semibold">Cameras</h1>
            <p class="text-sm text-gray-500">Assign cameras to agents and manage credentials.</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="relative">
                <input type="text" wire:model.debounce.400ms="search"
                    placeholder="Search cameras…"
                    class="border rounded pl-9 pr-3 py-2 w-64">
                <span class="absolute left-2 top-2.5 text-gray-400">🔎</span>
            </div>
            <select wire:model="perPage" class="border rounded px-2 py-2">
                <option>10</option><option>15</option><option>25</option><option>50</option>
            </select>
            <button class="bg-indigo-600 text-white px-3 py-2 rounded" wire:click="openForm">New</button>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left">
                    @php
                        $th = 'px-4 py-2 cursor-pointer select-none';
                        $sortIcon = fn($f) => $sortField === $f ? ($sortDir === 'asc' ? '▲' : '▼') : ' ';
                    @endphp
                    <th class="{{ $th }}" wire:click="sortBy('label')">Label {{ $sortIcon('label') }}</th>
                    <th class="{{ $th }}" wire:click="sortBy('ip')">IP {{ $sortIcon('ip') }}</th>
                    <th class="{{ $th }}" wire:click="sortBy('assigned_agent_uuid')">Agent {{ $sortIcon('assigned_agent_uuid') }}</th>
                    <th class="{{ $th }}" wire:click="sortBy('enabled')">Enabled {{ $sortIcon('enabled') }}</th>
                    <th class="px-4 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cameras as $c)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $c->label }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $c->ip }}</td>
                        <td class="px-4 py-2">
                            @php $agent = $agents->firstWhere('uuid', $c->assigned_agent_uuid); @endphp
                            {{ $agent?->name ?? ($c->assigned_agent_uuid ? Str::limit($c->assigned_agent_uuid, 10) : '—') }}
                        </td>
                        <td class="px-4 py-2">
                            @if($c->enabled)
                                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">Enabled</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-700">Disabled</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button class="px-2 py-1 rounded bg-gray-200" wire:click="openForm({{ $c->id }})">Edit</button>
                            <button class="ml-2 px-2 py-1 rounded bg-red-600 text-white" wire:click="confirmDelete({{ $c->id }})">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No cameras.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $cameras->links() }}</div>

    {{-- Delete confirm --}}
    @if ($deleteId)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-40">
            <div class="bg-white rounded p-6 w-full max-w-sm shadow-lg">
                <p class="mb-4">Delete this camera?</p>
                <div class="flex justify-end gap-2">
                    <button class="px-3 py-2 rounded bg-gray-200" wire:click="$set('deleteId', null)">Cancel</button>
                    <button class="px-3 py-2 rounded bg-red-600 text-white" wire:click="delete">Delete</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Create / Edit modal --}}
    @if ($showForm)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-40">
            <div class="bg-white rounded p-6 w-full max-w-lg shadow-lg">
                <h3 class="text-lg font-semibold mb-4">{{ $cameraId ? 'Edit Camera' : 'New Camera' }}</h3>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Label</label>
                        <input class="border rounded px-3 py-2 w-full" wire:model.defer="label">
                        @error('label') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">IP Address</label>
                        <input class="border rounded px-3 py-2 w-full" wire:model.defer="ip">
                        @error('ip') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            API Key
                            @if($cameraId) <span class="text-xs text-gray-500">(leave blank to keep existing)</span> @endif
                        </label>
                        <input class="border rounded px-3 py-2 w-full" wire:model.defer="api_key">
                        @error('api_key') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Assigned Agent</label>
                        <select class="border rounded px-3 py-2 w-full" wire:model.defer="assigned_agent_uuid">
                            <option value="">— Unassigned —</option>
                            @foreach ($agents as $a)
                                <option value="{{ $a->uuid }}">{{ $a->name ?? $a->uuid }}</option>
                            @endforeach
                        </select>
                        @error('assigned_agent_uuid') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="mr-2" wire:model.defer="enabled">
                            <span>Enabled</span>
                        </label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Notes</label>
                        <textarea rows="3" class="border rounded px-3 py-2 w-full" wire:model.defer="notes"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button class="px-3 py-2 rounded bg-gray-200" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="px-3 py-2 rounded bg-indigo-600 text-white" wire:click="save">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
