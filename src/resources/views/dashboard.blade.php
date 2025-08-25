@extends('layouts.admin')

@section('content')
  <div class="bg-white rounded-xl shadow p-6">
    <h1 class="text-xl font-semibold mb-4">Dashboard</h1>
    <p class="text-gray-600 mb-6">This is your starter dashboard. Replace with your own metrics.</p>
    @livewire('items.table')
  </div>
@endsection
