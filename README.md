# Production Management

Production Management is a Laravel-based control panel for managing applications that are already deployed on your own servers.

It is intended for individuals and small teams who prefer to deploy their applications manually, but still want a structured way for other trusted people to operate those applications afterward.

It does not provision infrastructure and it does not perform first-time deployments. Instead, it manages an existing application directory and gives your team controlled access to common production operations such as:

- Git update and rebase for an existing checkout
- Laravel optimize, down, and up commands
- PM2 restart and stop for Node.js apps
- `.env` editing with backup history
- Admin assignment and action history
- Per-deployment Git SSH key storage and sync

This makes it useful when one person handles the actual server setup and manual deployment, while other admins need safe access to day-to-day production controls without full shell access.

## What It Supports

Current support includes:

- Laravel / PHP applications
- Node.js applications managed by PM2

## How It Works

Each deployment record points to an application path that already exists on the server, for example `/var/www/my-app`.

When a user runs a redeploy or rebase action, the app works against that existing directory. In practice this means:

- `rebase` runs Git commands in the configured project path
- `redeploy` updates the local Git checkout, then runs the app-specific post-update command
- Laravel apps run `php artisan optimize`
- Node.js apps run `pm2 restart`

This is production maintenance and release management for live applications, not server provisioning and not zero-to-one deployment automation.

In short:

- You deploy the app manually
- Production Management helps other authorized users maintain and operate it

## Git and SSH Keys

Production environments often fail Git operations when PHP or the web server user cannot access `/home/user/.ssh`.

This project now supports storing a Git SSH private key per deployment in the database using Laravel encrypted casting. During Git operations, the system writes that key to a temporary file and uses it through `GIT_SSH_COMMAND`, so Git does not depend on the runtime user's default SSH directory.

Supported workflows:

- Paste a private key directly into the deployment form
- Import an existing private key from a server path such as `/home/user/.ssh/github-deploy-key`
- Re-sync the stored key later if the source file changes

### Recommended Setup

Use a dedicated GitHub deploy key with read-only access for each repository or environment.

Generate one on the server:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/github-deploy-key -C "melvin deploy"
```

Add the generated public key to the repository in GitHub as a read-only deploy key:

```bash
cat ~/.ssh/github-deploy-key.pub
```

Then configure the deployment in Production Management using either:

- The private key contents
- The source path `/home/user/.ssh/github-deploy-key`

### Sync an Existing Key into the Database

If the key already exists on the server, you can import it from the UI or with Artisan:

```bash
php artisan deployments:sync-ssh-key DEPLOYMENT_ID --from=/home/user/.ssh/github-deploy-key
```

If the deployment already has a saved source path, you can sync again later without passing `--from`:

```bash
php artisan deployments:sync-ssh-key DEPLOYMENT_ID
```

## Setup

Install dependencies:

```bash
composer install
npm install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Run database migrations and seeders:

```bash
php artisan migrate
php artisan db:seed
```

If you are pulling the latest version that includes deployment SSH key support, run:

```bash
php artisan migrate
```

## Local Development

Start the Laravel server:

```bash
php artisan serve
```

Start Vite:

```bash
npm run dev
```

## Useful Commands

Refresh the database:

```bash
php artisan migrate:refresh
php artisan db:seed
```

Optimize Laravel:

```bash
php artisan optimize
```

Run tests:

```bash
php artisan test
```

## Operational Notes

- This app assumes the target project directory already exists on the server.
- The configured Git remote must already be valid for that project.
- SSH key storage is intended for repository access during maintenance actions such as fetch and rebase.
- Only super admins can create deployments and manage deployment SSH keys.

## License

This project is licensed under the **PolyForm Noncommercial License 1.0.0**.

See [LICENSE.md](LICENSE.md) for details.

© 2026 Melvin Jones Repol.
