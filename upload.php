<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require __DIR__ . '/config.php';
require __DIR__ . '/db.php';

/** @var array $evergreenConfig */
$apiKey = (string) ($evergreenConfig['anthropic_api_key'] ?? '');
$model = (string) ($evergreenConfig['anthropic_model'] ?? 'claude-sonnet-4-6');
$uploadDir = (string) ($evergreenConfig['upload_dir'] ?? __DIR__ . '/uploads');
$rendersDir = (string) ($evergreenConfig['renders_dir'] ?? __DIR__ . '/renders');
$maxBytes = (int) ($evergreenConfig['max_upload_bytes'] ?? 12 * 1024 * 1024);
$dbPath = (string) ($evergreenConfig['db_path'] ?? __DIR__ . '/database.sqlite');

if ($apiKey === '') {
    error_log('evergreen: consultation API key not configured');
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'Consultations are temporarily unavailable. Please try again later.']);
    exit;
}

$location = trim((string) ($_POST['location'] ?? ''));
$location = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $location) ?? '';
if (mb_strlen($location) > 280) {
    $location = mb_substr($location, 0, 280);
}
if ($location === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Add where you live (city, region, or ZIP) so we can suggest suitable plants.']);
    exit;
}

$plantGoal = (string) ($_POST['plant_goal'] ?? 'looks');
if ($plantGoal === 'food') {
    $plantGoal = 'food_forest';
}
if (!in_array($plantGoal, ['looks', 'kitchen', 'food_forest', 'mixed'], true)) {
    $plantGoal = 'looks';
}

$edibleStyle = trim((string) ($_POST['edible_style'] ?? ''));
if ($plantGoal === 'mixed') {
    if (!in_array($edibleStyle, ['kitchen', 'food_forest'], true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'For a balanced plan, choose whether the edible side leans kitchen garden or food forest.']);
        exit;
    }
}

$guidanceLooks = 'Prioritize looks maxing: pure aesthetics, curb appeal, and beauty. Visualize the yard transformed by rich color, soft textures, and traditional garden architecture—a relaxing ornamental retreat and pollinator magnet using plants that thrive in the region. Do not emphasize vegetable rows or a production food system.';
$guidanceKitchen = 'Prioritize a kitchen garden: a highly functional, organized edible space emphasizing companion planting—herbs, flowers, and vegetables grown closely together to deter pests and maximize harvest of everyday essentials suited to the region. Plan for clear beds, succession where helpful, and practical access while keeping the layout attractive.';
$guidanceFoodForest = 'Prioritize a food forest: visualize a self-sustaining, multi-layered ecosystem with layers of abundance—turning the open yard into a productive mini-jungle that maximizes vertical and horizontal space using species suited to the region. Use a mulch-forward soil foundation (wood chips / organic mulch). Stack edible canopy (taller fruit or nut trees), understory (berries, fruiting shrubs, perennial vegetables), and ground layer (edible groundcovers, herbs, spreading fruits); pair major fruit trees with nitrogen-fixing companions (e.g. clover, goumi, sea buckthorn, Siberian pea shrub, or region-appropriate fixers) and explain pairings. Fill available space without overcrowding; keep paths and building clearance realistic.';

$goalGuidance = match ($plantGoal) {
    'looks' => $guidanceLooks,
    'kitchen' => $guidanceKitchen,
    'food_forest' => $guidanceFoodForest,
    default => '',
};
$focusLabel = match ($plantGoal) {
    'looks' => 'Looks maxing',
    'kitchen' => 'Kitchen garden',
    'food_forest' => 'Food forest',
    default => '',
};

if ($plantGoal === 'mixed') {
    $sub = $edibleStyle === 'food_forest' ? $guidanceFoodForest : $guidanceKitchen;
    $goalGuidance = 'Balance strong ornamental appeal (color, texture, curb appeal, pollinator-friendly flowers, relaxing retreat) with meaningful edible plantings. For the edible portion of the plan, apply this approach: ' . $sub;
    $focusLabel = $edibleStyle === 'food_forest' ? 'Balanced · food forest' : 'Balanced · kitchen garden';
}

