<x-app-layout title="Home">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                <p class="text-2xl font-semibold text-gray-900">Melvin Server</p>
                <p class="text-sm text-gray-500">Production Management</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                @if($errors->any())
                    <div class="mb-4 rounded-md border border-accent-100 bg-accent-50 px-4 py-3 text-sm text-accent-700">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-accent-600 focus:ring-accent-500">
                        Remember me
                    </label>
                    <button type="submit" class="w-full bg-accent-600 hover:bg-accent-700 text-white text-sm font-medium py-2 rounded-md">
                        Sign in
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
