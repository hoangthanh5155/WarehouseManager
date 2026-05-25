@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Loại phương tiện</label>
        <select name="vehicle_type" class="form-select" required>
            @foreach($typeLabels as $value => $label)
                <option value="{{ $value }}" @selected(old('vehicle_type', $vehicle->vehicle_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Trạng thái</label>
        <select name="status" class="form-select" required>
            @foreach($statusLabels as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $vehicle->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Biển kiểm soát</label>
        <input type="text" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Trọng tải</label>
        <input type="number" step="0.01" min="0" name="load_capacity" value="{{ old('load_capacity', $vehicle->load_capacity) }}" class="form-control">
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Ghi chú</label>
        <textarea name="note" rows="3" class="form-control">{{ old('note', $vehicle->note) }}</textarea>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger mt-3">{{ $errors->first() }}</div>
@endif

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('delivery.vehicles.index') }}" class="btn btn-light border fw-bold">Hủy</a>
    <button type="submit" class="btn btn-primary fw-bold">Lưu phương tiện</button>
</div>
