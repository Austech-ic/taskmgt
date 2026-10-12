<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('app.notifications', ['notifications' => $request->user()->notifications()->paginate(25)]);
    }

    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        $url = $notification->data['url'] ?? null;

        // Only follow links back into this site.
        return redirect()->to($url && str_starts_with($url, url('/')) ? $url : route('app.notifications'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All caught up ✨');
    }
}
