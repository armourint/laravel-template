@extends('layouts.admin')

@section('content')
    <h2 class="text-lg font-semibold mb-4">Recent Alerts</h2>

    <table class="min-w-full bg-white shadow rounded">
        <thead class="bg-gray-200">
            <tr>
                <th class="px-4 py-2 text-left text-sm font-medium">ROI</th>
                <th class="px-4 py-2 text-left text-sm font-medium">Temperature</th>
                <th class="px-4 py-2 text-left text-sm font-medium">Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alerts as $alert)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $alert->roi }}</td>
                    <td class="px-4 py-2">{{ $alert->temperature }}</td>
                    <td class="px-4 py-2">{{ $alert->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
