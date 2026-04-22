# Contributing

## Setup

```bash
git clone https://github.com/ahmed-aliraqi/ui-manager
cd ui-manager
composer install
```

## Running tests

```bash
./vendor/bin/phpunit
```

## Modifying the dashboard frontend

The compiled assets in `public/vendor/ui-manager/` are committed to the repository and ship with every release. End-users never need to run `npm`.

If you make changes to `resources/js/ui-manager/`, rebuild before committing:

```bash
npm install
npm run build
```

Both the source files in `resources/js/ui-manager/` and the compiled output in `public/vendor/ui-manager/` should be included in the same commit.
