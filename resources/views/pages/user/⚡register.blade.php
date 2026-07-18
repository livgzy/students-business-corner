<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

new class extends Component
{
    // public $count = 0;
    // public function increment()
    // {
    //     $this->count++; 
    // }
    
    #[Validate('required|min:3|max:100')]
    public $name = '';
    
    #[Validate('required|email|unique:users,email')]
    public $email = '';
    
    #[Validate('required|min:10|max:15')]
    public $phone = '';
    
    #[Validate('required|min:6|confirmed')]
    public $password = '';

    public $password_confirmation = '';

    public function register()
    {
        $validated = $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'phone' => $this->phone,
        ]);;

        Auth::login($user);
        return redirect()->intended('/');
    }

    public function render()
    {
        return $this->view()->layout('layouts::app', ['title' => 'Student Business Corner | Register']); 
    }
};
?>

{{-- <div>
    <h1>Counter: {{ $count }}</h1>
    <button wire:click="increment">+ Increment</button>
</div> --}}

<div>
    <div class="min-h-screen  flex items-center justify-center px-4 py-8">
        <div class="max-w-2xl w-full">
            <div class="bg-white rounded-2xl shadow-xl/20 p-6 md:p-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-800">Register</h2>
                </div>
                <form wire:submit.prevent="register" method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block font-semibold mb-2">
                                Nama Lengkap
                            </label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    wire:model="name"
                                    placeholder="Nama"
                                    class="w-full pl-3 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                                >
                            </div>
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Email Field -->
                        <div class="mb-4">
                            <label class="block font-semibold mb-2">
                                Email
                            </label>
                            <div class="relative">
                                <input 
                                    type="email" 
                                    wire:model="email"
                                    placeholder="Email"
                                    class="w-full pl-3 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                                >
                            </div>
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Phone Field -->
                        <div class="mb-4">
                            <label class="block font-semibold mb-2">
                                No. HP
                            </label>
                            <div class="relative">
                                <input 
                                    type="tel" 
                                    wire:model="phone"
                                    placeholder="No HP"
                                    class="w-full pl-3 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                                >
                            </div>
                            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            
                        </div>

                        <!-- Password Field -->
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

                        <div class="mb-8">
                            <label class="block font-semibold mb-2">
                                Konfirmasi Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    wire:model="password_confirmation"
                                    placeholder="Ulangi password"
                                    class="w-full pl-3 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                                >
                            </div>
                        </div>
                    </div>

                    <button type="submit" 
                            wire:loading.attr="disabled"
                            class="w-full bg-amber-500 hover:bg-amber-700 text-white font-semibold py-3 rounded-lg transition duration-200 flex items-center justify-center cursor-pointer">
                        <flux:icon.user-plus wire:loading.remove class="mr-2"/>
                        <flux:icon.loading wire:loading class="mr-2"/>
                        Register
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Sudah punya akun? 
                        <a href="/login" class="text-orange-500 font-semibold hover:text-orange-700 transition">
                            Login disini
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>