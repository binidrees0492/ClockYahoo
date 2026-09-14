@extends('layouts.app')

@section('content')
    <div class="bg-white rounded shadow p-6">
        <livewire:time-off.policy-wizard :policy-id="$policyId ?? null" />
    </div>
@endsection