if (!isset($_FILES['photo']) || !is_array($_FILES['photo'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['photo'];
$uploadErr = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($uploadErr !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $userMsg = match ($uploadErr) {
        UPLOAD_ERR_INI_SIZE => 'This photo is larger than your PHP upload limit (upload_max_filesize—often 2M by default). Use a smaller or more compressed image, or raise upload_max_filesize and post_max_size in php.ini. This repo includes php-uploads.ini; for the built-in server run: php -c php-uploads.ini -S localhost:8080',
        UPLOAD_ERR_FORM_SIZE => 'This photo is larger than the form allows.',
        UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'No photo was received. If the file is large, your server post_max_size may be too small.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temp folder for uploads.',
        UPLOAD_ERR_CANT_WRITE => 'Server could not write the uploaded file.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension blocked this upload.',
        default => 'Upload failed.',
    };
    echo json_encode(['ok' => false, 'error' => $userMsg]);
    exit;
}

if (($file['size'] ?? 0) > $maxBytes) {
    http_response_code(400);
    $mb = (int) round($maxBytes / 1048576);
    echo json_encode(['ok' => false, 'error' => "Please use an image under {$mb} MB."]);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']) ?: '';
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];
if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid image type. Use JPEG, PNG, WebP, or GIF.']);
    exit;
}

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not create upload directory.']);
    exit;
}

$ext = $allowed[$mime];
$stored = bin2hex(random_bytes(16)) . '.' . $ext;
$dest = $uploadDir . '/' . $stored;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save file.']);
    exit;
}

$binary = file_get_contents($dest);
if ($binary === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not read saved file.']);
    exit;
}

[$binary, $mime, $orientChanged] = evergreen_orient_pixels_to_display($binary, $mime, $dest);
if ($orientChanged) {
    if (file_put_contents($dest, $binary) === false) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Could not save oriented image.']);
        exit;
    }
}

$pdo = evergreen_db($dbPath);
$createdAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c');
$originalName = isset($file['name']) ? (string) $file['name'] : '';
$sizeBytes = strlen($binary);

$logStmt = $pdo->prepare(
    'INSERT INTO uploads (stored_filename, original_filename, mime, size_bytes, created_at, api_success, zones_count, error_message)
     VALUES (:sf, :of, :mime, :sz, :ca, 0, 0, NULL)'
);
$logStmt->execute([
    ':sf' => $stored,
    ':of' => $originalName,
    ':mime' => $mime,
    ':sz' => $sizeBytes,
    ':ca' => $createdAt,
]);
$uploadId = (int) $pdo->lastInsertId();

[$visionBinary, $visionMime] = evergreen_reduce_image_for_vision($binary, $mime);
if ($visionBinary === '') {
    evergreen_mark_upload_failed($pdo, $uploadId, 'Image exceeds API size limit and could not be resized.');
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'This photo is too large for the vision service. Use a smaller or more compressed image, or enable PHP GD on the server so we can resize it automatically.',
        'image' => 'uploads/' . $stored,
    ]);
    exit;
}

$base64 = base64_encode($visionBinary);
$safeLocation = str_replace(["\r", "\n", "\t"], ' ', $location);

$prompt = <<<PROMPT
## Homeowner context (use for every recommendation)
- **Where they live:** {$safeLocation}
- **What they want:** {$goalGuidance}

Infer regional climate and hardiness from the location when possible. If the location is broad, briefly state assumptions in consultation_full. Match plant choices to their priority (ornamental vs edible vs mixed).

You are a professional landscape consultant. Analyze the uploaded yard/garden photo in detail.

