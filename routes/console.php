<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\ExportVoucher;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('owner:reset-password {--email=} {--password=}', function () {
    $email = $this->option('email');
    $plainPassword = $this->option('password');

    $owner = $email
        ? User::query()->where('email', $email)->where('role', User::ROLE_ADMIN)->first()
        : User::query()->where('role', User::ROLE_ADMIN)->oldest('id')->first();

    if (!$owner) {
        $ownerEmail = $email ?: 'admin@warehouse.local';
        $owner = User::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin Chủ kho',
            'email' => $ownerEmail,
            'password' => $plainPassword ?: Str::password(16),
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'must_change_password' => false,
        ]);

        $this->warn('Chưa có tài khoản Chủ kho, đã tạo owner mới: ' . $owner->email);
        if (!$plainPassword) {
            $this->warn('Mật khẩu đã được tạo ngẫu nhiên nội bộ. Hãy chạy lại lệnh với --password=... để đặt mật khẩu bạn biết.');
            return 1;
        }
    }

    if (!$plainPassword) {
        $plainPassword = $this->secret('Nhập mật khẩu mới cho owner');
        $confirm = $this->secret('Nhập lại mật khẩu mới');

        if ($plainPassword !== $confirm) {
            $this->error('Mật khẩu nhập lại không khớp.');
            return 1;
        }
    }

    if (strlen((string) $plainPassword) < 8) {
        $this->error('Mật khẩu phải có tối thiểu 8 ký tự.');
        return 1;
    }

    $owner->forceFill([
        'password' => $plainPassword,
        'status' => User::STATUS_ACTIVE,
        'must_change_password' => false,
        'remember_token' => null,
    ])->save();

    $this->info('Đã đặt lại mật khẩu owner: ' . $owner->email);
    return 0;
})->purpose('Reset password for the root owner/admin account safely');

Artisan::command('stock:backfill-movements', function () {
    $createdImports = 0;
    $createdExports = 0;
    $linkedExports = 0;

    Product::query()
        ->with(['exportVoucher'])
        ->orderBy('id')
        ->chunkById(200, function ($products) use (&$createdImports, &$createdExports, &$linkedExports) {
            foreach ($products as $product) {
                $importExists = StockMovement::query()
                    ->where('product_id', $product->id)
                    ->where('movement_type', StockMovement::TYPE_IMPORT)
                    ->exists();

                if (!$importExists) {
                    StockMovement::create([
                        'movement_type' => StockMovement::TYPE_IMPORT,
                        'product_id' => $product->id,
                        'serial_number' => $product->serial_number,
                        'product_catalog_id' => $product->product_catalog_id,
                        'supplier_id' => $product->supplier_id,
                        'from_status' => null,
                        'to_status' => 1,
                        'from_location_id' => null,
                        'to_location_id' => $product->location_id,
                        'import_voucher_id' => $product->import_voucher_id,
                        'quantity' => 1,
                        'note' => $product->import_voucher_id ? null : 'Được tạo lại từ dữ liệu sản phẩm hiện có.',
                        'occurred_at' => $product->imported_at ?? $product->created_at ?? now(),
                    ]);
                    $createdImports++;
                }

                if ((int) $product->status !== 2) {
                    continue;
                }

                if (!$product->export_voucher_id) {
                    $voucher = ExportVoucher::query()
                        ->where('items', 'like', '%' . $product->serial_number . '%')
                        ->orderByDesc('exported_at')
                        ->first();

                    if ($voucher) {
                        $items = is_string($voucher->items) ? json_decode($voucher->items, true) : $voucher->items;
                        $containsSerial = collect($items ?: [])->contains(function ($item) use ($product) {
                            return in_array($product->serial_number, $item['serials'] ?? [], true);
                        });

                        if ($containsSerial) {
                            $product->forceFill([
                                'export_voucher_id' => $voucher->id,
                                'exported_at' => $product->exported_at ?? $voucher->exported_at,
                            ])->save();
                            $linkedExports++;
                        }
                    }
                }

                $exportExists = StockMovement::query()
                    ->where('product_id', $product->id)
                    ->where('movement_type', StockMovement::TYPE_EXPORT)
                    ->exists();

                if (!$exportExists) {
                    StockMovement::create([
                        'movement_type' => StockMovement::TYPE_EXPORT,
                        'product_id' => $product->id,
                        'serial_number' => $product->serial_number,
                        'product_catalog_id' => $product->product_catalog_id,
                        'supplier_id' => $product->supplier_id,
                        'from_status' => 1,
                        'to_status' => 2,
                        'from_location_id' => $product->location_id,
                        'to_location_id' => null,
                        'export_voucher_id' => $product->export_voucher_id,
                        'quantity' => 1,
                        'note' => $product->export_voucher_id ? null : 'Đã xuất trước khi có hệ thống truy vết đầy đủ.',
                        'occurred_at' => $product->exported_at ?? $product->updated_at ?? now(),
                    ]);
                    $createdExports++;
                }
            }
        });

    $this->info("Đã tạo {$createdImports} movement nhập, {$createdExports} movement xuất.");
    $this->info("Đã liên kết {$linkedExports} sản phẩm với phiếu xuất cũ.");
    return 0;
})->purpose('Backfill stock movements for existing product serial data');
