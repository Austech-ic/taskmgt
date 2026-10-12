<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Models\AiLog;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Thin wrapper over the official Anthropic PHP SDK.
 *
 * Every Devhail AI feature goes through ask() / askJson(). When AI is switched off
 * in Admin → Settings, or no API key is configured, callers get an AiUnavailable
 * exception and fall back to non-AI behaviour.
 */
class AiService
{
    protected const VOICE = <<<'TXT'
You write for Devhail, a Nigerian-born platform where tech professionals prove their skills through assessments and get hired or paid by milestone.
House voice: confident, warm, clear, a little playful — millennial/Gen Z energy without cringe. Plain English, short sentences, no corporate filler, no hashtags unless asked, at most one emoji.
Never invent facts about a person (employers, years, certifications, results). If something isn't provided, leave it out or use a clearly marked [placeholder].
TXT;

    public function enabled(): bool
    {
        return (bool) setting('ai_enabled') && filled(setting('ai_api_key'));
    }

    public function ask(string $feature, string $instructions, string $input, int $maxTokens = 2000): string
    {
        $response = $this->call($feature, $instructions, $input, $maxTokens);

        $text = '';
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return trim($text);
    }

    /** Ask for JSON matching a schema via structured outputs. */
    public function askJson(string $feature, string $instructions, string $input, array $schema, int $maxTokens = 2000): array
    {
        $response = $this->call($feature, $instructions, $input, $maxTokens, $schema);
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $data = json_decode($block->text, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }
        throw new AiUnavailable('The AI response could not be read. Try again.');
    }

    protected function call(string $feature, string $instructions, string $input, int $maxTokens, ?array $schema = null)
    {
        if (! $this->enabled()) {
            throw new AiUnavailable('AI features are switched off on this Devhail install.');
        }
        $this->throttle($feature);

        $model = (string) setting('ai_model', 'claude-opus-5-5');
        $outputConfig = ['effort' => (string) setting('ai_effort', 'low')];
        if ($schema) {
            $outputConfig['format'] = ['type' => 'json_schema', 'schema' => $schema];
        }

        try {
            $client = new Client(apiKey: (string) setting('ai_api_key'));
            // Server-side fallbacks: if a request is declined by safety classifiers,
            // the API re-runs it on an appropriate fallback model within the same call.
            $response = $client->beta->messages->create(
                maxTokens: $maxTokens,
                messages: [['role' => 'user', 'content' => $input]],
                model: $model,
                system: self::VOICE."\n\n".$instructions,
                outputConfig: $outputConfig,
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
                requestOptions: ['timeout' => 45.0, 'maxRetries' => 1],
            );
        } catch (AuthenticationException) {
            $this->log($feature, $model, 0, 0, 'auth_error');
            throw new AiUnavailable('The AI API key was rejected. An admin needs to update it in Settings → AI.');
        } catch (RateLimitException) {
            $this->log($feature, $model, 0, 0, 'rate_limited');
            throw new AiUnavailable('The AI is busy right now. Give it a minute and try again.');
        } catch (APIStatusException $e) {
            report($e);
            $this->log($feature, $model, 0, 0, 'api_error');
            throw new AiUnavailable('The AI service returned an error. Try again shortly.');
        } catch (APIConnectionException $e) {
            report($e);
            $this->log($feature, $model, 0, 0, 'network');
            throw new AiUnavailable('Couldn’t reach the AI service from this server.');
        } catch (Throwable $e) {
            report($e);
            $this->log($feature, $model, 0, 0, 'error');
            throw new AiUnavailable('Something went wrong talking to the AI.');
        }

        $this->log($feature, $model, $response->usage->inputTokens ?? 0, $response->usage->outputTokens ?? 0, (string) $response->stopReason);

        if ($response->stopReason === 'refusal') {
            throw new AiUnavailable('The AI declined this request. Try rephrasing it.');
        }

        return $response;
    }

    protected function throttle(string $feature): void
    {
        $user = auth()->user();
        if (! $user || $user->isStaff()) {
            return;
        }
        $key = 'ai:'.$user->id.':'.now()->toDateString();
        $count = Cache::increment($key);
        if ($count === 1) {
            Cache::put($key, 1, now()->endOfDay());
        }
        if ($count > (int) setting('ai_daily_limit', 30)) {
            throw new AiUnavailable('You’ve hit today’s AI limit. It resets at midnight ✨');
        }
    }

    protected function log(string $feature, string $model, int $in, int $out, string $status): void
    {
        try {
            AiLog::create(['user_id' => auth()->id(), 'feature' => $feature, 'model' => $model, 'input_tokens' => $in, 'output_tokens' => $out, 'status' => substr($status, 0, 20)]);
        } catch (Throwable) {
            // logging must never break the feature
        }
    }
}
