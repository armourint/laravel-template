<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <form method="POST" action="/login" class="bg-white p-8 rounded shadow-md w-full max-w-sm">
        @csrf
        <div class="flex justify-center mb-6">
            <img src="{{ asset('images/logo.svg') }}" alt="Butler Technologies Logo" class="h-100">
        </div>

        <h1 class="text-xl font-bold mb-6 text-center">Super Admin Login</h1>

        @if($errors->any())
            <div class="bg-red-100 text-red-700 p-2 rounded mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" name="email" type="email" required autofocus
                   class="mt-1 block w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring focus:border-blue-300"
                   value="{{ old('email') }}">
        </div>

        <div class="mb-6">
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 block w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring focus:border-blue-300">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Login
        </button>
    </form>
</body>
</html>
