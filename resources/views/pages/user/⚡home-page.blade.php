<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Tenant;
use App\Models\Categorie;

new class extends Component
{
    public $search = '';

    #[Computed]
    public function tenants()
    {
        return Tenant::withCount('products')
            ->latest()
            ->take(4)
            ->get();
    }

    #[Computed]
    public function categories()
    {
        return Categorie::withCount('products')->get();
    }

    public function render()
    {
        return $this->view([
            'tenants' => $this->tenants,
            'categories' => $this->categories,
        ])
        ->layout('layouts::app')
        ->title("UCIC Student Business Corner");
    }
};
?>

<div>
    @livewire('hero-section')


    <!-- TENANTS -->
    <div class="max-w-7xl mx-auto px-4 py-12">
        @livewire('tenant-list', ['tenants' => $tenants])
    </div>
    
    <!-- FILTER -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row justify-between gap-4 mb-1">

            <!-- CATEGORY -->
            @livewire('category-filter', [
                'categories' => $categories
            ])

            {{-- <div class="w-full md:w-96">
                <input 
                    type="text"
                    wire:model.debounce.100ms="search"
                    wire:input="$dispatch('searchUpdated', { search: $event.target.value })"
                    placeholder="Cari produk..."
                    class="w-full px-4 py-2 border rounded-lg"
                >
            </div> --}}
        </div> 
    </div>
    <!-- PRODUCTS -->
    <div class="max-w-7xl mx-auto px-4">
        <livewire:featured-products/>
    </div>

</div>