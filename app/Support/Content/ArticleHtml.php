<?php

namespace App\Support\Content;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;

final class ArticleHtml
{
    private const ALLOWED_TAGS = [
        'p', 'h1', 'h2', 'h3', 'h4', 'strong', 'em', 'u', 's', 'code', 'pre', 'blockquote',
        'ul', 'ol', 'li', 'a', 'img', 'figure', 'figcaption', 'br', 'hr',
        'table', 'caption', 'colgroup', 'col', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'iframe',
    ];

    private const DROP_WITH_CONTENT = [
        'script', 'style', 'svg', 'math', 'object', 'embed', 'form', 'input', 'button', 'textarea',
        'select', 'option', 'meta', 'link', 'base', 'noscript', 'template', 'canvas', 'video', 'audio',
    ];

    /**
     * Sanitize and normalize HTML saved by Quill 2.
     *
     * @return array{html: string, toc: list<array{id: string, text: string, level: int}>}
     */
    public function render(?string $source): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrorMode = libxml_use_internal_errors(true);
        $markup = '<!DOCTYPE html><html><body><div data-article-root="true">'.($source ?? '').'</div></body></html>';
        $document->loadHTML($markup, LIBXML_NONET | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorMode);

        $root = (new DOMXPath($document))->query('//div[@data-article-root="true"]')?->item(0);
        if (! $root instanceof DOMElement) {
            return ['html' => '', 'toc' => []];
        }

        $this->normalizeQuillLists($root);
        $this->sanitizeChildren($root);

        $toc = [];
        $headingCounts = [];
        $headings = (new DOMXPath($document))->query('.//h2 | .//h3', $root);
        foreach ($headings ?: [] as $heading) {
            if (! $heading instanceof DOMElement) {
                continue;
            }

            $text = trim($heading->textContent);
            if ($text === '') {
                continue;
            }

            $base = Str::slug($text) ?: 'section';
            $headingCounts[$base] = ($headingCounts[$base] ?? 0) + 1;
            $id = $headingCounts[$base] === 1 ? $base : $base.'-'.$headingCounts[$base];
            $heading->setAttribute('id', $id);
            $toc[] = ['id' => $id, 'text' => $text, 'level' => $heading->tagName === 'h2' ? 2 : 3];
        }

