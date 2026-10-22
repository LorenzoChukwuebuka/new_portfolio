<?php

namespace Tests\Unit;

use App\Support\Content\ArticleHtml;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticleHtmlTest extends TestCase
{
    #[Test]
    public function it_removes_unsafe_markup_and_links(): void
    {
        $article = app(ArticleHtml::class)->render(<<<'HTML'
            <p onclick="alert(1)">Safe <script>alert(document.cookie)</script><img src="/photo.png" onerror="alert(1)"></p>
            <a href="javascript:alert(1)" style="color:red">bad link</a>
            <iframe src="https://evil.example/embed/123"></iframe>
            <iframe src="https://www.youtube.com/embed/abc" onload="alert(1)"></iframe>
            HTML);

        $this->assertStringContainsString('<p>Safe <img src="/photo.png" alt="" loading="lazy" decoding="async"></p>', $article['html']);
        $this->assertStringContainsString('<img src="/photo.png" alt="" loading="lazy" decoding="async">', $article['html']);
        $this->assertStringContainsString('bad link</a>', $article['html']);
        $this->assertStringNotContainsString('javascript:', $article['html']);
        $this->assertStringNotContainsString('onclick', $article['html']);
        $this->assertStringNotContainsString('onerror', $article['html']);
        $this->assertStringNotContainsString('evil.example', $article['html']);
        $this->assertStringContainsString('https://www.youtube.com/embed/abc', $article['html']);
        $this->assertStringNotContainsString('onload', $article['html']);
    }

    #[Test]
    public function it_normalizes_quill_bullet_ordered_nested_and_task_lists(): void
    {
        $article = app(ArticleHtml::class)->render(<<<'HTML'
            <ol>
                <li data-list="bullet"><span class="ql-ui" contenteditable="false"></span>Bullet one</li>
                <li data-list="bullet" class="ql-indent-1"><span class="ql-ui"></span>Nested bullet</li>
                <li data-list="ordered"><span class="ql-ui"></span>Ordered one</li>
                <li data-list="checked"><span class="ql-ui"></span>Done</li>
                <li data-list="unchecked"><span class="ql-ui"></span>Todo</li>
            </ol>
            HTML);

        $this->assertStringContainsString('<ul><li>Bullet one<ul><li>Nested bullet</li></ul></li></ul>', $article['html']);
        $this->assertStringContainsString('<ol><li>Ordered one</li></ol>', $article['html']);
        $this->assertStringContainsString('task-list-item--checked', $article['html']);
        $this->assertStringContainsString('task-list-item--unchecked', $article['html']);
        $this->assertStringNotContainsString('ql-ui', $article['html']);
    }

    #[Test]
    public function it_demotes_headings_assigns_unique_ids_and_converts_syntax_blocks(): void
    {
        $article = app(ArticleHtml::class)->render(<<<'HTML'
            <h1>Same heading</h1><h2>Same heading</h2><h3 class="ql-align-center">Centered title</h3>
            <pre class="ql-syntax" spellcheck="false">echo "safe";</pre>
            HTML);

        $this->assertStringContainsString('<h2 id="same-heading">Same heading</h2>', $article['html']);
        $this->assertStringContainsString('<h2 id="same-heading-2">Same heading</h2>', $article['html']);
        $this->assertStringContainsString('<h3 class="text-align-center" id="centered-title">Centered title</h3>', $article['html']);
        $this->assertStringContainsString('<pre><code>echo "safe";</code></pre>', $article['html']);
        $this->assertSame([
            ['id' => 'same-heading', 'text' => 'Same heading', 'level' => 2],
            ['id' => 'same-heading-2', 'text' => 'Same heading', 'level' => 2],
            ['id' => 'centered-title', 'text' => 'Centered title', 'level' => 3],
        ], $article['toc']);
    }

    #[Test]
    public function it_handles_empty_and_malformed_html_and_estimates_reading_time(): void
    {
        $service = app(ArticleHtml::class);

        $this->assertSame(['html' => '', 'toc' => []], $service->render(''));
        $this->assertSame('<p><strong>Broken <em>markup</em></strong></p>', $service->render('<p><strong>Broken <em>markup')['html']);
        $this->assertSame(1, ArticleHtml::readTime('<p>Only a few words</p>'));
        $this->assertSame(2, ArticleHtml::readTime('<p>'.str_repeat('word ', 201).'</p>'));
    }

    #[Test]
    public function it_forces_safe_target_attributes_on_external_links(): void
    {
        config(['app.url' => 'https://portfolio.example']);
        $article = app(ArticleHtml::class)->render('<p><a href="https://outside.example/page" target="_self" rel="opener">Read</a></p>');

        $this->assertStringContainsString('target="_blank" rel="noopener noreferrer"', $article['html']);
    }
}
