```php
<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

new class extends Component {
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $this->remember)) {
            request()->session()->regenerate();

            $this->redirectIntended(
                default: route('dashboard')
            );

            return;
        }

        $this->addError('email', 'Email atau password tidak sesuai.');
    }
};
?>

<div class="min-h-screen flex items-center justify-center bg-slate-100 px-4 py-10">
    <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">

        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-slate-900 text-2xl font-bold text-white">
                K
            </div>

            <h1 class="text-2xl font-bold text-slate-900">
                KOOMBAAH
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Silakan masuk ke akun Anda
            </p>
        </div>

        <form wire:submit="login" class="space-y-5">
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">
                    Email
                </label>

                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    autocomplete="username"
                    required
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                    placeholder="nama@perusahaan.com"
                />

                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-700">
                    Password
                </label>

                <input
                    id="password"
                    type="password"
                    wire:model="password"
                    autocomplete="current-password"
                    required
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                    placeholder="Masukkan password"
                />
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input
                    type="checkbox"
                    wire:model="remember"
                    class="rounded border-slate-300"
                />
                Ingat saya
            </label>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full rounded-lg bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-700 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="login">Masuk</span>
                <span wire:loading wire:target="login">Memeriksa...</span>
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} Koombaah. Hak cipta dilindungi.
        </p>
    </div>
</div>
```