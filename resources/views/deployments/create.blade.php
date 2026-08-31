<x-app-layout title="New Deployment - Melvin">
    <h1 class="text-xl font-semibold text-gray-900 mb-6">New Deployment</h1>

    <div class="bg-white border border-gray-200 rounded-lg p-6 max-w-2xl">
        <form method="POST" action="{{ route('deployments.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="my-app-production"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" required
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
                    <option value="laravel" {{ old('type') === 'laravel' ? 'selected' : '' }}>Laravel / PHP</option>
                    <option value="node" {{ old('type') === 'node' ? 'selected' : '' }}>Node (Express / Next.js) via PM2</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Server path</label>
                <input type="text" name="path" value="{{ old('path') }}" required placeholder="/var/www/my-app"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Repository URL</label>
                <input type="text" name="repo_url" value="{{ old('repo_url') }}" placeholder="git@github.com:org/repo.git"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                <input type="text" name="branch" value="{{ old('branch', 'main') }}"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
            </div>

            <div class="border border-gray-200 rounded-lg p-4 space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-900">SSH deploy key for Git</p>
                    <p class="text-xs text-gray-500 mt-1">Optional, but recommended for production. Git actions will use this stored key instead of relying on <code>~/.ssh</code>.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Key label</label>
                    <input type="text" name="ssh_key_name" value="{{ old('ssh_key_name') }}" placeholder="github-production-deploy"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Import from server path</label>
                    <input type="text" name="ssh_private_key_path" value="{{ old('ssh_private_key_path') }}" placeholder="/home/user/.ssh/github-deploy-key"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
                    <p class="text-xs text-gray-400 mt-1">If provided without a pasted key, Melvin will read that file once and store the contents encrypted in the database.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Or paste private key</label>
                    <textarea name="ssh_private_key" rows="8" spellcheck="false" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono focus:border-accent-500 focus:ring-accent-500 focus:outline-none">{{ old('ssh_private_key') }}</textarea>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PM2 app name</label>
                    <input type="text" name="pm2_name" value="{{ old('pm2_name') }}" placeholder="my-app"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
                    <p class="text-xs text-gray-400 mt-1">Only used for Node apps.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PM2 id or number (optional)</label>
                    <input type="text" name="pm2_instances" value="{{ old('pm2_instances') }}" placeholder="0"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-accent-500 focus:ring-accent-500 focus:outline-none">
                    <p class="text-xs text-gray-400 mt-1">Used instead of app name for pm2 restart if set.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-accent-600 hover:bg-accent-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                    Create Deployment
                </button>
                <a href="{{ route('deployments.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
