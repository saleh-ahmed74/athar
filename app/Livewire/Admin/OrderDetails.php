<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OrderDetails extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order->loadMissing(['items.product']);
    }

    public function render(): View
    {
        return view('livewire.admin.order-details')
            ->layout('layouts.admin', ['title' => __('Order details')]);
    }
}
