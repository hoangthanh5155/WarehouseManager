<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InternalUserController extends Controller
{
    public function index(): View
    {
        $currentUser = auth()->user();
        abort_unless($currentUser?->canManageUsers(), 403);

        $usersQuery = User::query();

        if ($currentUser->isWarehouseManager()) {
            $usersQuery->whereIn('role', [
                User::ROLE_WAREHOUSE_STAFF,
                User::ROLE_SALES_STAFF,
                User::ROLE_VIEWER,
            ]);
        } elseif (!$currentUser->isAdmin()) {
            abort(403);
        }

        $users = $usersQuery
            ->orderByRaw("role = 'admin' desc")
            ->orderBy('name')
            ->paginate(12);

        return view('users.index', [
            'users' => $users,
            'roleLabels' => User::roleLabels(),
            'statusLabels' => $this->statusLabels(),
            'manageableRoles' => $currentUser->manageableRoles(),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'manageableRoles' => auth()->user()->manageableRoles(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $roles = array_keys($request->user()->manageableRoles());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_.-]+$/', 'unique:users,name'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in($roles)],
            'status' => ['required', Rule::in(array_keys($this->statusLabels()))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validated['role'] === User::ROLE_ADMIN && User::query()->where('role', User::ROLE_ADMIN)->exists()) {
            return back()->withErrors(['role' => 'Hệ thống chỉ cho phép một tài khoản Chủ kho/root.'])->withInput();
        }

        $validated['created_by'] = $request->user()->id;
        $validated['must_change_password'] = true;

        User::query()->create($validated);

        return redirect()->route('users.index')->with('success', 'Đã tạo tài khoản nội bộ.');
    }

    public function edit(User $user): View
    {
        abort_unless(auth()->user()->canManageUser($user), 403);

        return view('users.edit', [
            'managedUser' => $user,
            'manageableRoles' => auth()->user()->manageableRoles(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageUser($user), 403);

        $roles = array_keys($request->user()->manageableRoles());

        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in($roles)],
            'status' => ['required', Rule::in(array_keys($this->statusLabels()))],
        ]);

        if ($user->id === $request->user()->id && $validated['status'] === User::STATUS_LOCKED) {
            return back()->withErrors(['status' => 'Không thể tự khóa tài khoản đang đăng nhập.'])->withInput();
        }

        if ($user->id === $request->user()->id && $validated['role'] !== User::ROLE_ADMIN && $request->user()->isAdmin()) {
            return back()->withErrors(['role' => 'Không thể tự hạ quyền Chủ kho của chính mình.'])->withInput();
        }

        if ($validated['role'] === User::ROLE_ADMIN && $user->role !== User::ROLE_ADMIN && User::query()->where('role', User::ROLE_ADMIN)->exists()) {
            return back()->withErrors(['role' => 'Hệ thống chỉ cho phép một tài khoản Chủ kho/root.'])->withInput();
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageUser($user), 403);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Không thể tự khóa tài khoản đang đăng nhập.']);
        }

        $user->update([
            'status' => $user->status === User::STATUS_ACTIVE ? User::STATUS_LOCKED : User::STATUS_ACTIVE,
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái tài khoản.');
    }

    public function generateResetLink(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageUser($user), 403);

        if ($user->id === $request->user()->id || !$request->user()->canManageUser($user)) {
            abort(403, 'Không thể áp dụng yêu cầu này cho tài khoản này.');
        }

        $token = Password::createToken($user);
        $resetLink = url('/reset-password/' . $token) . '?email=' . urlencode($user->email);

        $user->update([
            'must_change_password' => true,
        ]);

        return redirect()
            ->route('users.edit', $user)
            ->with('reset_link', $resetLink)
            ->with('success', 'Đã tạo liên kết đặt lại mật khẩu. Liên kết chỉ hiển thị một lần.');
    }

    private function statusLabels(): array
    {
        return [
            User::STATUS_ACTIVE => 'Đang hoạt động',
            User::STATUS_LOCKED => 'Đã khóa',
        ];
    }
}
