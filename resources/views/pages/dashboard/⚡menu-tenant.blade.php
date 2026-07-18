<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Categorie;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use App\Events\ProductAvailabilityChanged;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';
    public $statusFilter = 'all';
    public $perPage = 10;

    public $showModal = false;
    public $showDeleteModal = false;
    public $deleteProductId = null;
    public $deleteProductName = null;

    public function getTenantProperty()
    {

        return Tenant::where('reservation_id', Auth::user()->reservation->id)->first();
    }
    
    public function getProductsProperty()
    {
        $tenant = $this->tenant;
        
        if (!$tenant) {
            return collect();
        }
        
        $query = Product::with('category')
            ->where('tenant_id', $tenant->id);
        
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }
        
        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }
        
        if ($this->statusFilter === 'available') {
            $query->where('is_available', 1);
        } elseif ($this->statusFilter === 'unavailable') {
            $query->where('is_available', 0);
        }
        
        return $query->latest()->paginate($this->perPage);
    }
    
    public function getCategoriesProperty()
    {
        return Categorie::all();
    }

    public function toggleStatus($productId)
    {
        $tenant = $this->tenant;

        if (!$tenant) return;

        $product = Product::where('id', $productId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $product->update(['is_available' => !$product->is_available]);

        broadcast(new ProductAvailabilityChanged($product))->toOthers();

        $status = $product->is_available ? 'Tersedia' : 'Tidak Tersedia';
        session()->flash('message', "Status menu \"{$product->name}\" diubah menjadi {$status}");
    }

    public function render()
    {
        return $this->view([
            'products' => $this->products,
            'categories' => $this->categories,
            'tenant' => $this->tenant,
        ])
            ->layout('layouts::tenant', ['title' => 'Student Business Corner | Dashboard Menu']); 
    }
};
?>

