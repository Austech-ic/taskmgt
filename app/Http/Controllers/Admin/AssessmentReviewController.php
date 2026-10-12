<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAttempt;
use App\Services\AssessmentService;
use Illuminate\Http\Request;
use RuntimeException;

class AssessmentReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'under_review');

        return view('admin.attempts.index', [
            'attempts' => AssessmentAttempt::with(['user', 'assessment.skill'])->when($status !== 'all', fn ($q) => $q->where('status', $status))->latest('submitted_at')->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(AssessmentAttempt $attempt)
    {
        return view('admin.attempts.show', [
            'attempt' => $attempt->load(['user.profile', 'assessment.skill', 'reviewer']),
            'questions' => $attempt->questions()->each->makeVisible(['correct', 'explanation']),
        ]);
    }

    public function decide(Request $request, AssessmentAttempt $attempt, AssessmentService $service)
    {
        $data = $request->validate([
            'decision' => 'required|in:pass,fail',
            'level' => 'nullable|integer|between:1,5',
            'notes' => 'required|string|min:10|max:3000',
        ], ['notes.required' => 'Leave feedback for the candidate — it’s how they improve.']);

        try {
            $service->review($attempt, $request->user(), $data['decision'] === 'pass', $data['level'] ?? null, $data['notes']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.attempts.index')->with('success', 'Decision recorded and the candidate notified.');
    }
}
