<div class="space-y-4">
  <div class="flex gap-2">
    <input type="text" wire:model.live="search" placeholder="Search title…" class="border rounded px-3 py-2 w-full">
    <select wire:model.live="status" class="border rounded px-3 py-2">
      <option value="">All</option>
      <option value="draft">Draft</option>
      <option value="active">Active</option>
      <option value="archived">Archived</option>
    </select>
  </div>

  <div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="min-w-full">
      <thead>
      <tr class="bg-gray-50 text-left text-sm">
        <th class="px-4 py-3">ID</th>
        <th class="px-4 py-3">Title</th>
        <th class="px-4 py-3">Status</th>
        <th class="px-4 py-3">Created</th>
      </tr>
      </thead>
      <tbody>
      @foreach($items as $item)
        <tr class="border-t">
          <td class="px-4 py-3">{{ $item->id }}</td>
          <td class="px-4 py-3">{{ $item->title }}</td>
          <td class="px-4 py-3">
            <span class="px-2 py-1 rounded text-xs
              @class([
                'bg-yellow-100 text-yellow-800' => $item->status==='draft',
                'bg-green-100 text-green-800' => $item->status==='active',
                'bg-gray-200 text-gray-700' => $item->status==='archived',
              ])">
              {{ ucfirst($item->status) }}
            </span>
          </td>
          <td class="px-4 py-3">{{ $item->created_at->format('Y-m-d H:i') }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div>{{ $items->links() }}</div>
</div>
