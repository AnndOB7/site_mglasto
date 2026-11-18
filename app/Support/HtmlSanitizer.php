<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Cleans HTML before it is shown in the store (rule 8 of the project).
 *
 * Only simple formatting tags survive, always without attributes. Other tags
 * are unwrapped (their text stays) and dangerous ones are removed with their
 * content. Uses a real HTML parser instead of regular expressions, which are
 * easy to trick.
 */
final class HtmlSanitizer
{
    /** Tags kept, without any attribute. */
    private const ALLOWED = ['p', 'br', 'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'h2', 'h3', 'h4'];

    /** Tags removed together with everything inside them. */
    private const DROPPED = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'svg', 'math', 'template', 'noscript', 'head', 'title', 'meta', 'link'];

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        // The XML declaration makes the parser read the text as UTF-8 (accents).
        $document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return null;
        }

        self::cleanChildren($body);

        $clean = '';

        foreach ($body->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        $clean = trim($clean);

        return $clean !== '' ? $clean : null;
    }

    private static function cleanChildren(DOMNode $node): void
    {
        // Copy the list first: the loop changes the tree.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, self::DROPPED, true)) {
                    $node->removeChild($child);

                    continue;
                }

                self::cleanChildren($child);

                if (in_array($tag, self::ALLOWED, true)) {
                    while ($child->attributes->length > 0) {
                        $attribute = $child->attributes->item(0);

                        if ($attribute !== null) {
                            $child->removeAttribute($attribute->nodeName);
                        }
                    }
                } else {
                    // Unknown tag (div, span, a, img...): keep its content, drop the tag.
                    while ($child->firstChild !== null) {
                        $node->insertBefore($child->firstChild, $child);
                    }

                    $node->removeChild($child);
                }
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
            }
        }
    }
}
