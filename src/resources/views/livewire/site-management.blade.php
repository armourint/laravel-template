@props(['allSites' => $this->allSites])

<div x-data="{ sites: @entangle('allSites').defer }"
     x-effect="if (Array.isArray(sites)) initMap(sites)">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Site Management</h2>
        <button wire:click="openModal"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Add Site
        </button>
    </div>

    <div class="mb-4">
        <input wire:model.debounce.500ms="search"
               type="text"
               placeholder="Search sites..."
               class="w-full p-2 border rounded" />
    </div>

    @php use Illuminate\Support\Str; @endphp

    <table class="min-w-full bg-white border">
        <thead>
            <tr class="text-left">
                <th class="px-4 py-2 border-b">Name</th>
                <th class="px-4 py-2 border-b">Code</th>
                <th class="px-4 py-2 border-b">Description</th>
                <th class="px-4 py-2 border-b">Latitude</th>
                <th class="px-4 py-2 border-b">Longitude</th>
                <th class="px-4 py-2 border-b">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sites as $site)
                <tr wire:key="site-{{ $site->id }}"
                    data-site-id="{{ $site->id }}"
                    class="hover:bg-gray-50 cursor-pointer">
                    <td class="px-4 py-2 border-b">{{ $site->name }}</td>
                    <td class="px-4 py-2 border-b">{{ $site->code }}</td>
                    <td class="px-4 py-2 border-b">{{ Str::limit($site->description, 60) }}</td>
                    <td class="px-4 py-2 border-b">{{ $site->latitude }}</td>
                    <td class="px-4 py-2 border-b">{{ $site->longitude }}</td>
                    <td class="px-4 py-2 border-b flex space-x-2">
                        <button wire:click.stop="editSite({{ $site->id }})" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                        <button wire:click.stop="confirmDeletion({{ $site->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $sites->links() }}
    </div>

    {{-- Map --}}
    <div x-data="siteMapComponent({ sites: @js($allSites) })"
        x-init="render()"
        x-on:sites-updated.window="refresh()"
        class="mt-6"
        wire:ignore>
        <div id="sites-map" class="w-full h-96 rounded border"></div>
    </div>

    @push('scripts')
    <script>
    function siteMapComponent({ sites }) {
        let map = null;
        let markers = {};

        return {
            sites,

            render() {
                const el = document.getElementById('sites-map');
                if (!el || typeof L === 'undefined') return;

                map = L.map(el, { scrollWheelZoom: true });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                this.drawMarkers();
            },

            refresh() {
                fetch(window.location.href, {
                    headers: { 'X-Livewire': true }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const raw = doc.querySelector('script[data-sites-json]');
                    if (raw) {
                        this.sites = JSON.parse(raw.innerHTML);
                        this.drawMarkers();
                    }
                });
            },

            drawMarkers() {
                if (!map) return;

                // Remove old markers
                Object.values(markers).forEach(marker => marker.remove());
                markers = {};

                const bounds = [];

                this.sites.forEach(site => {
                    if (!site.latitude || !site.longitude) return;

                    const marker = L.marker([site.latitude, site.longitude])
                        .addTo(map)
                        .bindPopup(`<strong>${site.name}</strong><br>${site.description || ''}`);

                    markers[site.id] = marker;
                    bounds.push([site.latitude, site.longitude]);

                    marker.on('popupopen', () => {
                        const row = document.querySelector(`tr[data-site-id="${site.id}"]`);
                        if (row) {
                            document.querySelectorAll('tr[data-site-id]')
                                .forEach(r => r.classList.remove('bg-yellow-100'));
                            row.classList.add('bg-yellow-100');
                            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    });
                });

                if (bounds.length) {
                    map.fitBounds(bounds, { padding: [30, 30] });
                } else {
                    map.setView([53.35, -6.26], 6); // Default view
                }

                setTimeout(() => map.invalidateSize(), 100);
                this.bindRowClicks();
            },

            bindRowClicks() {
                document.querySelectorAll('tr[data-site-id]').forEach(row => {
                    row.onclick = (e) => {
                        if (e.target.closest('button')) return;
                        const id = row.dataset.siteId;
                        const marker = markers[id];
                        if (!marker) return;

                        map.fitBounds(Object.values(markers).map(m => m.getLatLng()), { padding: [30, 30] });
                        marker.openPopup();

                        document.querySelectorAll('tr[data-site-id]')
                            .forEach(r => r.classList.remove('bg-yellow-100'));
                        row.classList.add('bg-yellow-100');
                    };
                });
            }
        };
    }
    </script>
    @endpush

    {{-- Embed fresh allSites for fetch() fallback --}}
    <script data-sites-json type="application/json">
        {!! $allSites->toJson() !!}
    </script>

    {{-- Modal --}}
    @if($isModalOpen)
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[9999]">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
                <h3 class="text-lg font-semibold mb-4">
                    {{ $site_id ? 'Edit Site' : 'Add Site' }}
                </h3>
                <form wire:submit.prevent="{{ $site_id ? 'updateSite' : 'createSite' }}">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <input wire:model.defer="name" type="text" class="w-full p-2 border rounded" />
                        @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Code</label>
                        <input wire:model.defer="code" type="text" class="w-full p-2 border rounded" />
                        @error('code') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Description</label>
                        <textarea wire:model.defer="description" rows="3" class="w-full p-2 border rounded"></textarea>
                        @error('description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Latitude</label>
                        <input wire:model.defer="latitude" type="number" step="0.0000001" class="w-full p-2 border rounded" />
                        @error('latitude') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium mb-1">Longitude</label>
                        <input wire:model.defer="longitude" type="number" step="0.0000001" class="w-full p-2 border rounded" />
                        @error('longitude') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="button" wire:click="closeModal" class="mr-2 px-4 py-2 rounded border">Cancel</button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            {{ $site_id ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation --}}
    @if($confirmingSiteDeletion)
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[9999]">
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm">
                <h3 class="text-lg font-semibold mb-4">Confirm Deletion</h3>
                <p class="mb-4">Are you sure you want to delete this site?</p>
                <div class="flex justify-end space-x-2">
                    <button wire:click="$set('confirmingSiteDeletion', false)" class="px-4 py-2 border rounded">Cancel</button>
                    <button wire:click="deleteSite({{ $confirmingSiteDeletion }})" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
