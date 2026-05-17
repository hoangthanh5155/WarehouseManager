<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function edit(): View
    {
        $profile = CompanyProfile::current() ?? new CompanyProfile([
            'company_name' => CompanyProfile::fallbackName(),
        ]);

        return view('settings.company-profile', compact('profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:100'],
            'hotline' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = CompanyProfile::current();
        if ($profile) {
            $profile->update($validated);
        } else {
            CompanyProfile::query()->create($validated);
        }

        return redirect()->route('settings.company.edit')->with('success', 'Đã cập nhật hồ sơ công ty/kho.');
    }
}
