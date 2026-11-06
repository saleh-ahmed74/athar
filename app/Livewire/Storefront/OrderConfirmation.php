<?php

namespace App\Livewire\Storefront;

use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OrderConfirmation extends Component
{
    public Order $order;

    public function mount(string $orderNumber): void
    {
        $order = Order::query()->with('items')->where('order_number', $orderNumber)->firstOrFail();
        abort_unless(session('last_order_number') === $order->order_number || (auth()->check() && auth()->id() === $order->user_id), 404);
        $this->order = $order;
    }

    public function render(): View
    {
        return view('livewire.storefront.order-confirmation', ['settings' => StoreSetting::current()])->layout('layouts.storefront', ['title' => __('Order placed')]);
    }
}
