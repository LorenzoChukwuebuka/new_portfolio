<section class="section section--compact skills" aria-labelledby="skills-title">
    <div class="container-page">
        <div class="skills__intro" data-reveal>
            <p class="eyebrow">A dependable toolkit</p>
            <h2 id="skills-title">The tools behind the work.</h2>
        </div>
        <div class="skills__grid">
            @foreach (config('portfolio.skills') as $group)
                <article class="skills__group card" data-reveal>
                    <h3>{{ $group['category'] }}</h3>
                    <ul>
                        @foreach ($group['items'] as $skill)
                            <li>{{ $skill }}</li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    </div>
</section>