1. Zones: Map sunlight vs shade, structures, lawn, bare soil, paths, water. Rectangles in normalized 0-100 coords: (0,0)=top-left, (100,100)=bottom-right; zone x,y = top-left; width,height same scale.
2. Plants: Recommend 3-6 specific plants suited to their location and goals. Each needs x,y center (0-100) on the photo for mapping (beds/borders, not on buildings).
3. Care: Each plant gets a "care" object: sun, water, soil, maintenance, spacing, seasonal, pests_tips (strings).
4. consultation_summary: 2-4 sentence overview referencing their place and goals where relevant.
5. placement_paragraph: ONE dense paragraph for a photorealistic image editor. Describe exactly where each plant goes in the real scene (e.g. along the left fence, front-right bed). Include approximate scale and grouping. No JSON inside this string.
6. consultation_full: Full written consultation in Markdown: start with a short "Your situation" line (location + goals), then overview, then each plant (## Name) with why it fits, placement, and care.

Respond with ONLY valid JSON (no markdown fences, no commentary), exactly in this shape:
PROMPT;

$prompt .= <<<'PROMPT'
{
  "consultation_summary": "Short overview.",
  "placement_paragraph": "Single paragraph of placement instructions for an image model.",
  "consultation_full": "# Consultation\n\n…full markdown…",
  "zones": [{"type": "sun", "x": 20, "y": 30, "width": 40, "height": 20}],
  "suggestions": [
    {
      "plant": "Lavender",
      "location": "Back left sunny bed",
      "reason": "Drought tolerant",
      "x": 28,
      "y": 45,
      "care": {
        "sun": "Full sun",
        "water": "Low when established",
        "soil": "Well-drained",
        "maintenance": "Shear after bloom",
        "spacing": "18-24 in",
        "seasonal": "Mulch in cold winters",
        "pests_tips": "Avoid wet feet"
      }
    }
  ]
}

Zone "type" must be one of: sun, shade, mixed, lawn, soil, structure, path, water.

Critical: Reply with exactly one JSON object only—no markdown fences, no text before or after. Inside string values escape any " as \". If consultation_full is long, keep it as one JSON string (use \n for newlines inside the string).
PROMPT;

$body = [
    'model' => $model,
    'max_tokens' => (int) ($evergreenConfig['consultation_max_tokens'] ?? 8192),
    'messages' => [
        [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'image',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => $visionMime,
                        'data' => $base64,
                    ],
                ],
                [
                    'type' => 'text',
                    'text' => $prompt,
                ],
            ],
        ],
    ],
];

// Default max_execution_time (often 30s) is shorter than a vision API call; raise it for this request.
$aiDeadlineSeconds = (int) ($evergreenConfig['api_max_execution_seconds'] ?? 180);
if ($aiDeadlineSeconds < 60) {
    $aiDeadlineSeconds = 180;
}
@ini_set('max_execution_time', (string) $aiDeadlineSeconds);
if (function_exists('set_time_limit')) {
    set_time_limit($aiDeadlineSeconds);
}

$ch = curl_init('https://api.anthropic.com/v1/messages');
$curlTimeout = max(90, min(360, $aiDeadlineSeconds - 5));

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_POSTFIELDS => json_encode($body, JSON_THROW_ON_ERROR),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_TIMEOUT => $curlTimeout,
]);

$response = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false || $curlErr !== '') {
    evergreen_mark_upload_failed($pdo, $uploadId, $curlErr ?: 'Network error calling API.');
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'We couldn’t reach the consultation service. Please try again.', 'image' => 'uploads/' . $stored]);
    exit;
}

$decoded = json_decode($response, true);
if (!is_array($decoded)) {
    evergreen_mark_upload_failed($pdo, $uploadId, 'Invalid API response.');
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Something went wrong while analyzing your yard. Please try again.', 'image' => 'uploads/' . $stored]);
    exit;
}

if ($httpCode >= 400) {
    $msg = $decoded['error']['message'] ?? json_encode($decoded);
    evergreen_mark_upload_failed($pdo, $uploadId, $msg);
    error_log('evergreen consultation HTTP ' . $httpCode . ': ' . mb_substr((string) $msg, 0, 500));
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'We couldn’t finish your consultation. Please try again in a moment.', 'image' => 'uploads/' . $stored]);
    exit;
}

$text = evergreen_extract_text_from_claude_response($decoded);
$layout = evergreen_parse_layout_json($text);

if ($layout === null) {
    $sr = isset($decoded['stop_reason']) ? (string) $decoded['stop_reason'] : '';
    error_log('evergreen: layout JSON parse failed stop_reason=' . $sr . ' text_len=' . strlen($text) . ' excerpt=' . mb_substr($text, 0, 800));
    evergreen_mark_upload_failed($pdo, $uploadId, 'Could not parse JSON from model.');
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'error' => 'We couldn’t turn the results into a plan. Try again with a different photo.',
        'image' => 'uploads/' . $stored,
    ]);
    exit;
}

$placementParagraph = trim((string) ($layout['placement_paragraph'] ?? ''));
if ($placementParagraph === '') {
    $placementParagraph = evergreen_placement_paragraph_from_layout($layout);
}
$consultationFull = trim((string) ($layout['consultation_full'] ?? ''));
if ($consultationFull === '') {
    $consultationFull = evergreen_consultation_full_from_layout($layout);
}

$renderedRelative = null;
$renderError = null;
$geminiKey = (string) ($evergreenConfig['gemini_api_key'] ?? '');
$geminiModel = (string) ($evergreenConfig['gemini_image_model'] ?? 'gemini-3.1-flash-image-preview');

