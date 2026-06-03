# Contributing to Filament Short URL

Thank you for considering contributing to the Filament Short URL plugin!

## Development Setup

If you are modifying the plugin source code and need to recompile its stylesheet after changing Blade, PHP, or configuration files, follow these steps:

### 1. Recompile Plugin CSS (Tailwind CSS v4)
Run the Tailwind CSS CLI from the root folder of your host application to scan the plugin's views and compile the final stylesheet:

```bash
npx @tailwindcss/cli -i ./packages/filament-short-url/resources/css/plugin.css \
    -o ./packages/filament-short-url/resources/dist/filament-short-url.css --minify
```

### 2. Re-publish compiled assets
After compiling, publish the updated CSS stylesheet to the public folder of your host application so the browser can load it:

```bash
php artisan filament:assets
```

## Running Tests

We enforce test coverage for all features. To run the tests from the root of your host application:

```bash
php artisan test --filter=ShortUrl
```
