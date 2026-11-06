<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Orders extends Component
{
    use WithPagination;

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    /** @var array<int, string> */
    public array $statusChoices = [];

    /** @var array<int, string> */
    public array $returnReasons = [];

    private ?array $whatsappTemplatesCache = null;

    public function updateStatusFor(int $id): void
    {
        $order = Order::query()->findOrFail($id);
        $this->updateStatus($id, $this->statusChoices[$id] ?? $order->status);
        unset($this->statusChoices[$id], $this->returnReasons[$id]);
    }

    public function changeStatus(int $id, string $status): void
    {
        $this->statusChoices[$id] = $status;

        if ($status === 'returned') {
            return;
        }

        $this->updateStatus($id, $status);
        unset($this->statusChoices[$id], $this->returnReasons[$id]);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updateStatus(int $id, string $status): void
    {
        $order = Order::query()->findOrFail($id);
        Gate::authorize('update', $order);
        $allowedStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'returned', 'cancelled'];
        abort_unless(in_array($status, $allowedStatuses, true), 422);
        $returnReason = null;
        if ($status === 'returned') {
            $validated = $this->validate([
                'returnReasons.'.$id => ['required', 'string', 'min:5', 'max:2000'],
            ], [
                'returnReasons.'.$id.'.required' => __('Please enter a return reason.'),
                'returnReasons.'.$id.'.min' => __('The return reason must be at least 5 characters.'),
            ]);
            $returnReason = $validated['returnReasons'][$id];
        }

        DB::transaction(function () use ($id, $status, $returnReason): void {
            $order = Order::query()->with('items')->lockForUpdate()->findOrFail($id);
            if ($status === $order->status) {
                return;
            }

            $products = Product::query()
                ->whereIn('id', $order->items->pluck('product_id')->filter()->unique()->sort()->values())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if ($status === 'cancelled' && $order->status !== 'cancelled' && $order->inventory_tracked) {
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        $product = $products->get($item->product_id);
                        $product?->increment('stock', $item->quantity);
                    }
                }
            }

            if ($order->status === 'cancelled' && $status !== 'cancelled' && $order->inventory_tracked) {
                foreach ($order->items as $item) {
                    if (! $item->product_id) {
                        continue;
                    }

                    $product = $products->get($item->product_id);
                    if ($product && $product->stock < $item->quantity) {
                        throw ValidationException::withMessages(['status' => __('There is not enough stock to reopen this order.')]);
                    }

                    $product?->decrement('stock', $item->quantity);
                }
            }

            $order->update([
                'status' => $status,
                'payment_status' => match ($status) {
                    'delivered' => 'paid',
                    'returned' => 'refunded',
                    default => 'unpaid',
                },
                'return_reason' => $status === 'returned' ? $returnReason : null,
            ]);
        }, 3);

        session()->flash('status', __('Order status updated.'));
    }

    public function whatsappConfirmationLink(Order $order): string
    {
        $phone = preg_replace('/\D+/', '', $order->phone) ?? '';
        if (str_starts_with($phone, '0')) {
            $phone = '20'.ltrim($phone, '0');
        }

        $status = $this->messageStatus($order);
        // Read the editable template from storage directly so a cached store
        // settings snapshot cannot make WhatsApp open an older message.
        $templates = $this->whatsappTemplatesCache ??= (StoreSetting::query()->first()?->whatsapp_status_templates ?? []);
        $template = $status === 'pending'
            ? ($templates[$status] ?? WhatsAppTemplates::DEFAULT_CONFIRMATION_TEMPLATE)
            : '';

        if (! filled($template)) {
            return 'https://wa.me/'.$phone;
        }

        $items = $order->items->map(fn ($item) => '• '.$item->product_name.' × '.$item->quantity)->implode("\n");
        $message = strtr($template, [
            ':name' => $order->customer_name,
            ':order_number' => $order->order_number,
            ':items' => $items,
            ':subtotal' => number_format((float) $order->subtotal, 2),
            ':shipping' => number_format((float) $order->shipping_fee, 2),
            ':total' => number_format((float) $order->total, 2),
            ':phone' => $order->phone,
            ':city' => $order->city,
            ':address' => $order->address,
            ':email' => $order->email ?: 'مش مضاف',
            ':notes' => $order->notes ?: 'مفيش',
        ]);

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    public function hasWhatsAppTemplate(Order $order): bool
    {
        return true;
    }

    private function messageStatus(Order $order): string
    {
        return $order->status === 'processing' ? 'confirmed' : $order->status;
    }

    public function render(): View
    {
        if (! in_array($this->statusFilter, ['', 'pending', 'confirmed', 'shipped', 'delivered', 'returned', 'cancelled'], true)) {
            $this->statusFilter = '';
        }

        return view('livewire.admin.orders', [
            'orders' => Order::query()->with(['items.product'])->withCount('items')->when($this->statusFilter !== '', function ($query): void {
                if ($this->statusFilter === 'confirmed') {
                    $query->whereIn('status', ['confirmed', 'processing']);
                } else {
                    $query->where('status', $this->statusFilter);
                }
            })->latest()->paginate(15),
            'statusOptions' => ['pending', 'confirmed', 'shipped', 'delivered', 'returned', 'cancelled'],
        ])->layout('layouts.admin', ['title' => __('Orders')]);
    }
}