if ($geminiKey !== '') {
    if (!is_dir($rendersDir) && !mkdir($rendersDir, 0755, true)) {
        $renderError = 'Could not create renders directory.';
    } else {
        $geminiTimeout = max(90, min(300, $aiDeadlineSeconds - 20));
        $gem = evergreen_gemini_flash_image_edit(
            $geminiKey,
            $geminiModel,
            $visionMime,
            $base64,
            $placementParagraph,
            $rendersDir,
            $geminiTimeout
        );
        $renderedRelative = $gem['path'];
        $renderError = $gem['error'];
    }
} else {
    $renderError = 'GEMINI_API_KEY not set; skipped image render.';
}

$zonesCount = isset($layout['zones']) && is_array($layout['zones']) ? count($layout['zones']) : 0;
$layoutJson = json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$upd = $pdo->prepare(
    'UPDATE uploads SET api_success = 1, zones_count = :zc, error_message = NULL,
     location_label = :loc, focus_label = :foc,
     consultation_full = :cf, layout_json = :lj, rendered_image = :ri
     WHERE id = :id'
);
$upd->execute([
    ':zc'  => $zonesCount,
    ':loc' => $location,
    ':foc' => $focusLabel,
    ':cf'  => $consultationFull,
    ':lj'  => $layoutJson,
    ':ri'  => $renderedRelative,
    ':id'  => $uploadId,
]);

$previewNote = evergreen_preview_note_for_user($renderedRelative, $geminiKey !== '');

echo json_encode([
    'ok' => true,
    'image' => 'uploads/' . $stored,
    'rendered_image' => $renderedRelative,
    'preview_note' => $previewNote,
    'consultation_full' => $consultationFull,
    'layout' => $layout,
    'upload_id' => $uploadId,
    'location_label' => $location,
    'focus_label' => $focusLabel,
], JSON_THROW_ON_ERROR);

/**
 * @param array<string, mixed> $decoded
 */
function evergreen_extract_text_from_claude_response(array $decoded): string
{
    $content = $decoded['content'] ?? [];
    if (!is_array($content)) {
        return '';
    }
    $parts = [];
    foreach ($content as $block) {
        if (!is_array($block)) {
            continue;
        }
        if (($block['type'] ?? '') === 'text' && isset($block['text'])) {
            $parts[] = (string) $block['text'];
        }
    }
    return trim(implode("\n", $parts));
}

/**
 * Extract first top-level `{ ... }` using brace depth, respecting JSON strings (fixes bad slice when
 * placement_paragraph or consultation_full contains "}" or "{" inside values).
 */
function evergreen_extract_balanced_json_object(string $s): ?string
{
    $start = strpos($s, '{');
    if ($start === false) {
        return null;
    }
    $len = strlen($s);
    $depth = 0;
    $inString = false;
    $escape = false;
    for ($i = $start; $i < $len; $i++) {
        $c = $s[$i];
        if ($escape) {
            $escape = false;
            continue;
        }
        if ($inString) {
            if ($c === '\\') {
                $escape = true;
                continue;
            }
            if ($c === '"') {
                $inString = false;
            }
            continue;
        }
        if ($c === '"') {
            $inString = true;
            continue;
        }
        if ($c === '{') {
            $depth++;
        } elseif ($c === '}') {
            $depth--;
            if ($depth === 0) {
                return substr($s, $start, $i - $start + 1);
            }
        }
    }

    return null;
}

function evergreen_strip_json_markdown_fence(string $text): string
{
    $t = trim($text);
    if ($t === '') {
        return $t;
    }
    if (preg_match('/^```(?:json)?\s*\R?/i', $t, $m, PREG_OFFSET_CAPTURE)) {
        $from = $m[0][1] + strlen($m[0][0]);
        $close = strpos($t, '```', $from);
        if ($close !== false) {
            return trim(substr($t, $from, $close - $from));
        }
    }
    if (preg_match('/```(?:json)?\s*\R?/i', $t, $m, PREG_OFFSET_CAPTURE)) {
        $from = $m[0][1] + strlen($m[0][0]);
        $close = strpos($t, '```', $from);
        if ($close !== false) {
            return trim(substr($t, $from, $close - $from));
        }
    }

    return $t;
}

