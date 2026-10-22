<?php

use App\Support\Content\ProjectDescription;

test('project descriptions become paragraphs headings and lists', function () {
    expect(ProjectDescription::blocks("First line\ncontinued.\n\n## What changed\n- Safer releases\n- Clearer screens\n\nLast note."))
        ->toBe([
            ['type' => 'paragraph', 'text' => 'First line continued.'],
            ['type' => 'heading', 'text' => 'What changed'],
            ['type' => 'list', 'items' => ['Safer releases', 'Clearer screens']],
            ['type' => 'paragraph', 'text' => 'Last note.'],
        ]);
});

test('project descriptions strip markup and return an empty list for empty input', function () {
    expect(ProjectDescription::blocks('<script>alert(1)</script><p>Plain &amp; safe</p>'))
        ->toBe([['type' => 'paragraph', 'text' => 'alert(1)Plain & safe']])
        ->and(ProjectDescription::blocks(null))->toBe([]);
});
