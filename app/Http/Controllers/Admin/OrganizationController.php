<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Notifications\PlatformNotification;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.organizations', [
            'orgs' => Organization::with('owner')->withCount(['jobs', 'unlocks', 'members'])
                ->when($request->q, fn ($q, $t) => $q->where('name', 'like', "%$t%"))
                ->when($request->pending, fn ($q) => $q->whereNull('verified_at'))
                ->latest()->paginate(25)->withQueryString(),
        ]);
    }

    public function verify(Request $request, Organization $organization)
    {
        $organization->forceFill(['verified_at' => $organization->verified_at ? null : now()])->save();
        AuditLog::record($organization->verified_at ? 'organization.verified' : 'organization.unverified', $organization);
        if ($organization->verified_at) {
            $organization->owner->notify(new PlatformNotification('Company verified ✅', $organization->name.' now shows a verified badge.', route('app.company.edit'), 'badge-check'));
        }

        return back()->with('success', 'Updated.');
    }

    public function credits(Request $request, Organization $organization)
    {
        $data = $request->validate(['credits' => 'required|integer|between:-100,500', 'reason' => 'required|string|max:200']);
        $organization->forceFill(['unlock_credits' => max(0, $organization->unlock_credits + $data['credits'])])->save();
        AuditLog::record('organization.credits_adjusted', $organization, $data);

        return back()->with('success', 'Credits adjusted.');
    }
}