function evergreen_parse_layout_json(string $text): ?array
{
    $clean = evergreen_strip_json_markdown_fence(trim($text));
    $json = evergreen_extract_balanced_json_object($clean);
    if ($json === null) {
        $json = evergreen_extract_balanced_json_object(trim($text));
    }
    if ($json === null) {
        return null;
    }
    $flags = defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0;
    $data = json_decode($json, true, 512, $flags);
    if (!is_array($data)) {
        return null;
    }
    if (!isset($data['suggestions']) || !is_array($data['suggestions'])) {
        return null;
    }
    if (!isset($data['zones']) || !is_array($data['zones'])) {
        $data['zones'] = [];
    }

    return $data;
}

function evergreen_mark_upload_failed(PDO $pdo, int $id, string $message): void
{
    $stmt = $pdo->prepare(
        'UPDATE uploads SET api_success = 0, error_message = :em WHERE id = :id'
    );
    $stmt->execute([':em' => mb_substr($message, 0, 2000), ':id' => $id]);
}

function evergreen_preview_note_for_user(?string $renderPath, bool $hadRendererKey): string
{
    if ($renderPath !== null && $renderPath !== '') {
        return '';
    }
    if (!$hadRendererKey) {
        return 'A visual “after” preview isn’t available here. Your written plan below is complete.';
    }

    return 'We couldn’t create a preview image this time. Your written plan below is still complete.';
}

function evergreen_placement_paragraph_from_layout(array $layout): string
{
    $parts = [];
    foreach ($layout['suggestions'] ?? [] as $s) {
        if (!is_array($s)) {
            continue;
        }
        $plant = trim((string) ($s['plant'] ?? 'Plant'));
        $loc = trim((string) ($s['location'] ?? ''));
        $reason = trim((string) ($s['reason'] ?? ''));
        $x = isset($s['x']) && is_numeric($s['x']) ? (int) round((float) $s['x']) : null;
        $y = isset($s['y']) && is_numeric($s['y']) ? (int) round((float) $s['y']) : null;
        $pos = ($x !== null && $y !== null) ? " (approximately {$x}% from the left edge and {$y}% from the top of the image)" : '';
        $parts[] = "Place {$plant}{$pos}: {$loc}. {$reason}";
    }
    $out = trim(implode(' ', array_filter($parts)));

    return $out !== '' ? $out : 'Add suitable landscape plants in visible planting beds and open soil, matching sun and shade shown in the photograph, in a naturalistic arrangement.';
}

function evergreen_consultation_full_from_layout(array $layout): string
{
    $md = [];
    $sum = trim((string) ($layout['consultation_summary'] ?? ''));
    if ($sum !== '') {
        $md[] = "## Overview\n\n{$sum}";
    }
    foreach ($layout['suggestions'] ?? [] as $s) {
        if (!is_array($s)) {
            continue;
        }
        $name = trim((string) ($s['plant'] ?? 'Plant'));
        $loc = trim((string) ($s['location'] ?? ''));
        $reason = trim((string) ($s['reason'] ?? ''));
        $block = "## {$name}\n\n";
        if ($loc !== '') {
            $block .= "**Where:** {$loc}\n\n";
        }
        if ($reason !== '') {
            $block .= "{$reason}\n\n";
        }
        $care = $s['care'] ?? null;
        if (is_array($care)) {
            $block .= "**Care**\n\n";
            $labels = [
                'sun' => 'Sun & light',
                'water' => 'Water',
                'soil' => 'Soil',
                'maintenance' => 'Maintenance',
                'spacing' => 'Spacing',
                'seasonal' => 'Seasonal',
                'pests_tips' => 'Tips',
            ];
            foreach ($labels as $k => $label) {
                $v = isset($care[$k]) ? trim((string) $care[$k]) : '';
                if ($v !== '') {
                    $block .= '- **' . $label . ':** ' . $v . "\n";
                }
            }
        }
        $md[] = rtrim($block);
    }

    return implode("\n\n", array_filter($md));
}

/**
 * Bake EXIF Orientation into pixel data so Claude/Gemini match what the user saw in the camera roll.
 * JPEG only (typical phone vertical shots). Re-encodes as JPEG without orientation metadata.
 *
 * @return array{0: string, 1: string, 2: bool} binary, mime, whether bytes changed (rewrite file)
 */
