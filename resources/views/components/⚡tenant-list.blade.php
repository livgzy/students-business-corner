<?php

use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component
{
    public $tenants;

    public function mount($tenants)
    {
        $this->tenants = $tenants;
    }

    #[On('echo:tenants.status,.store-status-changed')]
    public function handleStoreStatusChanged($event)
    {
        $tenant = $this->tenants->firstWhere('id', $event['tenant_id']);
        if (!$tenant) {
            return;
        }
        $tenant->is_open = (bool) $event['is_open'];
    }
};
?>

<div>
    @if(count($tenants) > 0)
    <div class="mb-4 border-b border-black-100 pb-4 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">List Tenant</h1>
        </div>
        <a href="/tenants" wire:navigate class="text-orange-600 hover:text-orange-700 font-medium transition">
            Lihat Semua →
        </a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($tenants as $tenant)
                <a href="/tenant/{{ $tenant->tenant_code }}" class="group">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
                        <div class="h-48 bg-gray-200 relative overflow-hidden">
                            @if($tenant->tenant_img)
                                <img src="{{ Storage::disk('tsbc_disk')->url($tenant->tenant_img) }}" 
                                     alt="{{ $tenant->store_name }}"
                                     class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-orange-100 to-orange-200">
                                    <flux:icon.building-storefront class="size-14 text-orange-500"/>
                                </div>
                            @endif
                            <div class="absolute top-2 right-2">
                                @if($tenant->is_open)
                                    <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                        Buka
                                    </span>
                                @else
                                    <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                        Tutup
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-lg text-gray-800 group-hover:text-orange-600 transition">
                                {{ $tenant->store_name }}
                            </h3>
                            <h4 class="text-md text-gray-800 group-hover:text-orange-600 transition">
                                Tenant {{ $tenant->tenant_code }}
                            </h4>
                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">
                                {{ $tenant->description ?? 'Tidak ada deskripsi' }}
                            </p>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xs text-gray-400">
                                    {{ $tenant->products_count }} Menu
                                </span>
                                <span class="text-amber-500 group-hover:translate-x-1 transition inline-block">
                                    Lihat →
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <p class="mt-2 text-gray-500">Belum ada tenant yang tersedia</p>
        </div>
    @endif
</div>