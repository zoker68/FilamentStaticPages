# FilamentStaticPages
Simple plugin for static pages

# Install

```bash
composer require zoker/filament-static-pages
```

## Publish config

```bash
php artisan vendor:publish --tag=filament-static-pages-config
```

## Publish views

```bash
php artisan vendor:publish --tag=filament-static-pages-views
```

## Publish components

```bash
php artisan vendor:publish --tag=filament-static-pages-components
```

## Publish migrations

```bash
php artisan vendor:publish --tag=filament-static-pages-migrations
```

# New Component:

- In directory `app/View/Components` create new component
```php
namespace App\View\Components;

class TextBlock extends \Zoker\FilamentStaticPages\Classes\BlockComponent
{
    public function __construct(public array $data) {}

    public function render()
    {
        return view('components.text', ['data' => $this->data]);
    }

    public static function getSchema()
    {
        return [
            Textarea::make('data.content'),
        ];
    }
}

```

- You can add label to component (optional)
```php
    public static string $label = 'Text Block';
```

- Register component in ServiceProvider
```php
\Zoker\FilamentStaticPages\Classes\ComponentRegistry::register(\App\View\Components\TextBlock::class);
```
