<?php

use Livewire\Component;

new class extends Component
{
    public function render()
    {
        return $this->view()
            ->layout('layouts::tenant', ['title' => 'Student Business Corner | Dashboard Tenant']); 
    }
};
?>

<div>
    You must be the change you wish to see in the world. - Mahatma Gandhi
</div>