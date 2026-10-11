<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

class Markdown
{
    public static function toHtml(?string $markdown): string
    {
        $markdown = trim((string) $markdown);

        if ($markdown === '') {
            return '';
        }

        $html = (string) Str::markdown($markdown, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        return self::sanitizeHtml($html);
    }

    public static function toPlainText(?string $markdown): string
    {
        $html = self::toHtml($markdown);

        if ($html === '') {
            return '';
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private static function sanitizeHtml(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<div id="markdown-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        $root = $document->getElementById('markdown-root');

        if (! $root instanceof DOMElement) {
            return '';
        }

        self::sanitizeNode($root);

        $output = '';

        foreach (iterator_to_array($root->childNodes) as $childNode) {
            $output .= $document->saveHTML($childNode);
        }

        return trim($output);
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        $allowedTags = [
            'p',
            'br',
            'strong',
            'em',
            'u',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'a',
            'ul',
            'ol',
            'li',
            'blockquote',
            'code',
            'pre',
            'hr',
            'span',
            'del',
            'sup',
            'sub',
        ];

        $stripCompletely = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'svg', 'canvas'];

        foreach (iterator_to_array($node->childNodes) as $childNode) {
            if (! $childNode instanceof DOMElement) {
                continue;
            }

            $tagName = strtolower($childNode->tagName);

            if (in_array($tagName, $stripCompletely, true)) {
                $node->removeChild($childNode);
                continue;
            }

            if (! in_array($tagName, $allowedTags, true)) {
                self::unwrapNode($node, $childNode);
                continue;
            }

            self::sanitizeAttributes($childNode);
            self::sanitizeNode($childNode);
        }
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        $tagName = strtolower($element->tagName);
        $allowedAttributes = match ($tagName) {
            'a' => ['href', 'title', 'rel', 'target'],
            'td', 'th' => ['colspan', 'rowspan'],
            default => [],
        };

        for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
            $attribute = $element->attributes->item($index);

            if (! $attribute) {
                continue;
            }

            $attributeName = strtolower($attribute->name);

            if (str_starts_with($attributeName, 'on') || $attributeName === 'style') {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if (! in_array($attributeName, $allowedAttributes, true)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if ($tagName === 'a' && $attributeName === 'href' && ! self::isSafeUrl($attribute->value)) {
                $element->removeAttribute('href');
            }
        }

        if ($tagName === 'a' && $element->hasAttribute('href')) {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        return (bool) preg_match('~^(https?:|mailto:|tel:|/|#|//)~i', $url);
    }

    private static function unwrapNode(DOMNode $parent, DOMNode $child): void
    {
        while ($child->firstChild) {
            $parent->insertBefore($child->firstChild, $child);
        }

        $parent->removeChild($child);
    }
}