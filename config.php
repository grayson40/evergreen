<?php

declare(strict_types=1);

/**
 * Load KEY=value pairs from .env into the process environment (getenv / $_ENV).
 * Real environment variables are not overwritten.
 */
function evergreen_load_dotenv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($name === '') {
            continue;
        }
        if (getenv($name) !== false) {
            continue;
        }
        if (strlen($value) >= 2) {
            $q = $value[0];
            if (($q === '"' || $q === "'") && $value[strlen($value) - 1] === $q) {
                $value = substr($value, 1, -1);
            }
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
    }
}

evergreen_load_dotenv(__DIR__ . '/.env');

/**
 * API key: ANTHROPIC_API_KEY from the real environment or from .env (loaded above).
 * Optional config.local.php overrides these values.
 */
$evergreenConfig = [
    'anthropic_api_key' => (string) (getenv('ANTHROPIC_API_KEY') ?: ($_ENV['ANTHROPIC_API_KEY'] ?? '')),
    'anthropic_model' => getenv('ANTHROPIC_MODEL') ?: 'claude-sonnet-4-6',
    'db_path' => __DIR__ . '/database.sqlite',
    'upload_dir' => __DIR__ . '/uploads',
    'max_upload_bytes' => 12 * 1024 * 1024,
    /** Seconds for PHP during AI requests (Claude + optional Gemini). Env: EVERGREEN_API_MAX_SECONDS */
    'api_max_execution_seconds' => max(60, (int) (getenv('EVERGREEN_API_MAX_SECONDS') ?: 300)),
    /** Anthropic output budget; long Markdown in consultation_full needs headroom */
    'consultation_max_tokens' => max(4096, (int) (getenv('EVERGREEN_CONSULTATION_MAX_TOKENS') ?: 8192)),
    'renders_dir' => __DIR__ . '/renders',
    /** Google AI Studio / Gemini API key. Env: GEMINI_API_KEY or GOOGLE_API_KEY */
    'gemini_api_key' => (string) (getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '') ?: getenv('GOOGLE_API_KEY') ?: ($_ENV['GOOGLE_API_KEY'] ?? '')),
    /** @see docs/frontend-rendering.md */
    'gemini_image_model' => getenv('GEMINI_IMAGE_MODEL') ?: 'gemini-3.1-flash-image-preview',
];

if (is_file(__DIR__ . '/config.local.php')) {
    $local = require __DIR__ . '/config.local.php';
    if (is_array($local)) {
        $evergreenConfig = array_merge($evergreenConfig, $local);
    }
}
