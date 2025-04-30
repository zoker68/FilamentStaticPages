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

## Publish migrations

```bash
php artisan vendor:publish --tag=filament-static-pages-migrations
```

## Migrations

```bash
php artisan migrate
```

## Add to Filament Service Provider
```php
->plugin(StaticPages::make())
```

# New Component:

- In directory `app/View/Components` create new component
```php
namespace App\View\Components;

class TextBlock extends \Zoker\FilamentStaticPages\Classes\BlockComponent
{
    public static string $viewTemplate = 'components.text'; 
    
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

- Set view for component
```php
    public static string $viewTemplate = 'components.text';
```

- Register component in ServiceProvider

```php
\Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry::register(\App\View\Components\TextBlock::class, 'TextBlock');
```

# Menu

```php
<x-fsp::menu code="menu-code"/>
```

# Content everywhere

```bladehtml
@fspContent(content-code)
```
