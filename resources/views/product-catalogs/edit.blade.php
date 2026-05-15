@extends('layouts.admin')

@section('content')
<style>
    /* CSS đồng bộ Smart Dropdown giống trang nhập hàng */
    .smart-input-container { position: relative; }
    .smart-input { 
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%236c757d' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3E%3C/svg%3E"); 
        background-repeat: no-repeat; 
        background-position: right 15px center; 
        background-size: 14px; 
        padding-right: 40px; 
    }
    .smart-menu { display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ced4da; border-radius: 8px; margin-top: 5px; z-index: 1050; box-shadow: 0 6px 15px rgba(0,0,0,0.15); max-height: 200px; overflow-y: auto; }
    .smart-menu.show { display: block; }
    .smart-option { padding: 12px 15px; border-bottom: 1px solid #f8f9fa; cursor: pointer; color: #333; }
    .smart-option:hover { background: #e9ecef; }
    .smart-add-new { color: #0d6efd; font-weight: bold; background: #f1f5fa; position: sticky; bottom: 0; border-top: 1px solid #dee2e6; z-index: 10; }
</style>

<div class="d-flex align-items-center gap-3 mb-4 mt-2">
    <a href="{{ route('product-catalogs.index') }}" class="btn btn-light bg-white shadow-sm border rounded-3 px-3 py-2 text-decoration-none flex-shrink-0">
        <span class="fw-bold text-dark text-nowrap">⬅️ Trở về</span>
    </a>
    <h4 class="m-0 fw-bold text-uppercase text-dark lh-base">✏️ CHỈNH SỬA DANH MỤC</h4>
</div>

@if($errors->any())
    <div class="alert alert-danger p-2 mb-3 small border-0 shadow-sm">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-5">
    <div class="card-body p-3 p-md-4">
        <form action="{{ route('product-catalogs.update', $catalog->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="fw-bold small text-secondary mb-1">Tên Sản Phẩm</label>
                <input type="text" class="form-control bg-light fw-bold" value="{{ $catalog->product_name }}" readonly>
                <input type="hidden" name="product_name" value="{{ $catalog->product_name }}">
            </div>

            <div class="mb-3">
                <label class="fw-bold small text-secondary mb-1">Nhà Cung Cấp</label>
                <input type="text" class="form-control bg-light" value="{{ $catalog->supplier->name ?? 'N/A' }}" readonly>
                <input type="hidden" name="supplier_id" value="{{ $catalog->supplier_id }}">
            </div>

            @php
                $currentLoc = $catalog->products->first() && $catalog->products->first()->location ? $catalog->products->first()->location : null;
            @endphp
            <div class="mb-3 smart-input-container">
                <label class="fw-bold small text-secondary mb-1">Vị Trí Kệ (Đổi hàng loạt cho hàng trong kho)</label>
                <input type="text" id="loc_display_input" class="form-control smart-input" placeholder="Gõ tìm kiếm hoặc click để chọn kệ..." value="{{ $currentLoc ? $currentLoc->shelf_name : '' }}" autocomplete="off">
                <input type="hidden" name="location_id" id="loc_hidden_input" value="{{ $currentLoc ? $currentLoc->id : '' }}">
                
                <div class="smart-menu" id="loc_menu">
                    @foreach($locations as $l) 
                        <div class="smart-option" data-id="{{ $l->id }}">{{ $l->shelf_name }}</div> 
                    @endforeach
                    <div class="smart-option smart-add-new d-none">➕ Thêm mới kệ: <span class="new-text"></span></div>
                </div>
                <small class="text-muted d-block mt-1">💡 Khi chọn vị trí mới, toàn bộ hàng thuộc mẫu này đang có trong kho sẽ được chuyển sang vị trí vừa chọn.</small>
            </div>

            <div class="mb-3">
                <label class="fw-bold small text-secondary mb-1">Giá Sỉ (Wholesale Price)</label>
                <input type="number" step="0.01" name="wholesale_price" class="form-control" value="{{ old('wholesale_price', $catalog->wholesale_price) }}">
            </div>

            <div class="mb-3">
                <label class="fw-bold small text-secondary mb-1">% Biên Đại Lý (Agency Margin %)</label>
                <input type="number" step="0.01" name="agency_margin" class="form-control" value="{{ old('agency_margin', $catalog->agency_margin) }}">
            </div>

            <div class="mb-3">
                <label class="fw-bold small text-secondary mb-1">% Biên Bán Lẻ (Profit Margin %)</label>
                <input type="number" step="0.01" name="profit_margin" class="form-control" value="{{ old('profit_margin', $catalog->profit_margin) }}">
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2 py-2 fw-bold shadow-sm">
                💾 CẬP NHẬT THAY ĐỔI
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Xử lý tìm kiếm và hiển thị Menu Dropdown khi focus / click / input
    $('#loc_display_input').on('input focus click', function() {
        let originalText = $(this).val();
        let searchText = originalText.toLowerCase().trim();
        let $menu = $('#loc_menu');
        
        $menu.addClass('show');
        let exactMatch = false; 
        let hasVisible = false;

        $menu.find('.smart-option:not(.smart-add-new)').each(function() {
            let itemText = $(this).text();
            if(itemText.toLowerCase().includes(searchText)) {
                $(this).show(); 
                hasVisible = true;
                if(itemText.toLowerCase() === searchText) exactMatch = true;
            } else {
                $(this).hide();
            }
        });

        // Hiện nút "Thêm mới kệ" nếu chưa có kết quả trùng khớp hoàn toàn
        let $addNewBtn = $menu.find('.smart-add-new');
        if(originalText.trim() !== '' && !exactMatch) {
            $addNewBtn.removeClass('d-none').find('.new-text').text(originalText);
            hasVisible = true;
        } else {
            $addNewBtn.addClass('d-none');
        }
        
        if(!hasVisible) $menu.removeClass('show');
    });

    // Chọn vị trí có sẵn từ danh sách
    $('#loc_menu').on('mousedown touchstart', '.smart-option:not(.smart-add-new)', function(e) {
        e.preventDefault();
        let textToSet = $(this).text();
        let idToSet = $(this).data('id');
        
        $('#loc_display_input').val(textToSet);
        $('#loc_hidden_input').val(idToSet);
        $('#loc_menu').removeClass('show');
    });

    // AJAX thêm nhanh vị trí kệ mới khi nhấn vào "Thêm mới kệ: ..."
    $('#loc_menu').on('mousedown touchstart', '.smart-add-new', function(e) {
        e.preventDefault();
        let shelfName = $(this).find('.new-text').text().trim();
        let $menu = $('#loc_menu');

        if (!shelfName) return;

        $.ajax({
            url: "{{ route('locations.store') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                shelf_name: shelfName,
                is_ajax: true
            },
            success: function(res) {
                if(res.status === 'success') {
                    // Cập nhật giá trị cho input hiển thị và input ẩn
                    $('#loc_display_input').val(res.data.shelf_name);
                    $('#loc_hidden_input').val(res.data.id);
                    
                    // Thêm phần tử mới vào Menu để người dùng có thể chọn lại
                    let newOption = `<div class="smart-option" data-id="${res.data.id}">${res.data.shelf_name}</div>`;
                    $menu.prepend(newOption);
                    $menu.removeClass('show');
                    alert('✅ Đã tạo mới kệ: ' + res.data.shelf_name);
                }
            },
            error: function(xhr) {
                let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Lỗi hệ thống!';
                if (xhr.status === 422 && xhr.responseJSON.errors && xhr.responseJSON.errors.shelf_name) {
                    msg = xhr.responseJSON.errors.shelf_name[0];
                }
                alert('❌ ' + msg);
            }
        });
    });

    // Tự động ẩn menu khi bấm ra ngoài
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.smart-input-container').length) {
            $('#loc_menu').removeClass('show');
        }
    });
});
</script>
@endsection