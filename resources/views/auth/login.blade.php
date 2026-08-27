@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
<div class="bg-white text-gray-800 rounded-3xl shadow-2xl p-8 sm:p-10">
    <div class="text-center mb-7">
        <div class="w-20 h-20 mx-auto rounded-full bg-white border-2 border-emerald-100 flex items-center justify-center overflow-hidden shadow-inner mb-3">
            <img src="{{ asset('logo.png') }}" alt="Logo" class="w-full h-full object-contain p-1">
        </div>
        <h1 class="text-xl font-extrabold text-gray-900">MOROWALI JUARA</h1>
        <p class="text-[12px] text-gray-500">Command Center Dashboard</p>
    </div>

    @if ($errors->any())
        <x-alert type="error" title="Gagal masuk" class="mb-4">
            {{ $errors->first() }}
        </x-alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div class="space-y-1">
            <label for="email" class="block text-[12px] font-bold text-gray-700">Email</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">✉️</span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       placeholder="nama@morowali.go.id"
                       class="w-full rounded-xl border border-gray-300 pl-10 pr-3 py-3 text-[14px] focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            </div>
        </div>

        <div class="space-y-1">
            <label for="password" class="block text-[12px] font-bold text-gray-700">Kata Sandi</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔒</span>
                <input id="password" type="password" name="password" required
                       placeholder="••••••••"
                       class="w-full rounded-xl border border-gray-300 pl-10 pr-3 py-3 text-[14px] focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-500">
            </div>
        </div>

        <div class="flex items-center justify-between pt-1">
            <label class="inline-flex items-center text-[13px] text-gray-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                <span class="ml-2">Ingat saya</span>
            </label>
        </div>

        <button type="submit"
                class="w-full py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-bold text-[14px] shadow-lg shadow-emerald-600/30 transition focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2">
            MASUK
        </button>
    </form>

    <div class="mt-6 pt-4 border-t border-gray-100 text-center">
        <p class="text-[11px] text-gray-400">Kabupaten Morowali · Sulawesi Tengah</p>
        <p class="text-[11px] text-gray-400 mt-0.5">© {{ date('Y') }} Morowali Juara Command Center</p>
    </div>
</div>
@endsection
