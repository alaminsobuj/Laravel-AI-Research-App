<?php

namespace App\Http\Controllers;

use App\Exceptions\AgentApiException;
use App\Services\ResearchWriterAgents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(): View
    {
        return view('agents.index');
    }

    public function generate(Request $request, ResearchWriterAgents $agents): View|RedirectResponse
    {
        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:500'],
            'provider' => ['sometimes', 'required', 'string', 'in:openai,gemini'],
        ]);

        try {
            return view('agents.index', [
                'topic' => $validated['topic'],
                ...$agents->run($validated['topic'], $validated['provider'] ?? 'openai'),
            ]);
        } catch (AgentApiException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'api' => $exception->getMessage(),
                ]);
        }
    }
}
