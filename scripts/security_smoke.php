<?php

require_once __DIR__ . '/../services/shared/html_sanitizer.php';

$cases = [
    [
        'name' => 'removes event handlers',
        'input' => '<p onclick=alert(1)>Hello <strong>world</strong></p>',
        'forbidden' => ['onclick', 'alert(1)'],
        'required' => ['<p>', '<strong>world</strong>'],
    ],
    [
        'name' => 'removes javascript links',
        'input' => '<a href="javascript:alert(1)" target="_blank">click</a>',
        'forbidden' => ['javascript:', 'href='],
        'required' => ['target="_blank"', 'rel="noopener noreferrer"'],
    ],
    [
        'name' => 'preserves safe https links',
        'input' => '<a href="https://example.com/path" target="_blank">safe</a>',
        'forbidden' => [],
        'required' => ['href="https://example.com/path"', 'rel="noopener noreferrer"'],
    ],
    [
        'name' => 'removes style attributes',
        'input' => '<p style="background:url(javascript:alert(1))">text</p>',
        'forbidden' => ['style=', 'javascript:'],
        'required' => ['<p>text</p>'],
    ],
    [
        'name' => 'unwraps forbidden tags',
        'input' => '<script>alert(1)</script><p>kept</p>',
        'forbidden' => ['<script', '</script>'],
        'required' => ['<p>kept</p>'],
    ],
    [
        'name' => 'preserves utf-8 text',
        'input' => '<p>Kulüp üyeliği — güvenli içerik</p>',
        'forbidden' => [],
        'required' => ['Kulüp üyeliği — güvenli içerik'],
    ],
];

$failed = false;
foreach ($cases as $case) {
    $output = app_sanitize_rich_html($case['input']);
    foreach ($case['forbidden'] as $needle) {
        if (stripos($output, $needle) !== false) {
            fwrite(STDERR, "FAIL {$case['name']}: forbidden fragment remained: {$needle}\nOutput: {$output}\n");
            $failed = true;
        }
    }
    foreach ($case['required'] as $needle) {
        if (stripos($output, $needle) === false) {
            fwrite(STDERR, "FAIL {$case['name']}: required fragment missing: {$needle}\nOutput: {$output}\n");
            $failed = true;
        }
    }
}

if ($failed) {
    exit(1);
}

echo "security smoke ok\n";
