<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function edit(Request $request)
    {
        $org = $request->user()->organization();

        return view('app.hirer.company', ['org' => $org, 'members' => $org?->members ?? collect(), 'seats' => $org?->activeSubscription()?->plan->recruiter_seats ?? 1]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:80',
            'size' => 'nullable|in:1-10,11-50,51-200,201-1000,1000+',
            'city' => 'nullable|string|max:80',
            'country' => 'required|string|max:80',
            'about' => 'nullable|string|max:2000',
            'registration_number' => 'nullable|string|max:40',
            'logo' => 'nullable|image|max:2048',
        ]);

        $org = $user->organization();
        if (! $org) {
            $org = new Organization;
            $org->owner_id = $user->id;
            $slug = Str::slug($data['name']) ?: 'company';
            $org->slug = Organization::where('slug', $slug)->exists() ? $slug.'-'.Str::lower(Str::random(4)) : $slug;
        } else {
            abort_unless(in_array($org->members->firstWhere('id', $user->id)?->pivot->role, ['owner', 'admin'], true), 403);
        }
        $org->fill(collect($data)->except('logo')->all());
        if ($request->hasFile('logo')) {
            $org->logo_path = $request->file('logo')->store('logos', 'public');
        }
        $org->save();
        $org->members()->syncWithoutDetaching([$user->id => ['role' => $org->owner_id === $user->id ? 'owner' : 'recruiter']]);

        return redirect()->route('app.company.edit')->with('success', 'Company profile saved 🏢');
    }

    public function addMember(Request $request)
    {
        $org = $request->user()->organization();
        abort_unless($org && $org->owner_id === $request->user()->id, 403);
        $data = $request->validate(['email' => 'required|email|exists:users,email', 'role' => 'required|in:admin,recruiter'], ['email.exists' => 'Ask your teammate to create a free account first, then add them here.']);
        $seats = $org->activeSubscription()?->plan->recruiter_seats ?? 1;
        if ($org->members()->count() >= $seats) {
            return back()->with('error', "Your plan includes {$seats} recruiter ".Str::plural('seat', $seats).'. Upgrade to add more.');
        }
        $member = User::where('email', $data['email'])->first();
        $member->forceFill(['is_hirer' => true])->save();
        $org->members()->syncWithoutDetaching([$member->id => ['role' => $data['role']]]);

        return back()->with('success', $member->name.' joined your team.');
    }

    public function removeMember(Request $request, User $user)
    {
        $org = $request->user()->organization();
        abort_unless($org && $org->owner_id === $request->user()->id && $user->id !== $org->owner_id, 403);
        $org->members()->detach($user->id);

        return back()->with('success', 'Member removed.');
    }
}
