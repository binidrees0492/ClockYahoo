@extends('layouts.app')

@section('content')
    <div class="p-6 flex flex-col lg:flex-row gap-6 w-full">

        <div class="w-full lg:w-[25%] shrink-0">
            <livewire:time-clock />
        </div>

        <div class="w-full lg:w-[75%] min-w-0">
            <livewire:timesheet-schedule-tabs />
        </div>

    </div>
@endsection
