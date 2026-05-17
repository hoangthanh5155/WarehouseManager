@extends('layouts.admin')

@section('title', 'Hồ sơ công ty/kho')

@section('content')
<div class="container-fluid px-1 px-md-2 mb-5" style="max-width: 980px;">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Hồ sơ công ty/kho</h3>
            <div class="text-muted">Thông tin thương hiệu hệ thống và thông tin bên bán trên hóa đơn.</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3 p-md-4">
            <form method="POST" action="{{ route('settings.company.update') }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Tên công ty/kho</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $profile->company_name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Mã số thuế</label>
                        <input type="text" name="tax_code" value="{{ old('tax_code', $profile->tax_code) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Hotline</label>
                        <input type="text" name="hotline" value="{{ old('hotline', $profile->hotline) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Ngân hàng</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $profile->bank_name) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Số tài khoản</label>
                        <input type="text" name="bank_account" value="{{ old('bank_account', $profile->bank_account) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted">Địa chỉ</label>
                        <input type="text" name="address" value="{{ old('address', $profile->address) }}" class="form-control">
                    </div>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger mt-3 mb-0">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary fw-bold" type="submit">Lưu hồ sơ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
