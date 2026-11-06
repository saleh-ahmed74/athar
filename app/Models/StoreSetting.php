<?php

namespace App\Models;

use Database\Factories\StoreSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class StoreSetting extends Model
{
    /** @use HasFactory<StoreSettingFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['shipping_fee' => 'decimal:2', 'shipping_zones' => 'array', 'policy_sections' => 'array', 'whatsapp_status_templates' => 'array', 'show_announcement' => 'boolean', 'track_stock' => 'boolean'];
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

        return Storage::disk('public')->url(ltrim((string) $value, '/'));
    }
}
