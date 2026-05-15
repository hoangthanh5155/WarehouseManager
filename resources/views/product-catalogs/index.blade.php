@extends('layouts.admin')

@section('content')
    <div class="d-flex align-items-center gap-3 mb-4 mt-2">
        <a href="{{ url('/') }}" class="btn btn-light bg-white shadow-sm border rounded-3 px-3 py-2 text-decoration-none flex-shrink-0">
            <span class="fw-bold text-dark text-nowrap">⬅️ Trở về</span>
        </a>
        <h4 class="m-0 fw-bold text-uppercase text-dark lh-base">📱 QUẢN LÝ DANH MỤC SẢN PHẨM</h4>
    </div>

    @if(session('success')) <div class="alert alert-success p-2 mb-3 small border-0 shadow-sm">🎉 {{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger p-2 mb-3 small border-0 shadow-sm">⚠️ {{ session('error') }}</div> @endif

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.95rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tên sản phẩm</th>
                            <th>Nhà cung cấp</th>
                            <th>Vị trí kệ</th> 
                            <th class="text-end pe-3" style="width: 90px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($catalogs as $cat)
                        <tr>
                            <td class="ps-3"><strong class="text-dark">{{ $cat->product_name }}</strong></td>
                            <td class="text-muted">{{ $cat->supplier ? $cat->supplier->name : 'N/A' }}</td>
                            
                            {{-- Lấy thông tin vị trí kệ từ các sản phẩm thực tế thuộc mẫu catalog này --}}
                            <td class="text-primary fw-bold">
                                @if($cat->products && $cat->products->isNotEmpty())
                                    @php
                                        $locations = $cat->products->map(function($prod) {
                                            return $prod->location ? $prod->location->shelf_name : null;
                                        })->filter()->unique()->implode(', ');
                                    @endphp
                                    
                                    {{ $locations ?: 'Chưa gắn vị trí' }}
                                @else
                                    <span class="text-muted fw-normal small">Hết hàng / Chưa nhập</span>
                                @endif
                            </td>
                            
                            <td class="text-end pe-3">
                                <div class="d-flex flex-column gap-2 align-items-end">
                                    <a href="{{ route('product-catalogs.edit', $cat->id) }}" class="btn btn-sm btn-outline-primary px-2 py-1 w-100">
                                        ✏️ Sửa
                                    </a>
                                    
                                    <form action="{{ route('product-catalogs.destroy', $cat->id) }}" method="POST" class="w-100" onsubmit="return confirm('Bạn chắc chắn muốn xóa?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1 w-100">
                                            🗑️ Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">📭 Chưa có dữ liệu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection