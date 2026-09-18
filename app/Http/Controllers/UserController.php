<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Auth::user()->canManageUsers(), 403);

        $term = trim((string) $request->query('q'));

        $users = User::query()
            ->with('creator')
            ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('kecamatan', 'like', "%{$term}%");
            }))
            ->when(! Auth::user()->isSuperadmin(), function ($query) {
                $user = Auth::user();

                $query->where('kecamatan', $user->kecamatan)
                    ->where('role', '!=', UserRole::Superadmin->value)
                    ->where('role', '!=', UserRole::AdminWilayah->value);
            })
            ->orderBy('role')
            ->orderBy('kecamatan')
            ->orderBy('name')
            ->get();

        return view('users.index', [
            'users' => $users,
            'term' => $term,
            'roles' => $this->manageableRoles(),
        ]);
    }

    public function create(): View
    {
        abort_unless(Auth::user()->canManageUsers(), 403);

        return view('users.form', [
            'user' => new User(['role' => UserRole::Petugas->value]),
            'roles' => $this->manageableRoles(),
            'isEdit' => false,
            'wilayah' => $this->wilayahTree(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        abort_unless(Auth::user()->canManageUsers(), 403);

        $data = $request->validated();
        $data['role'] = $this->sanitizeRole($request->input('role'));
        $data['created_by'] = Auth::id();

        if (! Auth::user()->isSuperadmin()) {
            $data['kecamatan'] = Auth::user()->kecamatan;
        }

        $user = User::create($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Akun '.$user->name.' berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        abort_unless($this->canManage($user), 403);

        return view('users.form', [
            'user' => $user,
            'roles' => $this->manageableRoles(),
            'isEdit' => true,
            'wilayah' => $this->wilayahTree(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        abort_unless($this->canManage($user), 403);

        $data = $request->validated();
        $data['role'] = $this->sanitizeRole($request->input('role'));

        if (! Auth::user()->isSuperadmin()) {
            $data['kecamatan'] = Auth::user()->kecamatan;
        }

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        if ($user->id === Auth::id() && isset($data['role'])) {
            Auth::user()->refresh();
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Akun '.$user->name.' diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($this->canManage($user), 403);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Akun dihapus.');
    }

    /**
     * Role yang boleh dibuat/dikelola pengguna aktif.
     *
     * @return list<string>
     */
    private function manageableRoles(): array
    {
        return collect(UserRole::cases())
            ->filter(fn (UserRole $role) => Auth::user()->canCreateRole($role))
            ->map(fn (UserRole $role) => $role->value)
            ->values()
            ->all();
    }

    /**
     * Pastikan role yang dikirim termasuk yang boleh dibuat.
     */
    private function sanitizeRole(string $role): string
    {
        $role = UserRole::tryFrom($role);

        if (! $role instanceof UserRole || ! Auth::user()->canCreateRole($role)) {
            return UserRole::Petugas->value;
        }

        return $role->value;
    }

    /**
     * Boleh mengelola akun target?
     */
    private function canManage(User $user): bool
    {
        $manager = Auth::user();

        if (! $manager->canManageUsers()) {
            return false;
        }

        if ($manager->isSuperadmin()) {
            return $user->role() !== UserRole::Superadmin;
        }

        // admin wilayah: hanya akun di kecamatan yang sama & lebih rendah
        return $user->kecamatan === $manager->kecamatan
            && $manager->outranks($user->role());
    }

    /**
     * Struktur master data wilayah untuk dropdown cascade: kecamatan → desa → rw → rt.
     *
     * @return array<string, array<string, array<string, list<string>>>>
     */
    private function wilayahTree(): array
    {
        $tree = [];

        foreach (Wilayah::kecamatans() as $kecamatan) {
            $tree[$kecamatan] = [];

            foreach (Wilayah::desas($kecamatan) as $desa) {
                $tree[$kecamatan][$desa] = [];

                foreach (Wilayah::rws($kecamatan, $desa) as $rw) {
                    $tree[$kecamatan][$desa][$rw] = Wilayah::rts($kecamatan, $desa, $rw);
                }
            }
        }

        return $tree;
    }
}
