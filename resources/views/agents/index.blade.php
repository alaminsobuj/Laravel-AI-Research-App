<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Researcher + Writer AI</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; color: #172033; background: #f3f5fa; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 48px 20px; }
        main { width: min(760px, 100%); margin: 0 auto; }
        .eyebrow { color: #5d63d8; font-size: .78rem; font-weight: 750; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 10px 0; font-size: clamp(2rem, 5vw, 3rem); letter-spacing: -.045em; }
        .intro { color: #667085; line-height: 1.65; }
        .panel, .output { margin-top: 26px; padding: 26px; border: 1px solid #e4e7ef; border-radius: 18px; background: white; box-shadow: 0 12px 36px rgb(22 34 64 / 5%); }
        label { display: block; margin-bottom: 9px; font-weight: 650; }
        textarea { width: 100%; min-height: 120px; resize: vertical; padding: 14px; border: 1px solid #d6dae5; border-radius: 10px; font: inherit; }
        textarea:focus { outline: 3px solid #dfe0ff; border-color: #777bf1; }
        button { margin-top: 16px; padding: 12px 19px; border: 0; border-radius: 10px; background: #5358d8; color: white; font: inherit; font-weight: 700; cursor: pointer; }
        button:hover { background: #4247bd; }
        .gemini-button { background: #147d64; }
        .gemini-button:hover { background: #10634f; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .error { margin: 9px 0 0; color: #b42318; font-size: .9rem; }
        .result { margin-top: 24px; }
        .result h2 { margin: 0 0 10px; font-size: 1.05rem; }
        .result p { margin: 0; color: #39445a; line-height: 1.7; white-space: pre-wrap; }
        .step { display: inline-block; margin-right: 7px; color: #5d63d8; font-size: .75rem; font-weight: 750; letter-spacing: .1em; }
        @media (max-width: 520px) { body { padding: 28px 14px; } .panel, .output { padding: 20px; } }
    </style>
</head>
<body>
<main>
    <span class="eyebrow">Two-step AI workflow</span>
    <h1>Researcher + Writer</h1>
    <p class="intro">Describe what you want researched. If you ask for a specific kind of content, the Writer will create it in that format.</p>

    <section class="panel">
        @error('api')
            <p class="error" role="alert">{{ $message }}</p>
        @enderror
        <form method="POST" action="{{ route('agents.generate') }}">
            @csrf
            <label for="topic">What would you like researched or created?</label>
            <textarea id="topic" name="topic" maxlength="500" required placeholder="For example: Research how AI is changing education, then create an Instagram caption about it.">{{ old('topic', $topic ?? '') }}</textarea>
            @error('topic')
                <p class="error">{{ $message }}</p>
            @enderror
            <div class="actions">
                {{-- <button type="submit" name="provider" value="openai">Run with OpenAI</button> --}}
                <button type="submit" name="provider" value="gemini" class="gemini-button">Run with Gemini</button>
            </div>
        </form>
    </section>

    @isset($research)
        <section class="output" aria-live="polite">
            <div class="result">
                <h2><span class="step">AGENT 1</span> Researcher: core facts</h2>
                <p>{{ $research }}</p>
            </div>
            <div class="result">
                <h2><span class="step">AGENT 2</span> Response to your request</h2>
                <p>{{ $result }}</p>
            </div>
        </section>
    @endisset
</main>
</body>
</html>
