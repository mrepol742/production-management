<x-app-layout title="Deployments - Melvin">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Deployments</h1>
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('deployments.create') }}" class="bg-accent-600 hover:bg-accent-700 text-white text-sm font-medium px-4 py-2 rounded-md">
            New Deployment
        </a>
        @endif
    </div>

    @if($deployments->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-10 text-center">
            <p class="text-sm text-gray-500">No deployments yet.</p>
        </div>
    @else
        <div class="bg-white border border-gray-200 rounded-lg divide-y divide-gray-200">
            @foreach($deployments as $deployment)
                <a href="{{ route('deployments.show', $deployment) }}" class="flex items-center justify-between px-5 py-4 hover:bg-gray-50">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $deployment->name }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst($deployment->type) }} - {{ $deployment->path }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($deployment->status === 'running')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Running</span>
                        @elseif($deployment->status === 'paused')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent-100 text-accent-700">Paused</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Unknown</span>
                        @endif
                        <span class="text-xs text-gray-400">
                            {{ $deployment->last_deployed_at ? $deployment->last_deployed_at->diffForHumans() : 'Never deployed' }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
