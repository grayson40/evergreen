<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/db.php';

/** @var array $evergreenConfig */
$dbPath = (string) ($evergreenConfig['db_path'] ?? __DIR__ . '/database.sqlite');
$pdo = evergreen_db($dbPath);

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    header('Location: feed.php');
    exit;
}

$row = $pdo->prepare(
    'SELECT * FROM uploads WHERE id = :id AND api_success = 1'
);
$row->execute([':id' => $id]);
$entry = $row->fetch();

if (!$entry) {
    http_response_code(404);
    header('Location: feed.php');
    exit;
}

$layout = null;
if (!empty($entry['layout_json'])) {
    $decoded = json_decode((string) $entry['layout_json'], true);
    if (is_array($decoded)) {
        $layout = $decoded;
    }
}

$suggestions  = $layout['suggestions'] ?? [];
$location     = htmlspecialchars((string) ($entry['location_label'] ?? ''), ENT_QUOTES, 'UTF-8');
$focusRaw     = (string) ($entry['focus_label'] ?? '');
$focus        = htmlspecialchars($focusRaw, ENT_QUOTES, 'UTF-8');
$beforeUrl    = 'uploads/' . htmlspecialchars((string) $entry['stored_filename'], ENT_QUOTES, 'UTF-8');
$afterUrl     = !empty($entry['rendered_image'])
    ? htmlspecialchars((string) $entry['rendered_image'], ENT_QUOTES, 'UTF-8')
    : null;
$consultFull  = (string) ($entry['consultation_full'] ?? '');
$zonesCount   = (int) ($entry['zones_count'] ?? 0);

function ev_ago(string $iso): string {
    try {
        $dt   = new DateTimeImmutable($iso);
        $now  = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $diff = $now->getTimestamp() - $dt->getTimestamp();
        if ($diff < 60)    return 'just now';
        if ($diff < 3600)  return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return $dt->format('M j, Y');
    } catch (\Throwable) {
        return '';
    }
}

$ago = ev_ago((string) ($entry['created_at'] ?? ''));

function ev_focus_color(string $focus): string {
    $f = strtolower($focus);

    return match (true) {
        str_contains($f, 'look') || str_contains($f, 'maxing') => 'bg-sky-900/40 text-sky-300 ring-1 ring-sky-700/30',
        str_contains($f, 'kitchen') || str_contains($f, 'food') || str_contains($f, 'forest')
            || str_contains($f, 'balanced') || str_contains($f, 'edible') => 'bg-amber-900/40 text-amber-300 ring-1 ring-amber-700/30',
        default => 'bg-moss-950/60 text-moss-300 ring-1 ring-moss-700/30',
    };
}

// Prev / next navigation
$prev = $pdo->prepare(
    'SELECT id FROM uploads WHERE api_success = 1 AND consultation_full IS NOT NULL AND consultation_full != \'\' AND id > :id ORDER BY id ASC LIMIT 1'
);
$prev->execute([':id' => $id]);
$prevId = $prev->fetchColumn();

