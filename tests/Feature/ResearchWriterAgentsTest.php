<?php

namespace Tests\Feature;

use App\Exceptions\AgentApiException;
use App\Services\ResearchWriterAgents;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\TestCase;

class ResearchWriterAgentsTest extends TestCase
{
    public function test_home_page_shows_the_topic_form(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Researcher + Writer')
            ->assertSee('name="topic"', false)
            ->assertSee('rel="icon" type="image/svg+xml"', false)
            ->assertSee('favicon.svg', false)
            ->assertSee('Run with Gemini')
            ->assertSee('What would you like researched or created?');
    }

    public function test_default_favicon_asset_is_valid_svg(): void
    {
        $faviconPath = public_path('favicon.svg');

        $this->assertFileExists($faviconPath);
        $this->assertStringContainsString('<svg', file_get_contents($faviconPath));
    }

    public function test_it_passes_the_topic_to_the_agents_and_displays_both_outputs(): void
    {
        $this->mock(ResearchWriterAgents::class, function (MockInterface $mock): void {
            $mock->shouldReceive('run')
                ->once()
                ->with('Laravel AI agents', 'openai')
                ->andReturn([
                    'research' => 'Three researched facts.',
                    'result' => 'A response tailored to the request.',
                ]);
        });

        $this->post('/generate', ['topic' => 'Laravel AI agents'])
            ->assertOk()
            ->assertSee('Three researched facts.')
            ->assertSee('A response tailored to the request.');
    }

    public function test_it_uses_gemini_when_the_gemini_button_is_submitted(): void
    {
        $this->mock(ResearchWriterAgents::class, function (MockInterface $mock): void {
            $mock->shouldReceive('run')
                ->once()
                ->with('Laravel AI agents', 'gemini')
                ->andReturn([
                    'research' => 'Gemini researched facts.',
                    'result' => 'A Gemini LinkedIn post.',
                ]);
        });

        $this->post('/generate', [
            'topic' => 'Laravel AI agents',
            'provider' => 'gemini',
        ])
            ->assertOk()
            ->assertSee('Gemini researched facts.')
            ->assertSee('A Gemini LinkedIn post.');
    }

    public function test_topic_is_required(): void
    {
        $this->from('/')
            ->post('/generate', ['topic' => ''])
            ->assertRedirect('/')
            ->assertSessionHasErrors('topic');
    }

    public function test_provider_must_be_openai_or_gemini(): void
    {
        $this->from('/')
            ->post('/generate', [
                'topic' => 'Laravel AI agents',
                'provider' => 'unsupported',
            ])
            ->assertRedirect('/')
            ->assertSessionHasErrors('provider');
    }

    public function test_it_shows_a_helpful_message_when_the_selected_provider_rejects_a_request(): void
    {
        $this->mock(ResearchWriterAgents::class, function (MockInterface $mock): void {
            $mock->shouldReceive('run')
                ->once()
                ->with('Laravel AI agents', 'openai')
                ->andThrow(new AgentApiException('OpenAI rate limit reached.'));
        });

        $this->from('/')
            ->followingRedirects()
            ->post('/generate', ['topic' => 'Laravel AI agents'])
            ->assertOk()
            ->assertSee('OpenAI rate limit reached.')
            ->assertSee('Laravel AI agents');
    }

    public function test_it_generates_both_outputs_through_the_gemini_api(): void
    {
        config([
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-test-model',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['candidates' => [['content' => ['parts' => [['text' => 'Three useful facts.']]]]]])
                ->push(['candidates' => [['content' => ['parts' => [['text' => 'A polished post.']]]]]]),
        ]);

        $topic = 'Research electric cars, then create an Instagram caption about their benefits.';
        $result = app(ResearchWriterAgents::class)->run($topic, 'gemini');

        $this->assertSame('Three useful facts.', $result['research']);
        $this->assertSame('A polished post.', $result['result']);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/models/gemini-test-model:generateContent')
            && $request->hasHeader('x-goog-api-key', 'test-gemini-key'));
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->data()['contents'][0]['parts'][0]['text'],
            'Do not assume LinkedIn or any other platform if the user did not ask for one.',
        ));
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->data()['contents'][0]['parts'][0]['text'],
            $topic,
        ));
    }

    public function test_it_prompts_for_a_gemini_key_without_calling_the_api(): void
    {
        config(['services.gemini.api_key' => null]);
        Http::preventStrayRequests();

        $this->from('/')
            ->followingRedirects()
            ->post('/generate', [
                'topic' => 'Laravel AI agents',
                'provider' => 'gemini',
            ])
            ->assertOk()
            ->assertSee('Set GEMINI_API_KEY in your .env file to use Gemini.');

        Http::assertNothingSent();
    }
}
