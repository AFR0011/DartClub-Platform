<?php

function app_sanitize_rich_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $allowedTags = [
        'p', 'br', 'strong', 'em', 'b', 'i', 'ul', 'ol', 'li', 'a',
        'blockquote', 'code', 'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'img',
    ];
    $allowedAttributes = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title'],
    ];

    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument('1.0', 'UTF-8');
    $wrapperId = 'app-rich-html-root';
    $document->loadHTML(
        '<?xml encoding="UTF-8"><div id="' . $wrapperId . '">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = $document->getElementById($wrapperId);
    if (!$root instanceof DOMElement) {
        return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    $sanitizeNode = static function (DOMNode $node) use (&$sanitizeNode, $allowedTags, $allowedAttributes): void {
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            $sanitizeNode($child);
        }

        if (!$node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);
        if (!in_array($tag, $allowedTags, true)) {
            $parent = $node->parentNode;
            if ($parent !== null) {
                while ($node->firstChild !== null) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
            }
            return;
        }

        $allowedForTag = $allowedAttributes[$tag] ?? [];
        $attributeNames = [];
        foreach ($node->attributes as $attribute) {
            $attributeNames[] = $attribute->name;
        }

        foreach ($attributeNames as $attributeName) {
            $normalizedName = strtolower($attributeName);
            if (str_starts_with($normalizedName, 'on') || !in_array($normalizedName, $allowedForTag, true)) {
                $node->removeAttribute($attributeName);
            }
        }

        if ($tag === 'a' && $node->hasAttribute('href')) {
            $href = trim(html_entity_decode($node->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (!app_safe_rich_html_url($href, ['http', 'https', 'mailto'], true)) {
                $node->removeAttribute('href');
            }
        }

        if ($tag === 'img' && $node->hasAttribute('src')) {
            $src = trim(html_entity_decode($node->getAttribute('src'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (!app_safe_rich_html_url($src, ['http', 'https'], true)) {
                $node->removeAttribute('src');
            }
        }

        if ($tag === 'a' && $node->hasAttribute('target')) {
            $target = strtolower(trim($node->getAttribute('target')));
            if (!in_array($target, ['_self', '_blank'], true)) {
                $node->removeAttribute('target');
            } elseif ($target === '_blank') {
                $node->setAttribute('rel', 'noopener noreferrer');
            }
        }
    };

    $children = [];
    foreach ($root->childNodes as $child) {
        $children[] = $child;
    }
    foreach ($children as $child) {
        $sanitizeNode($child);
    }

    $output = '';
    foreach ($root->childNodes as $child) {
        $output .= $document->saveHTML($child);
    }

    return trim($output);
}

function app_safe_rich_html_url(string $value, array $allowedSchemes, bool $allowRelative): bool
{
    $value = trim($value);
    if ($value === '') {
        return false;
    }

    if ($allowRelative && !str_starts_with($value, '//')) {
        $scheme = parse_url($value, PHP_URL_SCHEME);
        if ($scheme === null || $scheme === false || $scheme === '') {
            return true;
        }
    }

    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

    return $scheme !== '' && in_array($scheme, $allowedSchemes, true);
}