$next = $pdo->prepare(
    'SELECT id FROM uploads WHERE api_success = 1 AND consultation_full IS NOT NULL AND consultation_full != \'\' AND id < :id ORDER BY id DESC LIMIT 1'
);
$next->execute([':id' => $id]);
$nextId = $next->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $location ? htmlspecialchars($location) . ' — ' : '' ?>Yard Consultation — Evergreen</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['DM Sans', 'system-ui', 'sans-serif'],
                        display: ['Outfit', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        moss: {
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            950: '#052e16',
                        },
                    },
                    keyframes: {
                        'fade-up': {
                            '0%': { opacity: '0', transform: 'translateY(8px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                    },
                    animation: {
                        'fade-up': 'fade-up 0.3s ease-out forwards',
                    },
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked@12.0.0/marked.min.js"></script>
    <style>
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(51,65,85,0.7); border-radius: 9999px; }

        /* Consultation markdown */
        #consultation-body { color: #cbd5e1; font-size: 0.875rem; line-height: 1.7; }
        #consultation-body h1 { font-size: 1.15rem; font-weight: 700; color: #f1f5f9; margin: 0 0 0.75rem; font-family: Outfit, system-ui, sans-serif; }
        #consultation-body h2 { font-size: 0.9375rem; font-weight: 600; color: #4ade80; margin: 1.5rem 0 0.5rem; font-family: Outfit, system-ui, sans-serif; padding-bottom: 0.25rem; border-bottom: 1px solid rgba(51,65,85,0.6); }
        #consultation-body h2:first-child { margin-top: 0; }
        #consultation-body h3 { font-size: 0.875rem; font-weight: 600; color: #e2e8f0; margin: 0.875rem 0 0.25rem; }
        #consultation-body p { margin: 0 0 0.65rem; }
        #consultation-body ul, #consultation-body ol { margin: 0 0 0.65rem 1.25rem; }
        #consultation-body ul { list-style: disc; }
        #consultation-body ol { list-style: decimal; }
        #consultation-body li { margin-bottom: 0.2rem; }
        #consultation-body strong { color: #e2e8f0; font-weight: 600; }
        #consultation-body a { color: #4ade80; text-decoration: underline; text-underline-offset: 2px; }
        #consultation-body blockquote { border-left: 3px solid #16a34a; padding-left: 0.875rem; color: #94a3b8; font-style: italic; margin: 0 0 0.65rem; }

        .care-label { font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; }
        .care-value { font-size: 0.8125rem; color: #cbd5e1; line-height: 1.4; margin-top: 0.1rem; }
        .plant-num { display:inline-flex;align-items:center;justify-content:center;width:1.375rem;height:1.375rem;border-radius:9999px;background:rgba(22,163,74,0.15);color:#4ade80;font-size:0.6875rem;font-weight:700;flex-shrink:0; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">

    <!-- ── Top nav ──────────────────────────────────────────────────────── -->
    <header class="sticky top-0 z-40 border-b border-slate-800 bg-slate-950/90 backdrop-blur-sm">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-3 sm:px-6">
            <div class="flex items-center gap-3 min-w-0">
                <a href="feed.php"
                   class="flex items-center gap-1.5 text-xs font-semibold text-slate-400 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500/60 rounded-lg shrink-0"
                   aria-label="Back to community feed">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span class="hidden sm:inline">Community feed</span>
                    <span class="sm:hidden">Feed</span>
                </a>
                <?php if ($location): ?>
                <span class="text-slate-700" aria-hidden="true">/</span>
                <p class="truncate text-xs text-slate-500 hidden sm:block"><?= $location ?></p>
                <?php endif; ?>
            </div>
            <a href="index.php"
               class="shrink-0 rounded-lg bg-moss-600 px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-moss-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">
                Get my plan →
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-10">

        <!-- ── Page header ──────────────────────────────────────────────── -->
        <div class="mb-7 animate-fade-up" style="opacity:0">
            <div class="flex flex-wrap items-start gap-3">
                <div class="flex-1 min-w-0">
                    <?php if ($location): ?>
                    <h1 class="font-display text-xl font-bold text-white sm:text-2xl flex items-center gap-2 flex-wrap">
                        <svg class="h-5 w-5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <?= $location ?>
                    </h1>
                    <?php else: ?>
                    <h1 class="font-display text-xl font-bold text-white sm:text-2xl">Yard Consultation</h1>
                    <?php endif; ?>
                    <p class="mt-1 text-sm text-slate-500"><?= $ago ?><?= $zonesCount ? ' · ' . $zonesCount . ' zones analysed' : '' ?></p>
                </div>
                <?php if ($focus): ?>
                <span class="rounded-full px-3 py-1 text-xs font-semibold <?= ev_focus_color($focusRaw) ?>">
                    <?= $focus ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="space-y-8">

            <!-- ── Before / After ───────────────────────────────────────── -->
            <section class="animate-fade-up" style="opacity:0; animation-delay:0.05s" aria-label="Before and concept images">
                <div class="flex items-baseline justify-between mb-3">
                    <h2 class="font-display text-sm font-semibold text-white">Before &amp; Concept</h2>
                    <p class="text-[11px] text-slate-600">Concept is illustrative — not a build spec</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <figure class="overflow-hidden rounded-xl border border-slate-800 bg-black/40">
                        <div class="flex items-center justify-between bg-slate-900/80 px-3 py-2">
                            <figcaption class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Before</figcaption>
                            <span class="text-[10px] text-slate-600">Submitted photo</span>
                        </div>
                        <img src="<?= $beforeUrl ?>" alt="Yard before" class="ev-photo-lightbox block w-full cursor-zoom-in object-contain max-h-72 sm:max-h-80 bg-black/20">
                    </figure>
                    <figure class="overflow-hidden rounded-xl border border-slate-800 bg-black/40">
                        <div class="flex items-center justify-between bg-slate-900/80 px-3 py-2">
                            <figcaption class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Concept</figcaption>
                            <span class="text-[10px] text-slate-600">AI render</span>
                        </div>
                        <?php if ($afterUrl): ?>
                        <img src="<?= $afterUrl ?>" alt="Concept render" class="ev-photo-lightbox block w-full cursor-zoom-in object-contain max-h-72 sm:max-h-80 bg-black/20">
                        <?php else: ?>
                        <div class="flex min-h-[10rem] flex-col items-center justify-center gap-2 px-4 py-8 text-center">
                            <svg class="h-8 w-8 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-xs text-slate-500">No concept image available</p>
                        </div>
                        <?php endif; ?>
                    </figure>
                </div>
            </section>

            <!-- ── Plant recommendations ────────────────────────────────── -->
            <?php if (!empty($suggestions)): ?>
            <section class="animate-fade-up" style="opacity:0; animation-delay:0.1s" aria-label="Recommended plants">
                <div class="flex items-baseline justify-between mb-3">
                    <h2 class="font-display text-sm font-semibold text-white">Recommended Plants</h2>
                    <p class="text-[11px] text-slate-600"><?= count($suggestions) ?> plant<?= count($suggestions) !== 1 ? 's' : '' ?></p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <?php
                    $careLabels = [
                        'sun'          => ['Sunlight',    '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>'],
                        'water'        => ['Watering',    '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/></svg>'],
                        'soil'         => ['Soil',        '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M3 10h18M3 7l9-4 9 4M4 10v11M20 10v11M8 10v11M16 10v11M12 10v11"/></svg>'],
                        'maintenance'  => ['Maintenance', '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'],
                        'spacing'      => ['Spacing',     '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>'],
                        'seasonal'     => ['Seasonal',    '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'],
                        'pests_tips'   => ['Tips',        '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'],
                    ];
                    foreach ($suggestions as $idx => $s):
                        if (!is_array($s)) continue;
                        $pname  = htmlspecialchars(trim((string)($s['plant'] ?? 'Plant')), ENT_QUOTES, 'UTF-8');
                        $ploc   = htmlspecialchars(trim((string)($s['location'] ?? '')), ENT_QUOTES, 'UTF-8');
                        $preason = htmlspecialchars(trim((string)($s['reason'] ?? '')), ENT_QUOTES, 'UTF-8');
                        $care   = is_array($s['care'] ?? null) ? $s['care'] : [];
                    ?>
                    <article class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden ring-1 ring-white/[0.03]">
                        <div class="px-4 pt-4 pb-3 border-b border-slate-800/70">
                            <div class="flex items-center gap-2.5 mb-1.5">
                                <span class="plant-num" aria-hidden="true"><?= $idx + 1 ?></span>
                                <h3 class="font-display text-sm font-semibold text-white"><?= $pname ?></h3>
                            </div>
                            <?php if ($ploc): ?>
                            <p class="text-[11px] text-moss-400 font-medium mb-1.5 flex items-center gap-1.5">
                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <?= $ploc ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($preason): ?>
                            <p class="text-xs text-slate-400 leading-snug"><?= $preason ?></p>
                            <?php endif; ?>
                        </div>
                        <?php
                        $hasCare = false;
                        foreach (array_keys($careLabels) as $ck) {
                            if (!empty($care[$ck])) { $hasCare = true; break; }
                        }
                        if ($hasCare):
                        ?>
                        <div class="px-4 py-3 grid grid-cols-2 gap-x-4 gap-y-3">
                            <?php foreach ($careLabels as $ck => [$clabel, $cicon]):
                                $val = trim((string)($care[$ck] ?? ''));
                                if ($val === '') continue;
                            ?>
                            <div class="flex flex-col gap-0.5">
                                <div class="flex items-center gap-1 text-slate-500">
                                    <?= $cicon ?>
                                    <span class="care-label"><?= htmlspecialchars($clabel) ?></span>
                                </div>
                                <p class="care-value"><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- ── Full written plan ─────────────────────────────────────── -->
            <?php if ($consultFull): ?>
            <section class="animate-fade-up" style="opacity:0; animation-delay:0.15s" aria-label="Full consultation plan">
                <div class="flex items-baseline justify-between mb-3">
                    <h2 class="font-display text-sm font-semibold text-white">Full Plan</h2>
                    <p class="text-[11px] text-slate-600">Cross-check with a local nursery</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-5 sm:p-6 ring-1 ring-white/[0.03]">
                    <div id="consultation-body" class="max-w-prose"></div>
                    <script>
                        (function() {
                            const raw = <?= json_encode($consultFull, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                            const el  = document.getElementById('consultation-body');
                            if (typeof marked !== 'undefined' && typeof DOMPurify !== 'undefined') {
                                el.innerHTML = DOMPurify.sanitize(marked.parse(raw, { breaks: true }));
                            } else {
                                el.textContent = raw;
                            }
                        })();
                    </script>
                </div>
            </section>
            <?php endif; ?>

            <!-- ── CTA + Prev/Next ───────────────────────────────────────── -->
            <div class="animate-fade-up pt-2" style="opacity:0; animation-delay:0.2s">
                <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-5 text-center">
                    <p class="font-display text-sm font-semibold text-white mb-1">Get a plan for your yard</p>
                    <p class="text-xs text-slate-500 mb-4">One photo is all it takes. Free consultation in minutes.</p>
                    <a href="index.php"
                       class="inline-flex items-center gap-2 rounded-xl bg-moss-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-moss-950/30 transition hover:bg-moss-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-400">
                        Start my consultation →
                    </a>
                </div>
            </div>

            <!-- Prev / Next navigation -->
            <?php if ($prevId || $nextId): ?>
            <nav class="flex items-center gap-3 pt-2" aria-label="Consultation navigation">
                <?php if ($nextId): ?>
                <a href="consultation.php?id=<?= (int)$nextId ?>"
                   class="flex-1 flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900/50 px-4 py-3 text-xs font-semibold text-slate-400 transition hover:bg-slate-800 hover:text-white hover:border-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500/50">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    <span>Newer</span>
                </a>
                <?php endif; ?>
                <a href="feed.php"
                   class="flex items-center justify-center rounded-xl border border-slate-800 bg-slate-900/50 px-4 py-3 text-xs font-semibold text-slate-400 transition hover:bg-slate-800 hover:text-white hover:border-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500/50"
                   aria-label="Back to community feed">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </a>
                <?php if ($prevId): ?>
                <a href="consultation.php?id=<?= (int)$prevId ?>"
                   class="flex-1 flex items-center justify-end gap-2 rounded-xl border border-slate-800 bg-slate-900/50 px-4 py-3 text-xs font-semibold text-slate-400 transition hover:bg-slate-800 hover:text-white hover:border-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500/50">
                    <span>Older</span>
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>

        </div>
    </main>

<script src="lightbox.js"></script>
</body>
</html>
