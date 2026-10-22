<?php

namespace App\Services;

use App\Exceptions\AgentApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use OpenAI;
use OpenAI\Client;
use OpenAI\Exceptions\RateLimitException;

class ResearchWriterAgents
{
    /**
     * @return array{research: string, result: string}
     */
    public function run(string $topic, string $provider = 'openai'): array
    {
        return match ($provider) {
            'openai' => $this->runWithOpenAi($topic),
            'gemini' => $this->runWithGemini($topic),
            default => throw new \InvalidArgumentException('Unsupported AI provider.'),
        };
    }

    /**
     * @return array{research: string, result: string}
     */
    private function runWithOpenAi(string $topic): array
    {
        $apiKey = config('services.openai.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new AgentApiException('Set OPENAI_API_KEY in your .env file to use OpenAI.');
        }

        $client = OpenAI::client($apiKey);
        $model = config('services.openai.model', 'gpt-4o-mini');

        if (! is_string($model) || trim($model) === '') {
            throw new AgentApiException('OPENAI_MODEL must be a non-empty string.');
        }

        try {
            $research = $this->completeWithOpenAi($client, $model, sprintf(
                'You are an expert researcher. Research the subject and requirements in this user request. Find 3 accurate, useful, relevant facts. Do not draft the final deliverable yet. User request: %s',
                $topic,
            ));

            $result = $this->completeWithOpenAi($client, $model, sprintf(
                "You are a helpful assistant. Based only on the researched facts, fulfill the user's request below. If the user explicitly asks for a deliverable in a particular style or platform format, create it in that requested format. Do not assume LinkedIn or any other platform if the user did not ask for one. If the user only asks for research or an explanation, give a clear, useful answer without inventing a different deliverable.\n\nUser request: %s\n\nResearched facts:\n%s",
                $topic,
                $research,
            ));
        } catch (RateLimitException) {
            throw new AgentApiException('OpenAI rate limit reached. Check your API usage and limits, then try again later.');
        }

        return [
            'research' => $research,
            'result' => $result,
        ];
    }

    /**
     * @return array{research: string, result: string}
     */
    private function runWithGemini(string $topic): array
    {
        $apiKey = config('services.gemini.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new AgentApiException('Set GEMINI_API_KEY in your .env file to use Gemini.');
        }

        $model = config('services.gemini.model', 'gemini-2.5-flash');

        if (! is_string($model) || trim($model) === '') {
            throw new AgentApiException('GEMINI_MODEL must be a non-empty string.');
        }

        $research = $this->completeWithGemini($apiKey, $model, sprintf(
            'You are an expert researcher. Research the subject and requirements in this user request. Find 3 accurate, useful, relevant facts. Do not draft the final deliverable yet. User request: %s',
            $topic,
        ));

        $result = $this->completeWithGemini($apiKey, $model, sprintf(
            "You are a helpful assistant. Based only on the researched facts, fulfill the user's request below. If the user explicitly asks for a deliverable in a particular style or platform format, create it in that requested format. Do not assume LinkedIn or any other platform if the user did not ask for one. If the user only asks for research or an explanation, give a clear, useful answer without inventing a different deliverable.\n\nUser request: %s\n\nResearched facts:\n%s",
            $topic,
            $research,
        ));

        return [
            'research' => $research,
            'result' => $result,
        ];
    }

    private function completeWithOpenAi(Client $client, string $model, string $prompt): string
    {
        $response = $client->chat()->create([
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $content = $response->choices[0]->message->content;

        if (! is_string($content) || trim($content) === '') {
            throw new AgentApiException('OpenAI returned an empty response.');
        }

        return trim($content);
    }

    private function completeWithGemini(string $apiKey, string $model, string $prompt): string
    {
        try {
            $response = Http::acceptJson()
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->timeout(60)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                ]);
        } catch (ConnectionException $exception) {
            throw new AgentApiException('Could not connect to the Gemini API. Check your internet connection and try again.', previous: $exception);
        }

        if (! $response->successful()) {
            $message = $response->status() === 429
                ? 'Gemini API quota or rate limit reached. Check your Google AI Studio usage and try again later.'
                : sprintf('Gemini API request failed with HTTP %d. Check your API key, model, and API access.', $response->status());

            throw new AgentApiException($message);
        }

        $parts = $response->json('candidates.0.content.parts', []);
        $text = collect($parts)
            ->filter(fn (mixed $part): bool => is_array($part) && ! ($part['thought'] ?? false))
            ->pluck('text')
            ->filter(fn (mixed $part): bool => is_string($part) && trim($part) !== '')
            ->implode("\n");

        if ($text === '') {
            throw new AgentApiException('Gemini returned an empty response. Try a different topic or check the model response settings.');
        }

        return trim($text);
    }
}
