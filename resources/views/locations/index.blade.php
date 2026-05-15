@extends('layouts.admin')

@section('content')
    <div class="d-flex align-items-center gap-3 mb-4 mt-2">
        <a href="{{ url('/') }}" class="btn btn-light bg-white shadow-sm border rounded-3 px-3 py-2 text-decoration-none flex-shrink-0">
            <span class="fw-bold text-dark text-nowrap">⬅️ Trở về</span>
        </a>
        <h4 class="m-0 fw-bold text-uppercase text-dark lh-base">📍 QUẢN LÝ VỊ TRÍ KỆ</h4>
    </div>

    @if(session('success')) <div class="alert alert-success p-2 mb-3 small border-0 shadow-sm">🎉 {{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger p-2 mb-3 small border-0 shadow-sm">⚠️ {{ session('error') }}</div> @endif

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.95rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tên Kệ / Vị trí</th>
                            <th class="text-end pe-3" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locations as $l)
                        <tr>
                            <td class="ps-3"><strong class="text-primary">📍 {{ $l->shelf_name }}</strong></td>
                            <td class="text-end pe-3">
                                <form action="{{ route('locations.destroy', $l->id) }}" method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa vị trí này?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1">🗑️ Xóa</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-center py-4 text-muted">📭 Chưa có vị trí kệ nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection