<?php

use Livewire\Component;

new class extends Component
{
    public $categories;
    public $selectedCategory = null;

    public function mount($categories)
    {
        $this->categories = $categories;
    }

    public function setSelectedCategory($id)
    {
        $this->selectedCategory = $id;

        $this->dispatch('categorySelected', categoryId: $id);
    }
};
?>

<div class="flex flex-wrap gap-2">
    <button  
        wire:click="setSelectedCategory(null)"
        type="button"
        class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 whitespace-nowrap
        {{ !$selectedCategory ? 'bg-orange-600 text-white shadow-md' : 'bg-gray-200 hover:bg-gray-200' }}"
    >
        Semua
    </button>

    @foreach($categories as $category)
    <button 
        wire:click="setSelectedCategory({{ $category->id }})"
        class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 whitespace-nowrap cursor-pointer
        {{ $selectedCategory == $category->id ? 'bg-orange-600 text-white shadow-md' : 'bg-gray-200 hover:bg-gray-200' }}"
    >
        {{ $category->name }}
    </button>
    @endforeach
</div>