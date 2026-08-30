<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'Home' }} | Production Management</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="true" />
    <link
        href="https://fonts.googleapis.com/css2?family=Courgette&family=Lora:ital,wght@0,400..700;1,400..700&display=swap"
        rel="stylesheet"
    />
    @vite('resources/scss/app.scss')
    
</head>

<body class="bg-gray-50 text-gray-900 min-h-screen">
@auth
<div class="min-h-screen">
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 flex flex-col h-screen overflow-hidden">
        <div class="shrink-0 px-6 py-5 border-b border-gray-200">
            <p class="text-lg font-semibold text-gray-900">Melvin Server</p>
            <p class="text-xs text-gray-500">Production Management</p>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-hidden">
            <a href="{{ route('deployments.index') }}"
                class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('deployments.*') ? 'bg-accent-50 text-accent-700' : 'text-gray-700 hover:bg-gray-100' }}">
                Deployments
            </a>

            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('deployments.create') }}"
                    class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('deployments.create') ? 'bg-accent-50 text-accent-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    New Deployment
                </a>

                <a href="{{ route('users.index') }}"
                    class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-accent-50 text-accent-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    Accounts
                </a>
            @endif
        </nav>

        <div class="shrink-0 px-4 py-4 border-t border-gray-200">
            <p class="text-sm font-medium text-gray-900">
                {{ auth()->user()->name }}
            </p>

            <p class="text-xs text-gray-500 mb-3">
                {{ auth()->user()->role === 'super_admin' ? 'Super Admin' : 'Admin' }}
            </p>

            <a href="{{ route('logout') }}"
                class="text-sm text-gray-600 hover:text-accent-600">
                Sign out
            </a>
        </div>
    </aside>

    <main class="ml-64 min-h-screen">
        <div class="max-w-5xl mx-auto px-6 py-8">
            @if(session('status'))
                <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 rounded-md border border-accent-100 bg-accent-50 px-4 py-3 text-sm text-accent-700">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 rounded-md border border-accent-100 bg-accent-50 px-4 py-3 text-sm text-accent-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>
</div>
@else
    {{ $slot }}
@endauth
    
</body>
</html>
