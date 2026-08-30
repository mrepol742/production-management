<x-app-layout title="Accounts - Melvin">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Accounts</h1>
        <a href="{{ route('users.create') }}" class="bg-accent-600 hover:bg-accent-700 text-white text-sm font-medium px-4 py-2 rounded-md">
            New Account
        </a>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg divide-y divide-gray-200">
        @foreach($users as $user)
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->role === 'super_admin' ? 'bg-accent-100 text-accent-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $user->role === 'super_admin' ? 'Super Admin' : 'Admin' }}
                    </span>
                    @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Remove this account?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-gray-500 hover:text-accent-600">Remove</button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
