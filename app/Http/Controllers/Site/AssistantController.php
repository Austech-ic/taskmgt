<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\Ai\AiUnavailable;
use App\Services\Ai\Copilot;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function ask(Request $request, Copilot $copilot)
    {
        $data = $request->validate([
            'question' => 'required|string|max:500',
            'history' => 'nullable|array|max:8',
            'history.*.role' => 'nullable|string',
            'history.*.content' => 'nullable|string|max:2000',
        ]);

        try {
            return response()->json(['answer' => $copilot->assistant($data['question'], $data['history'] ?? [])]);
        } catch (AiUnavailable) {
            return response()->json(['answer' => $this->fallback($data['question'])]);
        }
    }

    /** Keyword match against FAQs when AI is off. */
    protected function fallback(string $question): string
    {
        $words = collect(preg_split('/\W+/', mb_strtolower($question)))->filter(fn ($w) => mb_strlen($w) > 3);
        $best = Faq::where('is_published', true)->get()
            ->map(fn ($f) => [$f, $words->filter(fn ($w) => str_contains(mb_strtolower($f->question.' '.$f->answer), $w))->count()])
            ->sortByDesc(1)->first();

        if ($best && $best[1] > 0) {
            return $best[0]->answer;
        }

        return 'Good question! I couldn’t find that in our help docs. Check the FAQ page or drop us a line on the contact page and a human will help 🙌';
    }
}