function evergreen_orient_pixels_to_display(string $binary, string $mime, string $jpegPath): array
{
    if ($mime !== 'image/jpeg' || !function_exists('exif_read_data') || !function_exists('imagecreatefromstring')) {
        return [$binary, $mime, false];
    }
    $o = 1;
    $ifd0 = @exif_read_data($jpegPath, 'IFD0', true, false);
    if (is_array($ifd0) && isset($ifd0['IFD0']['Orientation'])) {
        $o = (int) $ifd0['IFD0']['Orientation'];
    } else {
        $flat = @exif_read_data($jpegPath, null, false, false);
        if (is_array($flat) && isset($flat['Orientation'])) {
            $o = (int) $flat['Orientation'];
        }
    }
    if ($o < 2 || $o > 8) {
        return [$binary, $mime, false];
    }

    $im = @imagecreatefromstring($binary);
    if ($im === false) {
        return [$binary, $mime, false];
    }

    $bg = imagecolorallocate($im, 255, 255, 255);
    if ($bg === false) {
        imagedestroy($im);

        return [$binary, $mime, false];
    }

    switch ($o) {
        case 2:
            imageflip($im, IMG_FLIP_HORIZONTAL);
            break;
        case 3:
            $r = imagerotate($im, 180, $bg);
            imagedestroy($im);
            if ($r === false) {
                return [$binary, $mime, false];
            }
            $im = $r;
            break;
        case 4:
            imageflip($im, IMG_FLIP_VERTICAL);
            break;
        case 5:
            imageflip($im, IMG_FLIP_HORIZONTAL);
            $r = imagerotate($im, -90, $bg);
            imagedestroy($im);
            if ($r === false) {
                return [$binary, $mime, false];
            }
            $im = $r;
            break;
        case 6:
            $r = imagerotate($im, -90, $bg);
            imagedestroy($im);
            if ($r === false) {
                return [$binary, $mime, false];
            }
            $im = $r;
            break;
        case 7:
            imageflip($im, IMG_FLIP_HORIZONTAL);
            $r = imagerotate($im, 90, $bg);
            imagedestroy($im);
            if ($r === false) {
                return [$binary, $mime, false];
            }
            $im = $r;
            break;
        case 8:
            $r = imagerotate($im, 90, $bg);
            imagedestroy($im);
            if ($r === false) {
                return [$binary, $mime, false];
            }
            $im = $r;
            break;
        default:
            imagedestroy($im);

            return [$binary, $mime, false];
    }

    ob_start();
    imagejpeg($im, null, 92);
    $out = ob_get_clean();
    imagedestroy($im);

    if (!is_string($out) || strlen($out) < 100) {
        return [$binary, $mime, false];
    }

    return [$out, 'image/jpeg', true];
}

/**
 * Anthropic vision input must stay under ~5 MB decoded; large camera photos exceed that.
 * Returns [jpeg-or-original-bytes, mime] or ['', mime] on failure.
 *
 * @return array{0: string, 1: string}
 */
function evergreen_reduce_image_for_vision(string $binary, string $mime): array
{
    $maxBytes = 5 * 1024 * 1024 - 262144; // ~4.75 MB under API cap
    if (strlen($binary) <= $maxBytes) {
        return [$binary, $mime];
    }
    if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
        return ['', $mime];
    }
    $src = @imagecreatefromstring($binary);
    if ($src === false) {
        return ['', $mime];
    }
    $w = imagesx($src);
    $h = imagesy($src);
    if ($w < 1 || $h < 1) {
        imagedestroy($src);

        return ['', $mime];
    }

    $maxSide = 2560;
    $quality = 86;

    for ($attempt = 0; $attempt < 28; $attempt++) {
        $nw = $w;
        $nh = $h;
        if ($maxSide > 0 && max($nw, $nh) > $maxSide) {
            $scale = $maxSide / max($nw, $nh);
            $nw = max(1, (int) round($nw * $scale));
            $nh = max(1, (int) round($nh * $scale));
        }

        $dst = imagecreatetruecolor($nw, $nh);
        if ($dst === false) {
            break;
        }
        imagealphablending($dst, false);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagejpeg($dst, null, $quality);
        $jpeg = ob_get_clean();
        imagedestroy($dst);

        if (is_string($jpeg) && strlen($jpeg) <= $maxBytes && strlen($jpeg) > 200) {
            imagedestroy($src);

            return [$jpeg, 'image/jpeg'];
        }

        if ($quality > 50) {
            $quality -= 7;
        } else {
            $maxSide = (int) max(400, $maxSide * 0.86);
        }
    }

    imagedestroy($src);

    return ['', $mime];
}

