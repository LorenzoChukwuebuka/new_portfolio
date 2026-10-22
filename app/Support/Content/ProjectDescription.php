<?php

namespace App\Support\Content;

use Illuminate\Support\Str;

final class ProjectDescription
{
    /**
     * Turn plain-text project notes into escaped paragraph, heading, and list blocks.
     * The view is responsible for escaping each returned text value.
     *
     * @return list<array{type: string, text?: string, items?: list<string>}>
     */
    public static function blocks(?string $description): array
    {
        $plainText = html_entity_decode(strip_tags($description ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lines = preg_split('/\r\n|\r|\n/', trim($plainText)) ?: [];
        $blocks = [];
        $paragraph = [];
        $items = [];

        $flushParagraph = static function () use (&$paragraph, &$blocks): void {
            $text = trim(implode(' ', $paragraph));
            if ($text !== '') {
                $blocks[] = ['type' => 'paragraph', 'text' => $text];
            }
            $paragraph = [];
        };

        $flushList = static function () use (&$items, &$blocks): void {
            if ($items !== []) {
                $blocks[] = ['type' => 'list', 'items' => $items];
            }
            $items = [];
        };

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $flushParagraph();
                $flushList();
                continue;
            }

            if (Str::startsWith($line, '## ')) {
                $flushParagraph();
                $flushList();
                $blocks[] = ['type' => 'heading', 'text' => trim(Str::after($line, '## '))];
                continue;
            }

            if (Str::startsWith($line, '- ')) {
                $flushParagraph();
                $items[] = trim(Str::after($line, '- '));
                continue;
            }

            $flushList();
            $paragraph[] = $line;
        }

        $flushParagraph();
        $flushList();

        return $blocks;
    }
}
