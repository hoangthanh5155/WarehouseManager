<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

Artisan::command('stock:audit-consistency', function () {
    $this->info('Warehouse consistency audit (READ-ONLY)');
    $this->line('No data will be changed.');

    $hasStockMovements = Schema::hasTable('stock_movements');
    $hasExportVoucherId = Schema::hasColumn('products', 'export_voucher_id');
    $hasImportVoucherId = Schema::hasColumn('products', 'import_voucher_id');

    $statusInStock = DB::table('products')->where('status', 1)->count();
    $statusExported = DB::table('products')->where('status', 2)->count();

    $missingExportVoucherQuery = DB::table('products')
        ->where('status', 2);

    if ($hasExportVoucherId) {
        $missingExportVoucherQuery->whereNull('export_voucher_id');
    }

    $missingExportVoucherCount = (clone $missingExportVoucherQuery)->count();
    $missingExportVoucherExamples = (clone $missingExportVoucherQuery)
        ->select('id', 'serial_number', 'status')
        ->orderBy('id')
        ->limit(20)
        ->get();

    [$legacyJsonMismatchCount, $legacyJsonMismatchExamples] = auditExportVoucherItemsAgainstProducts();

    $missingExportMovementCount = null;
    $missingExportMovementExamples = collect();
    if ($hasStockMovements && $hasExportVoucherId) {
        $missingExportMovementQuery = DB::table('products')
            ->whereNotNull('products.export_voucher_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('stock_movements')
                    ->whereColumn('stock_movements.product_id', 'products.id')
                    ->whereColumn('stock_movements.export_voucher_id', 'products.export_voucher_id')
                    ->where('stock_movements.movement_type', StockMovement::TYPE_EXPORT);
            });

        $missingExportMovementCount = (clone $missingExportMovementQuery)->count();
        $missingExportMovementExamples = (clone $missingExportMovementQuery)
            ->select('products.id', 'products.serial_number', 'products.export_voucher_id')
            ->orderBy('products.id')
            ->limit(20)
            ->get();
    }

    $missingImportMovementCount = null;
    $missingImportMovementExamples = collect();
    if ($hasStockMovements && $hasImportVoucherId) {
        $missingImportMovementQuery = DB::table('products')
            ->whereNotNull('products.import_voucher_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('stock_movements')
                    ->whereColumn('stock_movements.product_id', 'products.id')
                    ->whereColumn('stock_movements.import_voucher_id', 'products.import_voucher_id')
                    ->where('stock_movements.movement_type', StockMovement::TYPE_IMPORT);
            });

        $missingImportMovementCount = (clone $missingImportMovementQuery)->count();
        $missingImportMovementExamples = (clone $missingImportMovementQuery)
            ->select('products.id', 'products.serial_number', 'products.import_voucher_id')
            ->orderBy('products.id')
            ->limit(20)
            ->get();
    }

    $orphanMovementCount = null;
    $orphanMovementExamples = collect();
    if ($hasStockMovements) {
        $orphanMovementQuery = DB::table('stock_movements')
            ->leftJoin('products', 'products.id', '=', 'stock_movements.product_id')
            ->whereNotNull('stock_movements.product_id')
            ->whereNull('products.id');

        $orphanMovementCount = (clone $orphanMovementQuery)->count();
        $orphanMovementExamples = (clone $orphanMovementQuery)
            ->select(
                'stock_movements.id',
                'stock_movements.product_id',
                'stock_movements.serial_number',
                'stock_movements.movement_type'
            )
            ->orderBy('stock_movements.id')
            ->limit(20)
            ->get();
    }

    $exportMovementStillInStockCount = null;
    $exportMovementStillInStockExamples = collect();
    if ($hasStockMovements) {
        $exportMovementStillInStockQuery = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->where('stock_movements.movement_type', StockMovement::TYPE_EXPORT)
            ->where('products.status', 1);

        $exportMovementStillInStockCount = (clone $exportMovementStillInStockQuery)->count();
        $exportMovementStillInStockExamples = (clone $exportMovementStillInStockQuery)
            ->select(
                'products.id',
                'products.serial_number',
                'products.status',
                'stock_movements.id as movement_id'
            )
            ->orderBy('stock_movements.id')
            ->limit(20)
            ->get();
    }

    $this->newLine();
    $this->table(['Check', 'Count'], [
        ['products status = 1', $statusInStock],
        ['products status = 2', $statusExported],
        ['products status = 2 but missing export_voucher_id', $missingExportVoucherCount],
        ['serials in export_vouchers.items but product status is not 2', $legacyJsonMismatchCount],
        ['products with export_voucher_id but missing export stock_movement', formatAuditCount($missingExportMovementCount)],
        ['products with import_voucher_id but missing import stock_movement', formatAuditCount($missingImportMovementCount)],
        ['stock_movements pointing to missing product_id', formatAuditCount($orphanMovementCount)],
        ['export stock_movements whose product is still status = 1', formatAuditCount($exportMovementStillInStockCount)],
    ]);

    renderAuditExamples($this, 'products status = 2 but missing export_voucher_id', $missingExportVoucherExamples, [
        'id',
        'serial_number',
        'status',
    ]);
    renderAuditExamples($this, 'serials in export_vouchers.items but product status is not 2', $legacyJsonMismatchExamples, [
        'serial_number',
        'product_id',
        'product_status',
        'export_voucher_id',
        'export_code',
    ]);
    renderAuditExamples($this, 'products with export_voucher_id but missing export stock_movement', $missingExportMovementExamples, [
        'id',
        'serial_number',
        'export_voucher_id',
    ]);
    renderAuditExamples($this, 'products with import_voucher_id but missing import stock_movement', $missingImportMovementExamples, [
        'id',
        'serial_number',
        'import_voucher_id',
    ]);
    renderAuditExamples($this, 'stock_movements pointing to missing product_id', $orphanMovementExamples, [
        'id',
        'product_id',
        'serial_number',
        'movement_type',
    ]);
    renderAuditExamples($this, 'export stock_movements whose product is still status = 1', $exportMovementStillInStockExamples, [
        'id',
        'serial_number',
        'status',
        'movement_id',
    ]);

    return 0;
})->purpose('Read-only audit for warehouse legacy core and stock movements');

