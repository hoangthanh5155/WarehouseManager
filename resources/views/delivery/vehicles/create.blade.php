@extends('layouts.admin')

@section('title', 'Tạo phương tiện giao hàng')

@section('content')
<div class="container-fluid px-1 px-md-2" style="max-width: 900px;">
    <h3 class="fw-bold mb-3">Tạo phương tiện giao hàng</h3>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3 p-md-4">
            <form method="POST" action="{{ route('delivery.vehicles.store') }}">
                @include('delivery.vehicles._form')
            </form>
        </div>
    </div>
</div>
@endsection
