<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\Ai\AiUnavailable;
use App\Services\Ai\Copilot;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function inbox(Request $request)
    {
        return view('admin.inbox', ['messages' => ContactMessage::when($request->status, fn ($q, $s) => $q->where('status', $s))->latest()->paginate(25)->withQueryString()]);
    }

    public function inboxStatus(Request $request, ContactMessage $message)
    {
        $message->update($request->validate(['status' => 'required|in:new,replied,closed']));

        return back()->with('success', 'Updated.');
    }

    public function aiBlog(Request $request, Copilot $copilot)
    {
        $data = $request->validate(['topic' => 'required|string|max:200', 'audience' => 'required|string|max:100']);
        try {
            return response()->json(['result' => $copilot->blogDraft($data['topic'], $data['audience'])]);
        } catch (AiUnavailable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
