{{-- resources/views/layouts/admin.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Panel</title>

  <script src="https://cdn.tailwindcss.com"></script>
  

  @livewireStyles

  {{-- Leaflet (used on Sites page) --}}
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body
  class="bg-gray-100 min-h-screen"
  x-data="{ sidebarOpen: false }"
  @resize.window="if (window.innerWidth >= 768) sidebarOpen = false"
>
  @php
      $isAdmin = auth()->check() && auth()->user()->role === 'admin';
  @endphp

  {{-- Mobile Backdrop --}}
  <div
    class="fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity duration-300"
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-50"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-50"
    x-transition:leave-end="opacity-0"
    style="display: none;"
    @click="sidebarOpen = false"
  ></div>

  {{-- Mobile Sidebar --}}
  <div
    class="fixed inset-y-0 left-0 w-[280px] bg-white z-50 border-r transform md:hidden"
    x-show="sidebarOpen"
    x-transition:enter="transition ease-in-out duration-300 transform"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in-out duration-300 transform"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    style="display: none;"
  >
    <div class="h-16 flex items-center justify-between px-4 border-b">
      <img src="{{ asset('images/logo.svg') }}" alt="Logo" class="h-10">
      <button @click="sidebarOpen = false" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-2">
      <a href="{{ route('admin.alerts') }}"
         class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.alerts') ? 'bg-gray-200 font-semibold' : '' }}">
        Alerts
      </a>

      @if($isAdmin)
        <a href="{{ route('admin.users') }}"
           class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.users') ? 'bg-gray-200 font-semibold' : '' }}">
          User Management
        </a>
        <a href="{{ route('admin.sites') }}"
           class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.sites') ? 'bg-gray-200 font-semibold' : '' }}">
          Site Management
        </a>
         <a href="{{ route('admin.agents') }}"
           class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.agents') ? 'bg-gray-200 font-semibold' : '' }}">
          Agent/PC Management
        </a>
         <a href="{{ route('admin.cameras') }}"
           class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.cameras') ? 'bg-gray-200 font-semibold' : '' }}">
          Camera Management
        </a>
      @endif
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="p-4 border-t">
      @csrf
      <button type="submit" class="w-full text-left text-red-600 hover:text-red-800">Logout</button>
    </form>
  </div>

  {{-- Desktop Sidebar & Main Content --}}
  <div class="flex">
    {{-- Desktop Sidebar --}}
    <aside class="hidden md:flex md:flex-col w-64 bg-white border-r">
      <div class="h-16 flex items-center justify-center border-b">
        <img src="{{ asset('images/logo.svg') }}" alt="Logo" class="h-10">
      </div>
      <nav class="flex-1 px-4 py-6 space-y-2">
        <a href="{{ route('admin.alerts') }}"
           class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.alerts') ? 'bg-gray-200 font-semibold' : '' }}">
          Alerts
        </a>

        @if($isAdmin)
          <a href="{{ route('admin.users') }}"
             class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.users') ? 'bg-gray-200 font-semibold' : '' }}">
            User Management
          </a>
          <a href="{{ route('admin.sites') }}"
             class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.sites') ? 'bg-gray-200 font-semibold' : '' }}">
            Site Management
          </a>
          <a href="{{ route('admin.agents') }}"
             class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.agents') ? 'bg-gray-200 font-semibold' : '' }}">
            Agent Management
          </a>
          <a href="{{ route('admin.cameras') }}"
             class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('admin.cameras') ? 'bg-gray-200 font-semibold' : '' }}">
            Camera Management
          </a>
        @endif
      </nav>
      <form method="POST" action="{{ route('logout') }}" class="p-4 border-t">
        @csrf
        <button type="submit" class="w-full text-left text-red-600 hover:text-red-800">Logout</button>
      </form>
    </aside>

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col">
      <header class="h-16 bg-white shadow flex items-center px-6 md:px-8">
        <button class="md:hidden mr-4 text-2xl" @click="sidebarOpen = true">☰</button>
        <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
      </header>

      <main class="flex-1 overflow-y-auto p-6">
        @if (session('status'))
          <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
            {{ session('status') }}
          </div>
        @endif

        {{-- traditional Blade views --}}
        @yield('content')

        {{-- Livewire “page” components --}}
        {{ $slot ?? '' }}
      </main>
    </div>
  </div>

  @livewireScripts

  <script>
    window.addEventListener('debug-search', e => {
      console.log('🔍 Livewire received search update:', e.detail.value);
    });
  </script>

  @stack('scripts')
</body>
</html>
