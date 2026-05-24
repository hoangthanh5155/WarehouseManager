<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $currentUser = auth()->user();

        return view('users.create', [
            'manageableRoles' => $this->creatableRoleOptions($currentUser),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $roles = array_keys($this->creatableRoleOptions($request->user()));

        if ($request->input('role') === User::ROLE_ADMIN) {
            return back()
                ->withErrors(['role' => 'Hệ thống chỉ cho phép một tài khoản Chủ kho/root.'])
                ->withInput();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_.-]+$/', 'unique:users,name'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in($roles)],
            'status' => ['required', Rule::in(array_keys($this->statusLabels()))],
        ]);

        $temporaryPassword = $this->resolveTemporaryPassword(
            $validated['name'],
            $request->input('initial_password')
        );

        $validated['created_by'] = $request->user()->id;
        $validated['must_change_password'] = true;
        $validated['password'] = $temporaryPassword;

        $user = User::query()->create($validated);

        return redirect()
            ->route('users.create')
            ->with('success', 'Đã tạo tài khoản nội bộ.')
            ->with('created_account', [
                'username' => $user->name,
                'temporary_password' => $temporaryPassword,
                'role_label' => $user->roleLabel(),
            ]);
    }

    public function edit(User $user): View
    {
        abort_unless(auth()->user()->canManageUser($user), 403);

        $user->load('featurePermissions');

        return view('users.edit', [
            'managedUser' => $user,
            'manageableRoles' => $this->editableRoleOptions(auth()->user(), $user),
            'statusLabels' => $this->statusLabels(),
            'roleReadonly' => $user->role === User::ROLE_ADMIN,
            'featurePermissionLabels' => User::featurePermissionLabels(),
            'assignedFeaturePermissions' => $user->featurePermissionAbilities(),
            'canManageFeaturePermissions' => auth()->user()->isAdmin() && $user->canReceiveFeaturePermissions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageUser($user), 403);

        $roleReadonly = $user->role === User::ROLE_ADMIN;
        $roles = array_keys($this->editableRoleOptions($request->user(), $user));

        if (!$roleReadonly && $request->input('role') === User::ROLE_ADMIN) {
            return back()
                ->withErrors(['role' => 'Hệ thống chỉ cho phép một tài khoản Chủ kho/root.'])
                ->withInput();
        }

        $rules = [
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(array_keys($this->statusLabels()))],
            'feature_permissions' => ['nullable', 'array'],
            'feature_permissions.*' => ['string', Rule::in(array_keys(User::featurePermissionLabels()))],
        ];

        if (!$roleReadonly) {
            $rules['role'] = ['required', Rule::in($roles)];
        }

        $validated = $request->validate($rules);

        if ($roleReadonly) {
            $validated['role'] = User::ROLE_ADMIN;
        }

        if ($user->id === $request->user()->id && $validated['status'] === User::STATUS_LOCKED) {
            return back()->withErrors(['status' => 'Không thể tự khóa tài khoản đang đăng nhập.'])->withInput();
        }

        if ($user->id === $request->user()->id && $validated['role'] !== User::ROLE_ADMIN && $request->user()->isAdmin()) {
            return back()->withErrors(['role' => 'Không thể tự hạ quyền Chủ kho của chính mình.'])->withInput();
        }

        $featurePermissions = $validated['feature_permissions'] ?? [];
        unset($validated['feature_permissions']);

        DB::transaction(function () use ($request, $user, $validated, $featurePermissions): void {
            $user->update($validated);
            $user->refresh();

            $this->syncFeaturePermissions($request, $user, $featurePermissions);
        });

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

    private function creatableRoleOptions(User $currentUser): array
    {
        return collect($currentUser->manageableRoles())
            ->except([User::ROLE_ADMIN])
            ->all();
    }

    private function editableRoleOptions(User $currentUser, User $targetUser): array
    {
        if ($targetUser->role === User::ROLE_ADMIN) {
            return [User::ROLE_ADMIN => User::roleLabels()[User::ROLE_ADMIN]];
        }

        return collect($currentUser->manageableRoles())
            ->except([User::ROLE_ADMIN])
            ->all();
    }

    private function generateTemporaryPassword(string $username): string
    {
        return 'WMS@' . $username . '#' . random_int(1000, 9999);
    }

    private function resolveTemporaryPassword(string $username, ?string $submittedPassword): string
    {
        $expectedPattern = '/^WMS@' . preg_quote($username, '/') . '#\d{4}$/';

        if (is_string($submittedPassword) && preg_match($expectedPattern, $submittedPassword)) {
            return $submittedPassword;
        }

        return $this->generateTemporaryPassword($username);
    }

    /**
     * @param array<int, string> $selectedAbilities
     */
    private function syncFeaturePermissions(Request $request, User $user, array $selectedAbilities): void
    {
        if (!$request->user()?->isAdmin()) {
            return;
        }

        if (!$user->canReceiveFeaturePermissions()) {
            $user->featurePermissions()->delete();
            return;
        }

        $allowedAbilities = array_keys(User::featurePermissionLabels());
        $selectedAbilities = collect($selectedAbilities)
            ->intersect($allowedAbilities)
            ->unique()
            ->values()
            ->all();

        if ($selectedAbilities === []) {
            $user->featurePermissions()->delete();
            return;
        }

        $user->featurePermissions()
            ->whereNotIn('ability', $selectedAbilities)
            ->delete();

        foreach ($selectedAbilities as $ability) {
            $user->featurePermissions()->updateOrCreate(
                ['ability' => $ability],
                [
                    'granted_by' => $request->user()->id,
                    'granted_at' => now(),
                ]
            );
        }
    }
}