function auditExportVoucherItemsAgainstProducts(): array
{
    $count = 0;
    $examples = collect();
    $seenSerials = [];

    DB::table('export_vouchers')
        ->select('id', 'export_code', 'items')
        ->orderBy('id')
        ->chunkById(100, function ($vouchers) use (&$count, &$examples, &$seenSerials) {
            foreach ($vouchers as $voucher) {
                $serials = collect(json_decode((string) $voucher->items, true) ?: [])
                    ->flatMap(fn ($item) => $item['serials'] ?? [])
                    ->map(fn ($serial) => trim((string) $serial))
                    ->filter()
                    ->unique()
                    ->values();

                if ($serials->isEmpty()) {
                    continue;
                }

                $productsBySerial = DB::table('products')
                    ->whereIn('serial_number', $serials)
                    ->get(['id', 'serial_number', 'status'])
                    ->keyBy('serial_number');

                foreach ($serials as $serial) {
                    if (isset($seenSerials[$serial])) {
                        continue;
                    }

                    $seenSerials[$serial] = true;
                    $product = $productsBySerial->get($serial);

                    if ($product && (int) $product->status === 2) {
                        continue;
                    }

                    $count++;

                    if ($examples->count() < 20) {
                        $examples->push((object) [
                            'serial_number' => $serial,
                            'product_id' => $product?->id ?: 'missing',
                            'product_status' => $product?->status ?? 'missing',
                            'export_voucher_id' => $voucher->id,
                            'export_code' => $voucher->export_code,
                        ]);
                    }
                }
            }
        }, 'id');

    return [$count, $examples];
}

function formatAuditCount(?int $count): string|int
{
    return $count ?? 'schema unavailable';
}

function renderAuditExamples($command, string $title, $rows, array $columns): void
{
    if ($rows->isEmpty()) {
        return;
    }

    $command->newLine();
    $command->warn($title . ' - examples (max 20)');
    $command->table($columns, $rows->map(function ($row) use ($columns) {
        return collect($columns)
            ->map(fn ($column) => data_get($row, $column))
            ->all();
    })->all());
}
