<div class="space-y-4" wire:poll.30s>
    @if (session('success'))
        <div class="rounded bg-green-100 text-green-800 px-4 py-2">{{ session('success') }}</div>
    @endif

    @if ($tokenOnce)
        <div class="rounded bg-yellow-100 text-yellow-900 px-4 py-2">
            <div class="font-semibold">Agent Token (copy now):</div>
            <code class="break-all">{{ $tokenOnce }}</code>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-xl font-semibold">Agents</h1>
            <p class="text-sm text-gray-500">Manage Windows hosts running the camera agent.</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="relative">
                <input type="text" wire:model.debounce.400ms="search"
                    placeholder="Search agents…"
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
                    <th class="{{ $th }}" wire:click="sortBy('name')">Name {{ $sortIcon('name') }}</th>
                    <th class="{{ $th }}" wire:click="sortBy('uuid')">UUID {{ $sortIcon('uuid') }}</th>
                    <th class="{{ $th }}" wire:click="sortBy('hostname')">Host {{ $sortIcon('hostname') }}</th>
                    <th class="{{ $th }}" wire:click="sortBy('last_seen_at')">Last Seen {{ $sortIcon('last_seen_at') }}</th>
                    <th class="{{ $th }}" wire:click="sortBy('watcher_count')">Watchers {{ $sortIcon('watcher_count') }}</th>
                    <th class="px-4 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($agents as $a)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $a->name ?? '—' }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $a->uuid }}</td>
                        <td class="px-4 py-2">{{ $a->hostname ?? $a->host_label ?? '—' }}</td>
                        <td class="px-4 py-2">{{ optional($a->last_seen_at)->diffForHumans() ?? 'Never' }}</td>
                        <td class="px-4 py-2">{{ $a->watcher_count }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button class="px-2 py-1 rounded bg-gray-200" wire:click="openForm({{ $a->id }})">Edit</button>
                            <button class="ml-2 px-2 py-1 rounded bg-yellow-600 text-white" wire:click="rotateToken({{ $a->id }})">Rotate</button>
                            <button class="ml-2 px-2 py-1 rounded bg-red-600 text-white" wire:click="confirmDelete({{ $a->id }})">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No agents.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $agents->links() }}</div>

    {{-- Delete confirm --}}
    @if ($deleteId)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-40">
            <div class="bg-white rounded p-6 w-full max-w-sm shadow-lg">
                <p class="mb-4">Delete this agent?</p>
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
                <h3 class="text-lg font-semibold mb-4">{{ $agentId ? 'Edit Agent' : 'New Agent' }}</h3>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <input class="border rounded px-3 py-2 w-full" wire:model.defer="name">
                        @error('name') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Host Label</label>
                        <input class="border rounded px-3 py-2 w-full" wire:model.defer="host_label">
                        @error('host_label') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Notes</label>
                        <textarea rows="3" class="border rounded px-3 py-2 w-full" wire:model.defer="notes"></textarea>
                        @error('notes') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
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