        $html = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $html .= $document->saveHTML($child) ?: '';
        }

        return ['html' => $html, 'toc' => $toc];
    }

    public static function readTime(string $html): int
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);

        return max(1, (int) ceil(count($matches[0] ?? []) / 200));
    }

    private function normalizeQuillLists(DOMElement $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $this->normalizeQuillLists($child);
            }
        }

        if (! in_array(strtolower($parent->tagName), ['ol', 'ul'], true)) {
            return;
        }

        $items = array_values(array_filter(
            iterator_to_array($parent->childNodes),
            static fn (DOMNode $child): bool => $child instanceof DOMElement && strtolower($child->tagName) === 'li',
        ));
        $quillList = collect($items)->contains(static fn (DOMElement $item): bool =>
            $item->hasAttribute('data-list') || preg_match('/(?:^|\s)ql-indent-\d+(?:\s|$)/', $item->getAttribute('class')) === 1
        );

        if (! $quillList || $items === []) {
            return;
        }

        $document = $parent->ownerDocument;
        $fragment = $document->createDocumentFragment();
        $stack = [];
        $rootList = null;
        $rootType = null;

        foreach ($items as $sourceItem) {
            $dataList = strtolower($sourceItem->getAttribute('data-list'));
            $listType = in_array($dataList, ['bullet', 'checked', 'unchecked'], true) ? 'ul' : 'ol';
            if (! in_array($dataList, ['bullet', 'ordered', 'checked', 'unchecked'], true)) {
                $listType = strtolower($parent->tagName) === 'ul' ? 'ul' : 'ol';
            }

            preg_match('/(?:^|\s)ql-indent-(\d+)(?:\s|$)/', $sourceItem->getAttribute('class'), $indentMatch);
            $depth = min(8, (int) ($indentMatch[1] ?? 0));
            while ($depth > 0 && ! isset($stack[$depth - 1])) {
                $depth--;
            }

            $item = $document->createElement('li');
            if ($dataList === 'checked') {
                $item->setAttribute('class', 'task-list-item task-list-item--checked');
            } elseif ($dataList === 'unchecked') {
                $item->setAttribute('class', 'task-list-item task-list-item--unchecked');
            }

            foreach (iterator_to_array($sourceItem->childNodes) as $child) {
                if ($child instanceof DOMElement && preg_match('/(?:^|\s)ql-ui(?:\s|$)/', $child->getAttribute('class')) === 1) {
                    continue;
                }
                $item->appendChild($child->cloneNode(true));
            }

            if ($depth === 0) {
                if (! $rootList instanceof DOMElement || $rootType !== $listType) {
                    $rootList = $document->createElement($listType);
                    $fragment->appendChild($rootList);
                    $rootType = $listType;
                    $stack = [];
                }
                $rootList->appendChild($item);
                $stack = [0 => $item];
                continue;
            }

            $parentItem = $stack[$depth - 1] ?? null;
            if (! $parentItem instanceof DOMElement) {
                $depth = 0;
                if (! $rootList instanceof DOMElement || $rootType !== $listType) {
                    $rootList = $document->createElement($listType);
                    $fragment->appendChild($rootList);
                    $rootType = $listType;
                }
                $rootList->appendChild($item);
                $stack = [0 => $item];
                continue;
            }

            $nestedList = $parentItem->lastChild;
            if (! $nestedList instanceof DOMElement || strtolower($nestedList->tagName) !== $listType) {
                $nestedList = $document->createElement($listType);
                $parentItem->appendChild($nestedList);
            }
            $nestedList->appendChild($item);
            $stack[$depth] = $item;
            foreach (array_keys($stack) as $stackDepth) {
                if ($stackDepth > $depth) {
                    unset($stack[$stackDepth]);
                }
            }
        }

        $parent->parentNode?->replaceChild($fragment, $parent);
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                    $parent->removeChild($child);
                }
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($child);
                continue;
            }

            if ($tag === 'iframe') {
                $src = $this->safeIframeUrl($child->getAttribute('src'));
                if ($src === null) {
                    $parent->removeChild($child);
                    continue;
                }
                $this->keepOnlyAttributes($child, ['src', 'title', 'allow', 'allowfullscreen']);
                $child->setAttribute('src', $src);
                $child->setAttribute('title', $child->getAttribute('title') ?: 'Embedded video');
                $child->setAttribute('loading', 'lazy');
                $child->setAttribute('allowfullscreen', '');
                $this->sanitizeChildren($child);
                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                if (preg_match('/(?:^|\s)ql-ui(?:\s|$)/', $child->getAttribute('class')) === 1) {
                    $parent->removeChild($child);
                    continue;
                }
                $this->sanitizeChildren($child);
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);
                continue;
            }

            if ($tag === 'h1') {
                $replacement = $child->ownerDocument->createElement('h2');
                while ($child->firstChild) {
                    $replacement->appendChild($child->firstChild);
                }
                $child->parentNode?->replaceChild($replacement, $child);
                $child = $replacement;
                $tag = 'h2';
            }

            if ($tag === 'pre' && preg_match('/(?:^|\s)ql-syntax(?:\s|$)/', $child->getAttribute('class')) === 1) {
                $code = $child->ownerDocument->createElement('code');
                $code->appendChild($child->ownerDocument->createTextNode($child->textContent));
                while ($child->firstChild) {
                    $child->removeChild($child->firstChild);
                }
                $child->appendChild($code);
            }

            $this->sanitizeElementAttributes($child, $tag);
            $this->sanitizeChildren($child);
        }
    }

    private function sanitizeElementAttributes(DOMElement $element, string $tag): void
    {
        $sourceClass = $element->getAttribute('class');
        $sourceHref = $element->getAttribute('href');
        $sourceSrc = $element->getAttribute('src');
        $sourceTitle = $element->getAttribute('title');
        $sourceAlt = $element->getAttribute('alt');
        $sourceAllow = $element->getAttribute('allow');
        $sourceColspan = $element->getAttribute('colspan');
        $sourceRowspan = $element->getAttribute('rowspan');
        $sourceScope = $element->getAttribute('scope');
        $sourceStart = $element->getAttribute('start');
        $sourceWidth = $element->getAttribute('width');
        $sourceHeight = $element->getAttribute('height');

        $this->keepOnlyAttributes($element, []);

        if ($tag === 'a') {
            $href = $this->safeLinkUrl($sourceHref);
            if ($href !== null) {
                $element->setAttribute('href', $href);
                if ($this->isExternalUrl($href)) {
                    $element->setAttribute('target', '_blank');
                    $element->setAttribute('rel', 'noopener noreferrer');
                }
            }
            if ($sourceTitle !== '') {
                $element->setAttribute('title', $sourceTitle);
            }
        }

        if ($tag === 'img') {
            $src = $this->safeImageUrl($sourceSrc);
            if ($src === null) {
                $element->parentNode?->removeChild($element);
                return;
            }
            $element->setAttribute('src', $src);
            $element->setAttribute('alt', $sourceAlt);
            $element->setAttribute('loading', 'lazy');
            $element->setAttribute('decoding', 'async');
            foreach (['width' => $sourceWidth, 'height' => $sourceHeight] as $attribute => $value) {
                if (preg_match('/^\d{1,5}$/', $value) === 1 && (int) $value > 0) {
                    $element->setAttribute($attribute, $value);
                }
            }
        }

        if (in_array($tag, ['td', 'th'], true)) {
            foreach (['colspan' => $sourceColspan, 'rowspan' => $sourceRowspan] as $attribute => $value) {
                if (preg_match('/^\d{1,3}$/', $value) === 1 && (int) $value > 0) {
                    $element->setAttribute($attribute, $value);
                }
            }
            if ($tag === 'th' && in_array($sourceScope, ['row', 'col', 'rowgroup', 'colgroup'], true)) {
                $element->setAttribute('scope', $sourceScope);
            }
        }

        if ($tag === 'ol' && preg_match('/^\d{1,6}$/', $sourceStart) === 1) {
            $element->setAttribute('start', $sourceStart);
        }

        $classes = $this->safeClasses($tag, $sourceClass);
        if ($classes !== []) {
            $element->setAttribute('class', implode(' ', $classes));
        }

        if ($tag === 'iframe' && $sourceAllow !== '') {
            $element->setAttribute('allow', Str::limit(strip_tags($sourceAllow), 240, ''));
        }
    }

    /** @return list<string> */
    private function safeClasses(string $tag, string $sourceClass): array
    {
        $classes = [];
        if (preg_match('/(?:^|\s)ql-align-center(?:\s|$)/', $sourceClass) === 1) {
            $classes[] = 'text-align-center';
        }
        if (preg_match('/(?:^|\s)ql-align-right(?:\s|$)/', $sourceClass) === 1) {
            $classes[] = 'text-align-right';
        }
        if (preg_match('/(?:^|\s)ql-indent-(\d+)(?:\s|$)/', $sourceClass, $indentMatch) === 1
            && in_array($tag, ['p', 'h2', 'h3', 'h4', 'blockquote'], true)) {
            $classes[] = 'content-indent-'.min(4, (int) $indentMatch[1]);
        }
        if ($tag === 'li') {
            foreach (['task-list-item', 'task-list-item--checked', 'task-list-item--unchecked'] as $class) {
                if (preg_match('/(?:^|\s)'.preg_quote($class, '/').'(?:\s|$)/', $sourceClass) === 1) {
                    $classes[] = $class;
                }
            }
        }
        if ($tag === 'code' && preg_match('/(?:^|\s)language-([a-z0-9+#.-]{1,24})(?:\s|$)/i', $sourceClass, $languageMatch) === 1) {
            $classes[] = 'language-'.strtolower($languageMatch[1]);
        }

        return array_values(array_unique($classes));
    }

    private function keepOnlyAttributes(DOMElement $element, array $allowed): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowed, true)) {
                $element->removeAttribute($attribute->name);
            }
        }
    }

    private function safeLinkUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || preg_match('/[\x00-\x20]/', $url) === 1 || str_starts_with($url, '//')) {
            return null;
        }

        if (str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return $url;
        }

        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $url, $scheme) !== 1) {
            return $url;
        }

        return in_array(strtolower($scheme[1]), ['http', 'https', 'mailto', 'tel'], true) ? $url : null;
    }

    private function safeImageUrl(string $url): ?string
    {
        $url = trim($url);
        if (preg_match('/^data:image\/(png|jpeg|gif|webp);base64,([a-z0-9+\/=]+)$/i', $url, $match) === 1) {
            if (strlen($match[2]) > 7_000_000) {
                return null;
            }
            $bytes = base64_decode($match[2], true);
            $image = $bytes === false ? false : @getimagesizefromstring($bytes);
            if (! $image || ! in_array($image['mime'] ?? '', ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
                return null;
            }

            return $url;
        }

        $parsed = parse_url($url);
        if (! is_array($parsed)) {
            return null;
        }
        if (in_array(strtolower($parsed['scheme'] ?? ''), ['http', 'https'], true) && isset($parsed['host'])) {
            return $url;
        }
        if (! isset($parsed['scheme'], $parsed['host']) && str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        return null;
    }

    private function safeIframeUrl(string $url): ?string
    {
        $url = trim($url);
        $parsed = parse_url($url);
        if (! is_array($parsed) || ! in_array(strtolower($parsed['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parsed['host'] ?? '');
        $allowedHosts = ['youtube.com', 'www.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com'];
        if (! in_array($host, $allowedHosts, true)) {
            return null;
        }

        if (str_contains($host, 'youtube') && ! str_starts_with($parsed['path'] ?? '', '/embed/')) {
            return null;
        }
        if (str_contains($host, 'vimeo') && $host === 'player.vimeo.com' && ! str_starts_with($parsed['path'] ?? '', '/video/')) {
            return null;
        }

        return $url;
    }

    private function isExternalUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        $siteHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return ! is_string($siteHost) || strtolower($siteHost) !== strtolower($host);
    }
}
