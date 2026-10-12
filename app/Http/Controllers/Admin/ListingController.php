<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\JobPost;
use App\Models\Project;
use App\Models\Review;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'jobs');

        return view('admin.listings', [
            'tab' => $tab,
            'jobs' => $tab === 'jobs' ? JobPost::with('organization')->withCount('applications')->when($request->q, fn ($q, $t) => $q->where('title', 'like', "%$t%"))->latest()->paginate(25)->withQueryString() : null,
            'projects' => $tab === 'projects' ? Project::with('client')->withCount('proposals')->when($request->q, fn ($q, $t) => $q->where('title', 'like', "%$t%"))->latest()->paginate(25)->withQueryString() : null,
        ]);
    }

    public function status(Request $request, string $type, int $id)
    {
        $model = match ($type) {
            'job' => JobPost::findOrFail($id),
            'project' => Project::findOrFail($id),
            default => abort(404),
        };
        $data = $request->validate(['status' => 'required|in:open,closed,draft,cancelled']);
        $model->update(['status' => $data['status']]);
        AuditLog::record($type.'.moderated', $model, $data);

        return back()->with('success', ucfirst($type).' set to '.$data['status'].'.');
    }

    public function reviews(Request $request)
    {
        return view('admin.reviews', ['reviews' => Review::with(['reviewer', 'reviewee', 'contract'])->when($request->status, fn ($q, $s) => $q->where('status', $s))->latest()->paginate(25)->withQueryString()]);
    }

    public function moderateReview(Request $request, Review $review)
    {
        $data = $request->validate(['status' => 'required|in:published,hidden,flagged']);
        $review->update($data);
        AuditLog::record('review.moderated', $review, $data);

        return back()->with('success', 'Review '.$data['status'].'.');
    }
}
