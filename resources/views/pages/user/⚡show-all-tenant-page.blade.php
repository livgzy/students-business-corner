<?php

use Livewire\Component;

new class extends Component
{
    public function render()
    {
        return $this->view()
        ->layout('layouts.app')
        ->title("Student Business Corner | All Tenant");
    }
};
?>

<div>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">Semua Tenant</h1>
        </div>
        <livewire:tenant-card lazy/>
    </div>
</div>