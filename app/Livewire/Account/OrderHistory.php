<?php

namespace App\Livewire\Account;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class OrderHistory extends Component
{
    use WithPagination;

    public function cancelOrder(int $id): void
    {
        $order = auth()->user()->orders()->findOrFail($id);
        Gate::authorize('cancel', $order);

        DB::transaction(function () use ($id): void {
            $order = auth()->user()->orders()->with('items')->lockForUpdate()->findOrFail($id);
            if ($order->status !== 'pending') {
                throw ValidationException::withMessages(['order' => __('Only pending orders can be cancelled.')]);
            }

            if ($order->inventory_tracked) {
                $products = Product::query()
                    ->whereIn('id', $order->items->pluck('product_id')->filter()->unique()->sort()->values())
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        $products->get($item->product_id)?->increment('stock', $item->quantity);
                    }
                }
            }

            $order->update(['status' => 'cancelled']);
        }, 3);

        session()->flash('status', __('Order cancelled.'));
    }

    public function render(): View
    {
        return view('livewire.account.order-history', [
            'orders' => auth()->user()->orders()->with('items')->latest()->paginate(10),
        ])->layout('layouts.storefront', ['title' => __('My orders')]);
    }
}
