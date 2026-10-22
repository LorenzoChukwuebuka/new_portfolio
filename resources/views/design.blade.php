@extends('layouts.app')

@section('title', 'Design system | '.config('portfolio.name'))
@section('meta_description', 'Local design token and component preview.')
@section('robots', 'noindex, nofollow')

@section('content')
<main id="main" class="container-page section space-y-16">
    <header class="max-w-3xl space-y-4">
        <p class="eyebrow">Design system preview</p>
        <h1 class="font-display text-display text-balance">A considered set of parts.</h1>
        <p class="max-w-[65ch] text-lg text-ink-muted">This local-only page shows the public tokens and reusable components in context. The same colors, type scale, spacing and focus styles drive the public pages.</p>
        <div class="flex flex-wrap gap-3">
            <a class="btn btn-primary" href="#components">Primary action</a>
            <a class="btn btn-secondary" href="#components">Secondary action</a>
            <a class="btn btn-ghost" href="#prose">Quiet action</a>
        </div>
    </header>

    <section aria-labelledby="swatches-heading" class="space-y-5">
        <h2 id="swatches-heading" class="font-display text-3xl">Color and surface</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['Canvas', 'bg-canvas'], ['Surface', 'bg-surface'], ['Raised', 'bg-surface-raised'], ['Accent', 'bg-accent text-accent-ink']] as [$label, $class])
                <div class="card {{ $class }} p-5"><p class="eyebrow">{{ $label }}</p><p class="mt-8 font-mono text-xs">Semantic token</p></div>
            @endforeach
        </div>
    </section>

    <section id="components" aria-labelledby="components-heading" class="space-y-5">
        <h2 id="components-heading" class="font-display text-3xl">Components</h2>
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card space-y-5 p-6">
                <p class="eyebrow">Actions and labels</p>
                <div class="flex flex-wrap gap-3"><button class="btn btn-primary">Primary</button><button class="btn btn-secondary">Secondary</button><button class="btn btn-ghost">Ghost</button><button class="btn btn-danger">Danger</button></div>
                <div class="flex flex-wrap gap-2"><span class="chip">Editorial</span><span class="chip">Laravel</span><span class="chip">Accessibility</span></div>
            </div>
            <div class="card space-y-4 p-6">
                <p class="eyebrow">Fields and feedback</p>
                <label class="field"><span class="field-label">Email address</span><input class="field-input" type="email" placeholder="you@example.com"><span class="field-hint">A short, useful hint lives here.</span></label>
                <label class="field"><span class="field-label">Message</span><textarea class="field-input" rows="3" placeholder="Write a note"></textarea><span class="field-error">Example validation message.</span></label>
                <div class="flex flex-wrap gap-2"><span class="chip chip-success">Published</span><span class="chip chip-danger">Needs attention</span></div>
            </div>
        </div>
    </section>

    <section id="prose" aria-labelledby="prose-heading" class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <article class="prose-article card p-6 sm:p-9">
            <p class="eyebrow">Article typography</p>
            <h2 id="prose-heading">Long-form reading, with room to breathe</h2>
            <p>Body text uses the self-hosted sans face and a reading measure that stays comfortable on wide screens. A paragraph should feel calm at every viewport width, with enough line height to keep its rhythm.</p>
            <h3>Useful structure</h3>
            <p>Use headings to give a reader a map. Links stay visibly underlined and keyboard focus remains visible against each surface.</p>
            <ul><li>Lists keep their markers and indent</li><li>Code stays in a legible monospace face</li></ul>
            <blockquote><p>Good interface writing gets out of the reader’s way.</p></blockquote>
            <pre><code>const answer = 'clear and useful';</code></pre>
        </article>
        <aside class="card h-fit space-y-3 p-5">
            <p class="eyebrow">Token rules</p>
            <p class="text-sm text-ink-muted">Use semantic utilities such as <code>bg-surface</code> and <code>text-ink-muted</code>. Keep primitives inside the token layer.</p>
            <p class="text-xs text-ink-muted">See <code>docs/DESIGN_SYSTEM.md</code> in the repository for token and component rules.</p>
        </aside>
    </section>
</main>
@endsection
