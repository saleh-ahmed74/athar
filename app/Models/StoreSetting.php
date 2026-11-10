<?php

namespace App\Models;

use Database\Factories\StoreSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StoreSetting extends Model
{
    /** @use HasFactory<StoreSettingFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['shipping_fee' => 'decimal:2', 'shipping_zones' => 'array', 'policy_sections' => 'array', 'whatsapp_status_templates' => 'array', 'hero_images' => 'array', 'show_announcement' => 'boolean', 'track_stock' => 'boolean'];
    }

    public static function current(): self
    {
        $requestCacheKey = 'store-settings.current.request';
        if (! app()->bound($requestCacheKey)) {
            app()->scoped($requestCacheKey, fn (): self => Cache::remember('store-settings.current', now()->addMinutes(5), fn (): self => static::query()->first() ?? new static(['shipping_fee' => 0])));
        }

        return app($requestCacheKey);
    }

    public function localized(string $field): ?string
    {
        $locale = app()->isLocale('ar') ? 'ar' : 'en';
        $value = $this->getAttribute($field.'_'.$locale);

        return filled($value) ? $value : $this->getAttribute($field.'_'.($locale === 'ar' ? 'en' : 'ar'));
    }

    public function imageUrl(string $attribute): ?string
    {
        $value = $this->getAttribute($attribute);
        if (! filled($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true) ? $value : null;
        }

        return '/storage/'.ltrim((string) $value, '/');
    }

    /** @return list<string> */
    public function heroImageUrls(): array
    {
        $images = $this->hero_images;
        if (! is_array($images) || $images === []) {
            $images = filled($this->hero_image_url) ? [$this->hero_image_url] : [];
        }

        return array_values(array_filter(array_map(function (mixed $image): ?string {
            if (! is_string($image) || trim($image) === '') {
                return null;
            }

            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return in_array(parse_url($image, PHP_URL_SCHEME), ['https', 'http'], true) ? $image : null;
            }

            return '/storage/'.ltrim($image, '/');
        }, $images)));
    }
}