/**
 * @return array{path: ?string, error: ?string}
 */
function evergreen_gemini_flash_image_edit(
    string $apiKey,
    string $model,
    string $mime,
    string $imageBase64,
    string $placementParagraph,
    string $rendersDir,
    int $curlTimeout
): array {
    $url = sprintf(
        'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
        rawurlencode($model),
        rawurlencode($apiKey)
    );

    $instruction = "You are a photorealistic landscape image editor. Edit this photograph by adding vegetation only where soil, lawn, or planting beds exist. Preserve buildings, fences, paths, hardscape, sky, and overall lighting and perspective. Do not remove structures. Blend new plants naturally. Keep the same orientation and aspect ratio as the input (portrait stays portrait, landscape stays landscape).\n\nPlant placement instructions for the edit:\n\n"
        . $placementParagraph;

    $payloads = [
        [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'inlineData' => [
                                'mimeType' => $mime,
                                'data' => $imageBase64,
                            ],
                        ],
                        ['text' => $instruction],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseModalities' => ['TEXT', 'IMAGE'],
            ],
        ],
        [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => $mime,
                                'data' => $imageBase64,
                            ],
                        ],
                        ['text' => $instruction],
                    ],
                ],
            ],
            'generation_config' => [
                'response_modalities' => ['TEXT', 'IMAGE'],
            ],
        ],
    ];

    $lastRaw = '';
    $lastHttp = 0;
    $lastDecoded = null;

    foreach ($payloads as $pi => $payload) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => $curlTimeout,
        ]);
        $raw = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $lastRaw = is_string($raw) ? $raw : '';
        $lastHttp = $httpCode;

        if ($raw === false || $curlErr !== '') {
            return ['path' => null, 'error' => 'Gemini request failed: ' . ($curlErr ?: 'network')];
        }

        $decoded = json_decode($raw, true);
        $lastDecoded = is_array($decoded) ? $decoded : null;

        if ($httpCode >= 400) {
            if ($httpCode === 400 && $pi === 0) {
                continue;
            }
            $msg = is_array($decoded) ? ($decoded['error']['message'] ?? $raw) : $raw;

            return ['path' => null, 'error' => 'Gemini API error: ' . mb_substr((string) $msg, 0, 500)];
        }

        if (!is_array($decoded)) {
            return ['path' => null, 'error' => 'Gemini returned invalid JSON.'];
        }

        $img = evergreen_gemini_extract_first_inline_image($decoded);
        if ($img !== null) {
            [$outMime, $bytes] = $img;
            $extMap = [
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
            ];
            $ext = $extMap[$outMime] ?? 'png';
            $name = bin2hex(random_bytes(12)) . '.' . $ext;
            $dest = rtrim($rendersDir, '/') . '/' . $name;
            if (file_put_contents($dest, $bytes) === false) {
                return ['path' => null, 'error' => 'Could not save rendered image.'];
            }

            return ['path' => 'renders/' . $name, 'error' => null];
        }

        if ($pi === 0) {
            continue;
        }

        break;
    }

    $fb = is_array($lastDecoded) ? ($lastDecoded['promptFeedback'] ?? null) : null;
    $hint = is_array($fb) ? json_encode($fb) : mb_substr($lastRaw, 0, 300);

    return ['path' => null, 'error' => 'No image in Gemini response (HTTP ' . $lastHttp . '). ' . mb_substr((string) $hint, 0, 240)];
}

/**
 * @return array{0: string, 1: string}|null [mime, binary]
 */
function evergreen_gemini_extract_first_inline_image(array $decoded): ?array
{
    foreach ($decoded['candidates'] ?? [] as $cand) {
        if (!is_array($cand)) {
            continue;
        }
        $parts = $cand['content']['parts'] ?? [];
        if (!is_array($parts)) {
            continue;
        }
        foreach ($parts as $part) {
            if (!is_array($part)) {
                continue;
            }
            $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;
            if (!is_array($inline)) {
                continue;
            }
            $data = $inline['data'] ?? '';
            if ($data === '' || !is_string($data)) {
                continue;
            }
            $mt = (string) ($inline['mimeType'] ?? $inline['mime_type'] ?? 'image/png');
            $bin = base64_decode($data, true);
            if ($bin === false) {
                continue;
            }

            return [$mt, $bin];
        }
    }

    return null;
}
