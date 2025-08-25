<div wire:poll.5s.visible>
    <style>
        @keyframes flashRow{
            0%{background-color:var(--base-bg);color:var(--base-fg);}
            45%{background-color:#ff0000;color:#ffffff;}
            100%{background-color:var(--base-bg);color:var(--base-fg);}
        }
        .flash-once{animation:flashRow 1.1s ease-in-out;}

        /* Stronger base colours */
        table#alerts-table tbody tr.alert-row[data-status="Pending"]{
            background-color:#fca5a5 !important;   /* red-300 */
        }
        table#alerts-table tbody tr.alert-row[data-status="Acknowledged"]{
            background-color:#fdba74 !important;   /* orange-300 */
        }
        table#alerts-table tbody tr.alert-row[data-status="Resolved"]{
            background-color:#86efac !important;   /* green-300 */
        }
        </style>


    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Alerts</h2>
    </div>

    {{-- Search + Status Toggles --}}
    <div class="mb-4 flex flex-col sm:flex-row gap-2 sm:items-center">
        <input
            wire:model.debounce.500ms="search"
            type="text"
            placeholder="Search alerts..."
            class="w-full sm:flex-1 p-2 border rounded"
        />

        <div class="flex gap-2">
            @php
                $statuses = [
                    \App\Models\Alert::STATUS_PENDING      => ['label' => 'Pending',      'color' => 'red'],
                    \App\Models\Alert::STATUS_ACKNOWLEDGED => ['label' => 'Acknowledged', 'color' => 'orange'],
                    \App\Models\Alert::STATUS_RESOLVED     => ['label' => 'Resolved',     'color' => 'green'],
                ];
            @endphp

            @foreach($statuses as $value => $meta)
                @php $active = in_array($value, $statusFilters ?? [], true); @endphp
                <button
                    type="button"
                    wire:click="toggleStatus('{{ $value }}')"
                    class="px-3 py-1 rounded border text-sm transition
                           {{ $active
                                ? "bg-{$meta['color']}-600 text-white border-{$meta['color']}-700"
                                : 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200' }}"
                >
                    {{ $meta['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    <table id="alerts-table" class="min-w-full bg-white border text-sm">
        <thead class="text-left">
            <tr>
                <th class="px-4 py-2 border-b">Camera IP</th>
                <th class="px-4 py-2 border-b">Label</th>
                <th class="px-4 py-2 border-b">Max Temp (°C)</th>
                <th class="px-4 py-2 border-b">Box ID</th>
                <th class="px-4 py-2 border-b">Status</th>
                <th class="px-4 py-2 border-b">Created At</th>
                <th class="px-4 py-2 border-b">Last Updated By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alerts as $alert)
                @php $isNew = in_array($alert->id, $this->flashIds ?? []); @endphp
                <tr wire:key="alert-{{ $alert->id }}"
                    class="alert-row {{ $isNew ? 'flash-once' : '' }}"
                    data-status="{{ $alert->status }}"
                    style="--base-bg: currentColor; --base-fg: #000;">
                    <td class="px-4 py-2 border-b">{{ $alert->camera_ip }}</td>
                    <td class="px-4 py-2 border-b">{{ $alert->label }}</td>
                    <td class="px-4 py-2 border-b">{{ $alert->maxT }}°C</td>
                    <td class="px-4 py-2 border-b">{{ $alert->box_id }}</td>

                    <td class="px-4 py-2 border-b">
                        <select
                            class="border rounded px-2 py-1 text-xs"
                            wire:model="statuses.{{ $alert->id }}"
                        >
                            <option value="{{ \App\Models\Alert::STATUS_PENDING }}">Pending</option>
                            <option value="{{ \App\Models\Alert::STATUS_ACKNOWLEDGED }}">Acknowledged</option>
                            <option value="{{ \App\Models\Alert::STATUS_RESOLVED }}">Resolved</option>
                        </select>
                    </td>

                    <td class="px-4 py-2 border-b">
                        {{ $alert->created_at
                            ->setTimezone('Europe/Dublin')
                            ->format('d M Y, H:i') }}
                        </td>

                    <td class="px-4 py-2 border-b">
                        @if ($alert->status === \App\Models\Alert::STATUS_RESOLVED && $alert->resolvedByUser)
                            {{ $alert->resolvedByUser->name }}
                            at {{ $alert->resolved_at?->setTimezone('Europe/Dublin')->format('d M Y, H:i') }}
                        @elseif ($alert->status === \App\Models\Alert::STATUS_ACKNOWLEDGED && $alert->acknowledgedByUser)
                            {{ $alert->acknowledgedByUser->name }}
                            at {{ $alert->acknowledged_at?->setTimezone('Europe/Dublin')->format('d M Y, H:i') }}
                        @else
                            —
                        @endif
                    </td>    
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-4 text-center text-gray-500">No alerts found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $alerts->links() }}
    </div>
</div>
