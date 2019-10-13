# Blade Lint

# WORK IN PROGRESS

## Requirements

Laravel >= 5.5

## Installation

#### `composer require --dev blade-lint`

#### Create a .bladelintrc.yaml file

Example:

```
rules:
    - Indent:
        spaces: 4

parameters:
    include_files:
        - '/resources/views'

    exclude_files:
        - '/config'
        - '/storage'
        - '/vendor'
        - 'cache'
```

## Run it

#### `php artisan blade:lint`



## Development:

1. Run `composer install --prefer-dist` to install php dependencies
