# production-management

Manages configuration, deployments, account access, and deployment history for apps running in your own server supports laravel/php, node/npx/next and cloudflare as of this moment.

## Setting up
- install dependecies
  ```
  composer install && npm install
  ```
- create environment
  ```
  cp .env.example .env
  ```
- generate app key
  ```
  php artisan key:generate
  ```
- database migration
  ```
  php artisan migrate
  php artisan db:seed
  ```

# Start application
- start vite
  ```
  npm run dev
  ```
- start laravel
  ```sh
  php artisan serve
  ```

  ## Refresh migration
```
  php artisan migrate:refresh
  php artisan db:seed
```

## Optimize
```
  php artisan optimize
```

## License

# License

This project is licensed under the **PolyForm Noncommercial License 1.0.0**.

See the [LICENSE.md](LICENSE.md) file for details.

© 2026 Melvin Jones Repol.
