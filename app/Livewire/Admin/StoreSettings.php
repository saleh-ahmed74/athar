<?php

namespace App\Livewire\Admin;

use App\Models\StoreSetting as StoreSettingModel;
use App\Support\ImageUploadProcessor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class StoreSettings extends Component
{
    use WithFileUploads;

    public array $form = [];

    public ?TemporaryUploadedFile $logoFile = null;

    /** @var list<TemporaryUploadedFile> */
    public array $heroImageFiles = [];

    private const FIELDS = [
        'store_name', 'logo_url', 'instagram_url', 'facebook_url', 'tiktok_url', 'whatsapp_url', 'payment_label_ar', 'payment_label_en', 'currency_symbol', 'show_announcement', 'track_stock', 'order_confirmation_ar', 'order_confirmation_en', 'announcement_ar', 'announcement_en', 'shipping_zones', 'policy_sections',
        'support_email', 'support_phone', 'address', 'shipping_fee', 'tagline_ar', 'tagline_en',
        'seo_description_ar', 'seo_description_en', 'hero_eyebrow_ar', 'hero_eyebrow_en',
        'hero_title_ar', 'hero_title_en', 'hero_text_ar', 'hero_text_en', 'hero_cta_ar', 'hero_cta_en',
        'hero_image_url', 'categories_heading_ar', 'categories_heading_en', 'featured_heading_ar',
        'featured_heading_en', 'about_ar', 'about_en', 'policy_ar', 'policy_en',
    ];

    public function mount(): void
    {
        $settings = StoreSettingModel::current();
        foreach (self::FIELDS as $field) {
            $this->form[$field] = $settings->getAttribute($field) ?? match ($field) {
                'show_announcement' => true,
                'shipping_fee' => '0.00',
                'shipping_zones' => [],
                'policy_sections' => [],
                default => '',
            };
        }
        $this->form['hero_images'] = $settings->hero_images ?: (filled($settings->hero_image_url) ? [$settings->hero_image_url] : []);
    }

    public function removeHeroImage(int $index): void
    {
        unset($this->form['hero_images'][$index]);
        $this->form['hero_images'] = array_values($this->form['hero_images']);
    }

    public function removeNewHeroImage(int $index): void
    {
        unset($this->heroImageFiles[$index]);
        $this->heroImageFiles = array_values($this->heroImageFiles);
    }

    public function addShippingZone(): void
    {
        $this->form['shipping_zones'][] = ['id' => (string) Str::uuid(), 'name_en' => '', 'name_ar' => '', 'fee' => '0.00'];
    }

    public function removeShippingZone(int $index): void
    {
        unset($this->form['shipping_zones'][$index]);
        $this->form['shipping_zones'] = array_values($this->form['shipping_zones']);
    }

    public function addPolicySection(): void
    {
        $this->form['policy_sections'][] = ['id' => (string) Str::uuid(), 'title_en' => '', 'body_en' => '', 'title_ar' => '', 'body_ar' => ''];
    }

    public function removePolicySection(int $index): void
    {
        unset($this->form['policy_sections'][$index]);
        $this->form['policy_sections'] = array_values($this->form['policy_sections']);
    }

    public function save(): void
    {
        $rules = [
            'form.store_name' => ['required', 'string', 'max:120'],
            'form.currency_symbol' => ['required', 'string', 'max:12'],
            'form.show_announcement' => ['boolean'],
            'form.track_stock' => ['boolean'],
            'form.announcement_ar' => ['nullable', 'string', 'max:180'],
            'form.announcement_en' => ['nullable', 'string', 'max:180'],
            'form.support_email' => ['nullable', 'email', 'max:255'],
            'form.support_phone' => ['nullable', 'string', 'max:30'],
            'form.address' => ['nullable', 'string', 'max:255'],
            'form.shipping_fee' => ['required', 'numeric', 'min:0', 'max:100000'],
            'form.shipping_zones' => ['array', 'max:40'],
            'form.shipping_zones.*' => ['required', 'array:id,name_en,name_ar,fee'],
            'form.shipping_zones.*.id' => ['required', 'uuid'],
            'form.shipping_zones.*.name_en' => ['required', 'string', 'max:100'],
            'form.shipping_zones.*.name_ar' => ['required', 'string', 'max:100'],
            'form.shipping_zones.*.fee' => ['required', 'numeric', 'min:0', 'max:100000'],
            'form.policy_sections' => ['array', 'max:40'],
            'form.policy_sections.*' => ['required', 'array:id,title_en,body_en,title_ar,body_ar'],
            'form.policy_sections.*.id' => ['required', 'uuid'],
            'form.policy_sections.*.title_en' => ['required', 'string', 'max:160'],
            'form.policy_sections.*.body_en' => ['required', 'string', 'max:5000'],
            'form.policy_sections.*.title_ar' => ['required', 'string', 'max:160'],
            'form.policy_sections.*.body_ar' => ['required', 'string', 'max:5000'],
            'form.hero_image_url' => ['nullable', 'string', 'max:2048', $this->imageValueRule()],
            'form.hero_images' => ['array', 'max:10'],
            'form.hero_images.*' => ['required', 'string', 'max:2048', $this->imageValueRule()],
            'form.logo_url' => ['nullable', 'string', 'max:2048', $this->imageValueRule()],
            'logoFile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            'heroImageFiles' => ['array', 'max:10'],
            'heroImageFiles.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            'form.instagram_url' => ['nullable', 'url', 'max:2048'],
            'form.facebook_url' => ['nullable', 'url', 'max:2048'],
            'form.whatsapp_url' => ['nullable', 'url', 'max:2048'],
            'form.tiktok_url' => ['nullable', 'url', 'max:2048'],
        ];

        foreach (['order_confirmation_ar', 'order_confirmation_en', 'tagline_ar', 'tagline_en', 'hero_text_ar', 'hero_text_en', 'about_ar', 'about_en'] as $field) {
            $rules['form.'.$field] = ['nullable', 'string', 'max:5000'];
        }
        foreach (['policy_ar', 'policy_en'] as $field) {
            $rules['form.'.$field] = ['nullable', 'string', 'max:15000'];
        }
        foreach (['seo_description_ar', 'seo_description_en'] as $field) {
            $rules['form.'.$field] = ['nullable', 'string', 'max:320'];
        }
        foreach (['payment_label_ar', 'payment_label_en', 'hero_eyebrow_ar', 'hero_eyebrow_en', 'hero_title_ar', 'hero_title_en', 'hero_cta_ar', 'hero_cta_en', 'categories_heading_ar', 'categories_heading_en', 'featured_heading_ar', 'featured_heading_en'] as $field) {
            $rules['form.'.$field] = ['nullable', 'string', 'max:180'];
        }

        $validated = $this->validate($rules);
        $form = $validated['form'];
        if (count($form['hero_images'] ?? []) + count($this->heroImageFiles) > 10) {
            $this->addError('heroImageFiles', __('The homepage gallery can contain up to ten images.'));

            return;
        }
        $data = [];
        foreach (self::FIELDS as $field) {
            $value = $form[$field] ?? null;
            $data[$field] = is_string($value) && trim($value) === '' ? null : $value;
        }
        $settings = StoreSettingModel::query()->firstOrNew();
        $oldHeroImages = array_values(array_filter(array_merge($settings->hero_images ?? [], [$settings->hero_image_url])));
        $oldLogo = $settings->logo_url;
        if ($this->logoFile) {
            $data['logo_url'] = ImageUploadProcessor::store($this->logoFile, 'site', 1200, 1200, 84);
        }
        $heroImages = array_values($form['hero_images'] ?? []);
        foreach ($this->heroImageFiles as $image) {
            $heroImages[] = ImageUploadProcessor::store($image, 'site', 1920, 1200, 82);
        }
        $data['hero_images'] = $heroImages;
        $data['hero_image_url'] = $heroImages[0] ?? null;
        $settings->fill($data)->save();
        $imagesToDelete = array_merge(array_diff($oldHeroImages, $heroImages), $this->logoFile ? [$oldLogo] : []);
        Storage::disk('public')->delete(array_values(array_filter(array_map(
            fn ($path): ?string => is_string($path) && str_starts_with($path, 'site/') ? $path : null,
            $imagesToDelete,
        ))));
        Cache::forget('store-settings.current');
        app()->forgetInstance('store-settings.current.request');
        $this->reset(['logoFile', 'heroImageFiles']);

        session()->flash('status', __('Store settings saved.'));
    }

    public function render(): View
    {
        return view('livewire.admin.store-settings')->layout('layouts.admin', ['title' => __('Store settings')]);
    }

    private function imageValueRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! filled($value)) {
                return;
            }

            if (filter_var($value, FILTER_VALIDATE_URL)) {
                if (parse_url($value, PHP_URL_SCHEME) !== 'https') {
                    $fail(__('Use a secure HTTPS image URL.'));
                }

                return;
            }

            if (! preg_match('#^[A-Za-z0-9/_\-.]+$#', (string) $value)) {
                $fail(__('Choose an uploaded image or enter a secure image URL.'));
            }
        };
    }
}
