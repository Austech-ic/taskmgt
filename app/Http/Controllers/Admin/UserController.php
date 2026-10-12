<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w->where('name', 'like', "%$t%")->orWhere('email', 'like', "%$t%")))
            ->when($request->role === 'staff', fn ($q) => $q->whereNotNull('staff_role'))
            ->when($request->role === 'talent', fn ($q) => $q->where('is_talent', true))
            ->when($request->role === 'hirer', fn ($q) => $q->where('is_hirer', true))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.users', ['users' => $users, 'roles' => User::STAFF_ROLES]);
    }

    public function update(Request $request, User $user)
    {
        $actor = $request->user();
        $data = $request->validate([
            'staff_role' => 'nullable|in:'.implode(',', array_keys(User::STAFF_ROLES)),
            'status' => 'required|in:active,suspended',
        ]);
        abort_if($user->id === $actor->id && ($data['status'] === 'suspended' || $data['staff_role'] !== $actor->staff_role), 422, 'You can’t change your own role or suspend yourself.');
        // Only super admins may grant or remove privileged roles.
        $privileged = ['admin', 'super_admin', 'finance'];
        if ((in_array($data['staff_role'], $privileged, true) || in_array($user->staff_role, $privileged, true)) && $data['staff_role'] !== $user->staff_role) {
            abort_unless($actor->staff_role === 'super_admin', 403, 'Only a super administrator can change privileged roles.');
        }

        $before = ['staff_role' => $user->staff_role, 'status' => $user->status];
        $user->forceFill(['staff_role' => $data['staff_role'] ?: null, 'status' => $data['status']])->save();
        AuditLog::record('user.updated', $user, ['before' => $before, 'after' => $data]);

        return back()->with('success', $user->name.' updated.');
    }
}
