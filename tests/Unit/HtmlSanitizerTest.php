<?php

use App\Support\HtmlSanitizer;

test('simple formatting tags are kept without attributes', function () {
    expect(HtmlSanitizer::clean('<h2 class="x">Título</h2><p style="color:red">Texto <strong id="a">forte</strong> e <em>ênfase</em>.</p><ul><li>Um</li></ul>'))
        ->toBe('<h2>Título</h2><p>Texto <strong>forte</strong> e <em>ênfase</em>.</p><ul><li>Um</li></ul>');
});

test('dangerous content is removed', function (string $html, string $expected) {
    expect(HtmlSanitizer::clean($html))->toBe($expected);
})->with([
    'script with content' => ['<p>Oi</p><script>alert(1)</script>', '<p>Oi</p>'],
    'event handler' => ['<p onclick="alert(1)">Oi</p>', '<p>Oi</p>'],
    'image with onerror' => ['<p>Oi<img src=x onerror=alert(1)></p>', '<p>Oi</p>'],
    'javascript link keeps only the text' => ['<p><a href="javascript:alert(1)">clique</a></p>', '<p>clique</p>'],
    'iframe' => ['<iframe src="https://evil.test"></iframe><p>Oi</p>', '<p>Oi</p>'],
    'style block' => ['<style>body{display:none}</style><p>Oi</p>', '<p>Oi</p>'],
    'svg with script' => ['<svg><script>alert(1)</script></svg><p>Oi</p>', '<p>Oi</p>'],
    'html comment' => ['<p>Oi<!-- segredo --></p>', '<p>Oi</p>'],
    'quote tricks inside attributes' => ['<p title="a>b" onclick="x()">Oi</p>', '<p>Oi</p>'],
]);

test('unknown wrappers are removed but their text stays', function () {
    expect(HtmlSanitizer::clean('<div class="elementor"><span>Piso <b>antiderrapante</b></span></div>'))
        ->toBe('Piso <b>antiderrapante</b>');
});

test('text that looks like html is escaped, not interpreted', function () {
    expect(HtmlSanitizer::clean('<p>1 &lt; 2 &amp; 3 &gt; 2</p>'))->toBe('<p>1 &lt; 2 &amp; 3 &gt; 2</p>');
});

test('accents survive', function () {
    expect(HtmlSanitizer::clean('<p>Proteção, lençóis e vedação</p>'))->toBe('<p>Proteção, lençóis e vedação</p>');
});

test('empty input gives null', function (?string $html) {
    expect(HtmlSanitizer::clean($html))->toBeNull();
})->with([null, '', '   ', '<script>alert(1)</script>']);
