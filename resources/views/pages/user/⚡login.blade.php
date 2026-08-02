<?php

use Livewire\Component;
use Livewire\Attributes\Validate;


new class extends Component
{
    #[Validate('required')]
    public $email = '';
    
    #[Validate('required')]
    public $password = '';

    public function login()
    {
        $this->validate();
        
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            session()->regenerate();
            return redirect('/');
        }
        
        $this->addError('email', 'email atau password salah.');
    }


    public function render()
    {
        return $this->view()
            ->layout('layouts::app', ['title' => 'Student Business Corner | Login']); 
    }
    
};
?>


<div>
    <div class="min-h-screen  flex items-center justify-center px-4 py-8">
        <div class="max-w-2xl w-full">
            <div class="bg-white rounded-2xl shadow-xl/20 p-6 md:p-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-800">Login</h2>
                </div>
                <form wire:submit.prevent="login" method="POST">
                @csrf
                    <div class="grid grid-cols-1 md:grid-cols gap-4">
                        <div class="mb-4">
                            <label class="block font-semibold mb-2">
                                Email
                            </label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    wire:model="email"
                                    placeholder="Email"
                                    class="w-full pl-3 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                                >
                            </div>
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block font-semibold mb-2">
                                Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    wire:model="password"
                                    placeholder="Password"
                                    class="w-full pl-3 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                                >
                            </div>
                            @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end mb-8">
                        <a href="#" class="text-sm text-orange-600 hover:text-orange-700 transition">
                            Lupa password?
                        </a>
                    </div>

                    <button type="submit" 
                            wire:loading.attr="disabled"
                            class="w-full bg-amber-500 hover:bg-amber-700 text-white font-semibold py-3 rounded-lg transition duration-200 flex items-center justify-center cursor-pointer">
                            <flux:icon.arrow-right-start-on-rectangle wire:loading.remove class="mr-2"/>
                            <flux:icon.loading wire:loading class="mr-2"/>
                        Login
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Belum punya akun? 
                        <a href="/register" class="text-orange-500 font-semibold hover:text-orange-700 transition">
                            Register disini
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>