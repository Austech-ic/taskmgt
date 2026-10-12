<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProfessionalProfile;
use App\Notifications\PlatformNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        return view('app.messages', ['conversations' => $this->list($request), 'active' => null, 'messages' => collect()]);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->member($request, $conversation);
        $conversation->users()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        return view('app.messages', [
            'conversations' => $this->list($request),
            'active' => $conversation->load('users'),
            'messages' => $conversation->messages()->with('user')->get(),
        ]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $this->member($request, $conversation);
        $data = $request->validate(['body' => 'required_without:attachment|nullable|string|max:5000', 'attachment' => 'nullable|file|max:10240|mimes:pdf,png,jpg,jpeg,webp,zip,doc,docx,txt']);

        $message = new Message(['body' => $data['body'] ?? '📎 Attachment']);
        $message->conversation_id = $conversation->id;
        $message->user_id = $request->user()->id;
        if ($request->hasFile('attachment')) {
            $message->attachment_path = $request->file('attachment')->store('messages/'.$conversation->id, 'local');
            $message->attachment_name = $request->file('attachment')->getClientOriginalName();
        }
        $message->save();
        $conversation->update(['last_message_at' => now()]);
        $conversation->users()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        foreach ($conversation->users as $u) {
            if ($u->id !== $request->user()->id && (! $u->last_seen_at || $u->last_seen_at->lt(now()->subMinutes(10)))) {
                // Message text stays out of email — just a nudge back to the app.
                $u->notify(new PlatformNotification('New message from '.$request->user()->firstName(), 'You have a new message on '.setting('site_name').'.', route('app.messages.show', $conversation), 'message-square', true));
            }
        }

        return redirect()->route('app.messages.show', $conversation)->withFragment('end');
    }

    public function attachment(Request $request, Conversation $conversation, Message $message)
    {
        $this->member($request, $conversation);
        abort_unless($message->conversation_id === $conversation->id && $message->attachment_path, 404);

        return Storage::disk('local')->download($message->attachment_path, $message->attachment_name);
    }

    /** Start a chat with a professional — allowed after an unlock, an accepted request, or an existing contract. */
    public function start(Request $request, ProfessionalProfile $profile)
    {
        $user = $request->user();
        $org = $user->organization();
        $allowed = ($org && $org->hasUnlocked($profile))
            || Contract::where('client_id', $user->id)->where('professional_id', $profile->user_id)->exists();
        if (! $allowed) {
            return back()->with('info', 'Send a contact request or unlock their profile to start a chat.');
        }
        $conversation = Conversation::between($user, $profile->user, 'Chat with '.($org?->name ?? $user->name));

        return redirect()->route('app.messages.show', $conversation);
    }

    protected function list(Request $request)
    {
        return $request->user()->conversations()->with(['users', 'latestMessage'])->orderByDesc('last_message_at')->get();
    }

    protected function member(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->users()->where('users.id', $request->user()->id)->exists(), 403);
    }
}