<div>
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Kelola Menu</h2>
            <p class="text-gray-500 text-sm mt-1">Atur daftar menu yang dijual di toko Anda</p>
        </div>
        <a href="/tenants/menu/add"
                class="px-4 py-2 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Menu
        </a>
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" 
                           wire:model.live.debounce.300ms="search"
                           placeholder="Cari menu..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                </div>
            </div>
            <div class="w-full md:w-48">
                <select wire:model.live="categoryFilter" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full md:w-40">
                <select wire:model.live="statusFilter" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500">
                    <option value="all">Semua Status</option>
                    <option value="available">Tersedia</option>
                    <option value="unavailable">Tidak Tersedia</option>
                </select>
            </div>
            <div class="w-full md:w-32">
                <select wire:model.live="perPage" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500">
                    <option value="10">10 data</option>
                    <option value="25">25 data</option>
                    <option value="50">50 data</option>
                    <option value="100">100 data</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Products Grid --}}
    <div class="mb-4 flex items-center justify-between">
        <span class="text-sm text-gray-500">
            Menampilkan {{ $products->firstItem() }}–{{ $products->lastItem() }} dari {{ $products->total() }} menu
        </span>
    </div>

    @if($products->isEmpty())
        <div class="bg-white rounded-xl shadow-sm p-16 flex flex-col items-center gap-3 text-gray-400">
            <svg class="w-14 h-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <p class="text-gray-500">Belum ada menu</p>
            <a href="/tenants/menu/add" class="text-orange-500 hover:text-orange-600 text-sm">Tambah menu sekarang</a>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            @foreach($products as $product)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col hover:shadow-md transition-shadow">

                    {{-- Gambar --}}
                    <div class="relative h-32 bg-gray-50">
                        @if($product->product_img)
                            <img src="{{ Storage::disk('tsbc_disk')->url($product->product_img) }}"
                                class="w-full h-full object-cover" alt="{{ $product->name }}">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif

                        
                        <button wire:click="toggleStatus({{ $product->id }})"
                                class="absolute top-2 right-2 flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium transition cursor-pointer
                                {{ $product->is_available
                                    ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                    : 'bg-red-100 text-red-700 hover:bg-red-200' }}">
                            @if($product->is_available)
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Tersedia
                            @else
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Habis
                            @endif
                        </button>
                    </div>

                    {{-- Konten --}}
                    <div class="p-3 flex flex-col gap-2 flex-1">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $product->name }}</p>
                        <p class="text-xs text-gray-400 line-clamp-2 leading-relaxed min-h-[2.5rem]">
                            {{ $product->description ?? '-' }}
                        </p>

                        {{-- Badge kategori & tipe --}}
                        <div class="flex flex-wrap gap-1">
                            <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">
                                {{ $product->category->name ?? '-' }}
                            </span>
                            @if($product->is_preorder)
                                <span class="flex items-center gap-0.5 px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-700">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Pre-Order
                                </span>
                            @else
                                <span class="flex items-center gap-0.5 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Ready
                                </span>
                            @endif
                        </div>

                        {{-- Harga & aksi --}}
                        <div class="flex items-center justify-between mt-auto pt-2 border-t border-gray-100">
                            <span class="text-sm font-bold text-orange-600">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </span>
                            <div class="flex items-center gap-1">
                                <button wire:click="editProduct({{ $product->id }})"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-indigo-500 hover:bg-indigo-50 hover:border-indigo-200 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button wire:click="confirmDelete({{ $product->id }})"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-red-500 hover:bg-red-50 hover:border-red-200 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6 px-1">
            {{ $products->links() }}
        </div>
    @endif

    <!-- Modal Form Tambah/Edit Menu -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" wire:click="$set('showModal', false)"></div>
                
                <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-y-auto">
                    <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800">
                                {{ $isEditing ? 'Edit Menu' : 'Tambah Menu Baru' }}
                            </h3>
                        </div>
                        <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveProduct" class="p-6 space-y-5">
                        <!-- Nama Menu -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                Nama Menu <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="name" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                   placeholder="Contoh: Nasi Goreng Spesial">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Kategori & Harga -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">
                                    Kategori <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500">
                                    <option value="">Pilih Kategori</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">
                                    Harga <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                                    <input type="number" wire:model="price" 
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                           placeholder="0">
                                </div>
                                @error('price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                Deskripsi
                            </label>
                            <textarea wire:model="description" rows="3" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                      placeholder="Deskripsi menu..."></textarea>
                            @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Foto Menu -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                Foto Menu
                            </label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:border-orange-500 transition">
                                <div class="space-y-1 text-center">
                                    @if($product_img)
                                        <img src="{{ $product_img->temporaryUrl() }}" class="mx-auto h-32 w-auto rounded-lg shadow">
                                    @elseif($existing_image)
                                        <img src="{{ Storage::disk('tsbc_disk')->url($existing_image) }}" class="mx-auto h-32 w-auto rounded-lg shadow">
                                    @else
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    @endif
                                    <div class="flex justify-center text-sm text-gray-500">
                                        <label for="product_img" class="cursor-pointer bg-white rounded-md font-medium text-orange-600 hover:text-orange-500">
                                            Upload foto
                                            <input id="product_img" type="file" wire:model="product_img" class="sr-only" accept="image/*">
                                        </label>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, JPEG up to 2MB</p>
                                </div>
                            </div>
                            @error('product_img') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Status -->
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="is_available" class="w-4 h-4 text-orange-500 rounded focus:ring-orange-500">
                                <span class="text-sm text-gray-700">Tersedia</span>
                            </label>
                        </div>

                        <!-- Pre-order Option -->
                        <div class="border-t pt-4">
                            <label class="flex items-center gap-2 cursor-pointer mb-3">
                                <input type="checkbox" wire:model="is_preorder" class="w-4 h-4 text-orange-500 rounded focus:ring-orange-500">
                                <span class="text-sm font-semibold text-gray-700">Menu Pre-Order</span>
                            </label>

                            @if($is_preorder)
                                <div class="bg-amber-50 rounded-xl p-4">
                                    <p class="text-sm font-medium text-amber-800 mb-3">Hari Pre-Order:</p>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                        @foreach($daysOfWeek as $key => $day)
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" value="{{ $key }}" wire:model="dayPreorder" 
                                                       class="w-4 h-4 text-amber-500 rounded focus:ring-amber-500">
                                                <span class="text-sm text-gray-700">{{ $day }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('dayPreorder') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button type="button" wire:click="$set('showModal', false)" 
                                    class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition">
                                Batal
                            </button>
                            <button type="submit" wire:loading.attr="disabled"
                                    class="px-4 py-2 text-sm text-white bg-orange-500 rounded-xl hover:bg-orange-600 transition flex items-center gap-2">
                                <svg wire:loading.remove class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <svg wire:loading class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                {{ $isEditing ? 'Update' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" wire:click="$set('showDeleteModal', false)"></div>
                
                <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full mx-auto">
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Hapus Menu</h3>
                        <p class="text-gray-500 mb-4">
                            Apakah Anda yakin ingin menghapus menu <span class="font-semibold text-red-600">{{ $deleteProductName }}</span>?
                            Data yang dihapus tidak dapat dikembalikan.
                        </p>
                        <div class="flex justify-center gap-3">
                            <button wire:click="$set('showDeleteModal', false)" 
                                    class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition">
                                Batal
                            </button>
                            <button wire:click="deleteProduct" 
                                    class="px-4 py-2 text-sm text-white bg-red-600 rounded-xl hover:bg-red-700 transition">
                                Ya, Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Flash Message -->
    @if(session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform translate-y-2"
             class="fixed bottom-4 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg">
            {{ session('message') }}
        </div>
    @endif
</div>