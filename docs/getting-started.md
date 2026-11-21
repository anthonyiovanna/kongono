# Getting Started with Kongono v1

Kongono is a zero-legacy **PHP 8.5+** application framework built from the ground up with no Symfony dependency and three built-in AI companions (**Oshie**, **Chie**, and **Hantei**).

## Requirements

- PHP **8.5+** (`ext-curl`, `ext-json`, `ext-mbstring`, `ext-pdo`)
- Composer 2.x

## Scaffold a New App

```bash
php bin/kongo new my-app --name="My App"
php bin/kongo serve --app=my-app --port=8000
```

## Explore the Starter App

The repository includes a full runnable starter application in `examples/starter-app` exercising all 10 Kongono packages:

```bash
php bin/kongo serve --app=examples/starter-app --port=8000
```

Visit `http://127.0.0.1:8000/` or inspect the live app from the CLI:

```bash
php bin/kongo routes --app=examples/starter-app
php bin/kongo graph --app=examples/starter-app
php bin/kongo work --app=examples/starter-app
```

## Instant Generators

```bash
php bin/kongo make:controller PostController --app=my-app
php bin/kongo make:dto CreatePostInput --app=my-app
php bin/kongo make:migration create_posts_table --app=my-app
```

## Quality Toolchain

```bash
composer cs        # PHP-CS-Fixer (PER-CS2.0)
composer analyse   # PHPStan Level 9 + strict-rules
composer test      # PHPUnit 13
```
