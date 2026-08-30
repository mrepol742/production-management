<x-app-layout title="New Account - Melvin">
    <h1 class="text-xl font-semibold text-gray-900 mb-6">New Account</h1>

    <div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required minlength="8"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" required
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Admins can redeploy, pause, resume, rebase and edit .env. Only super admins can create deployments and accounts.</p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-accent-600 hover:bg-accent-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                    Create Account
                </button>
                <a href="{{ route('users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
