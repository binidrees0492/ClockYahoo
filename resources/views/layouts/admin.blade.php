@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Admin & Employees</h1>
        <p class="text-gray-600">Manage company settings, users, and roles.</p>
    </div>

    <!-- This injects the interactive Livewire component -->
    <livewire:employees />
@endsection
