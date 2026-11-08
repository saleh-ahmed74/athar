<?php

namespace App\Livewire\Admin;

use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class WhatsAppTemplates extends Component
{
    public const STATUSES = ['pending', 'confirmed', 'shipped', 'delivered', 'returned', 'cancelled'];

    public const DEFAULT_CONFIRMATION_TEMPLATE = "أهلًا يا :name 👋\nمعاك فريق أثَر. بنأكد معاك طلبك رقم :order_number.\n\nتفاصيل طلبك:\n:items\n\nقيمة المنتجات: :subtotal جنيه\nالتوصيل: :shipping جنيه\nالإجمالي: :total جنيه\n\nبيانات التوصيل:\nالاسم: :name\nرقم الموبايل: :phone\nالمدينة: :city\nالعنوان: :address\nالبريد الإلكتروني: :email\nملاحظات: :notes\n\nلو كل البيانات تمام، ابعتلنا تأكيدك. ولو محتاج تعدّل أي حاجة قولنا. شكرًا لاختيارك أثَر ❤️";

    /** @var array<string, string> */
    public array $templates = [];

    /** @var array<string, string> */
    public array $messageFeedback = [];

    public function mount(): void
    {
        $savedTemplates = StoreSetting::current()->whatsapp_status_templates ?? [];
        $this->templates = array_fill_keys(self::STATUSES, '');
        $this->templates['pending'] = self::DEFAULT_CONFIRMATION_TEMPLATE;

        foreach ($savedTemplates as $status => $template) {
            if (in_array($status, self::STATUSES, true)) {
                $this->templates[$status] = (string) $template;
            }
        }
    }

    public function updatedTemplates(mixed $value, ?string $status = null): void
    {
        if (in_array($status, self::STATUSES, true)) {
            $this->persistTemplate($status, 'saved');
        }
    }

    public function saveTemplate(string $status): void
    {
        abort_unless(in_array($status, self::STATUSES, true), 404);
        $this->persistTemplate($status, 'saved');
    }

    public function deleteTemplate(string $status): void
    {
        abort_unless(in_array($status, self::STATUSES, true), 404);
        $this->templates[$status] = '';
        $this->persistTemplate($status, 'deleted');
    }

    private function persistTemplate(string $status, string $feedback): void
    {
        $validated = $this->validate([
            'templates.'.$status => ['nullable', 'string', 'max:5000'],
        ]);
        $message = trim((string) data_get($validated, 'templates.'.$status, ''));
        $settings = StoreSetting::query()->firstOrNew();
        $templates = array_merge(array_fill_keys(self::STATUSES, ''), $settings->whatsapp_status_templates ?? []);
        $templates[$status] = $message;
        $settings->whatsapp_status_templates = $templates;
        $settings->save();
        Cache::forget('store-settings.current');
        app()->forgetInstance('store-settings.current.request');

        $this->templates[$status] = $message;
        $this->messageFeedback[$status] = $feedback;
    }

    public function render(): View
    {
        return view('livewire.admin.whatsapp-templates', [
            'statuses' => self::STATUSES,
        ])->layout('layouts.admin', ['title' => __('WhatsApp messages')]);
    }
}
