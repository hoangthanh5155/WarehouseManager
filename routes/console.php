<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
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
