# Production Management

Production Management is a Laravel-based control panel for managing applications that are already deployed on your own servers.

It is intended for individuals and small teams who prefer to deploy their applications manually, but still want a structured way for other trusted people to operate those applications afterward.

It does not provision infrastructure and it does not perform first-time deployments. Instead, it manages an existing application directory and gives your team controlled access to common production operations such as:

- Queue deployment commands for an existing checkout
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

When a user queues a deploy action, the app works against that existing directory. In practice this means:

- the web UI records a pending deployment job
- cron later executes the saved deploy command under the correct server user
- the command can be Laravel-specific, Node-specific, or a mixed shell script for your project

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
php artisan deployments:sync-ssh DEPLOYMENT_ID --key=/home/user/.ssh/github-deploy-key
```

If your Git remote uses an alias such as `git@ascendensasia:team/repo.git`, also sync the SSH config file that contains the alias mapping:

```bash
php artisan deployments:sync-ssh DEPLOYMENT_ID --config=/home/user/.ssh/config
```

You can sync both in one command:

```bash
php artisan deployments:sync-ssh DEPLOYMENT_ID --key=/home/user/.ssh/github-deploy-key --config=/home/user/.ssh/config
```

If saved source paths already exist on the deployment, you can sync again later without passing options:

```bash
php artisan deployments:sync-ssh DEPLOYMENT_ID
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

## Cron Worker

This project is intended to queue deploy jobs from the web UI or webhook, then execute them later with cron under the correct deploy user.

Example cron entry:

```cron
* * * * * cd /path/to/production-management && php artisan deployments:run-pending >> storage/logs/deploy-cron.log 2>&1
```

Use the deployment's `deploy_command` to define what actually runs for each application. Example Laravel command:

```bash
git pull origin main && composer install --no-dev --optimize-autoloader && php artisan migrate --force && php artisan optimize
```

Example Node command:

```bash
git pull origin main && npm install && npm run build && pm2 restart my-app
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
- The configured Git remote and deploy command must already be valid for that project.
- SSH key storage is intended for repository access during queued deployment commands.
- Only super admins can create deployments and manage deployment SSH keys.

## License

This project is licensed under the **PolyForm Noncommercial License 1.0.0**.

See [LICENSE.md](LICENSE.md) for details.

© 2026 Melvin Jones Repol.
