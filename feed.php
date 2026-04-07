<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/db.php';

/** @var array $evergreenConfig */
$dbPath = (string) ($evergreenConfig['db_path'] ?? __DIR__ . '/database.sqlite');
$pdo = evergreen_db($dbPath);

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

$total = (int) $pdo->query(
    "SELECT COUNT(*) FROM uploads WHERE api_success = 1 AND consultation_full IS NOT NULL AND consultation_full != ''"
)->fetchColumn();

$rows = $pdo->prepare(
    "SELECT id, stored_filename, location_label, focus_label, rendered_image, created_at, zones_count
     FROM uploads
     WHERE api_success = 1 AND consultation_full IS NOT NULL AND consultation_full != ''
     ORDER BY id DESC
     LIMIT :lim OFFSET :off"
);
$rows->bindValue(':lim', $perPage, PDO::PARAM_INT);
$rows->bindValue(':off', $offset, PDO::PARAM_INT);
$rows->execute();
$entries = $rows->fetchAll();

$totalPages = max(1, (int) ceil($total / $perPage));

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

function ev_focus_badge(string $focus): string {
    $f = strtolower($focus);

    return match (true) {
        str_contains($f, 'balanced') => 'Balanced',
        str_contains($f, 'food forest') => 'Food forest',
        str_contains($f, 'kitchen') => 'Kitchen garden',
        str_contains($f, 'look') || str_contains($f, 'maxing') => 'Looks maxing',
        str_contains($f, 'food') || str_contains($f, 'forest') || str_contains($f, 'edible') => 'Edible',
        default => 'Plan',
    };
}

function ev_focus_color(string $focus): string {
    $f = strtolower($focus);

    return match (true) {
        str_contains($f, 'look') || str_contains($f, 'maxing') => 'bg-sky-900/40 text-sky-300 ring-sky-700/30',
        str_contains($f, 'kitchen') || str_contains($f, 'food') || str_contains($f, 'forest')
            || str_contains($f, 'balanced') || str_contains($f, 'edible') => 'bg-amber-900/40 text-amber-300 ring-amber-700/30',
        default => 'bg-moss-950/60 text-moss-300 ring-moss-700/30',
    };
}

?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Community Feed — Evergreen</title>
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
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
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
    <style>
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(51,65,85,0.7); border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(71,85,105,0.9); }
        .card-img { transition: transform 0.35s ease; }
        .feed-card:hover .card-img { transform: scale(1.03); }
        @media (prefers-reduced-motion: reduce) { .card-img { transition: none !important; } }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">

    <!-- ── Top nav ──────────────────────────────────────────────────────── -->
    <header class="sticky top-0 z-40 border-b border-slate-800 bg-slate-950/90 backdrop-blur-sm">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
            <a href="index.php" class="flex items-center gap-2.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500/60 rounded-lg" aria-label="Evergreen home">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-moss-600/20 ring-1 ring-moss-500/30" aria-hidden="true">
                    <svg class="h-4 w-4 text-moss-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c4 4 6 8 6 12a6 6 0 01-12 0c0-4 2-8 6-12z" />
                    </svg>
                </div>
                <span class="font-display text-sm font-semibold text-white">Evergreen</span>
            </a>
            <nav class="flex items-center gap-2" aria-label="Site navigation">
                <span class="hidden sm:inline text-xs text-slate-500 mr-1">Community feed</span>
                <a href="index.php"
                   class="rounded-lg bg-moss-600 px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-moss-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 cursor-pointer">
                    Get my plan →
                </a>
            </nav>
        </div>
    </header>

    <!-- ── Hero ─────────────────────────────────────────────────────────── -->
    <section class="border-b border-slate-800/60 bg-gradient-to-b from-slate-900/60 to-transparent px-4 py-8 sm:py-10 sm:px-6">
        <div class="mx-auto max-w-6xl">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="font-display text-2xl font-bold text-white sm:text-3xl">Community Yards</h1>
                    <p class="mt-1.5 text-sm text-slate-400 max-w-lg">Real yards, real plans. See before &amp; afters from people across the world and draw inspiration for your own space.</p>
                </div>
                <?php if ($total > 0): ?>
                <p class="text-sm text-slate-500 mt-2 sm:mt-0 shrink-0">
                    <span class="font-semibold text-slate-300"><?= number_format($total) ?></span>
                    consultation<?= $total !== 1 ? 's' : '' ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ── Feed grid ────────────────────────────────────────────────────── -->
    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">

        <?php if (empty($entries)): ?>
        <!-- Empty state -->
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-900 ring-1 ring-slate-800 mb-4" aria-hidden="true">
                <svg class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <h2 class="font-display text-lg font-semibold text-white mb-2">No consultations yet</h2>
            <p class="text-sm text-slate-500 max-w-xs mb-6">Be the first to share your yard transformation.</p>
            <a href="index.php" class="rounded-lg bg-moss-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-moss-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-400">
                Get my free consultation →
            </a>
        </div>

        <?php else: ?>
        <!-- Grid -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" role="list" aria-label="Community yard consultations">
            <?php foreach ($entries as $i => $row):
                $id        = (int) $row['id'];
                $before    = 'uploads/' . htmlspecialchars($row['stored_filename'], ENT_QUOTES, 'UTF-8');
                $after     = $row['rendered_image'] ? htmlspecialchars($row['rendered_image'], ENT_QUOTES, 'UTF-8') : null;
                $loc       = htmlspecialchars((string) ($row['location_label'] ?? ''), ENT_QUOTES, 'UTF-8');
                $focusRaw  = (string) ($row['focus_label'] ?? '');
                $badge     = ev_focus_badge($focusRaw);
                $badgeColor = ev_focus_color($focusRaw);
                $ago       = ev_ago((string) ($row['created_at'] ?? ''));
                $delay     = min($i % $perPage, 8) * 40;
            ?>
            <article class="feed-card group relative flex flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 ring-1 ring-white/[0.03] transition-all duration-200 hover:border-slate-700 hover:shadow-xl hover:shadow-black/40 animate-fade-up"
                     style="animation-delay:<?= $delay ?>ms; opacity:0"
                     role="listitem">
                <a href="consultation.php?id=<?= $id ?>" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500/60 focus-visible:ring-inset" aria-label="View consultation for <?= $loc ?: 'unknown location' ?>">

                    <!-- Image comparison -->
                    <div class="relative overflow-hidden bg-black/30" style="aspect-ratio:4/3">
                        <?php if ($after): ?>
                        <!-- Split before/after -->
                        <div class="absolute inset-0 flex">
                            <div class="relative w-1/2 overflow-hidden border-r border-slate-700/60">
                                <img src="<?= $before ?>" alt="Before" class="card-img h-full w-full object-cover" loading="lazy">
                                <span class="absolute bottom-1.5 left-1.5 rounded px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider bg-black/60 text-slate-400 backdrop-blur-sm">Before</span>
                            </div>
                            <div class="relative w-1/2 overflow-hidden">
                                <img src="<?= $after ?>" alt="Concept" class="card-img h-full w-full object-cover" loading="lazy">
                                <span class="absolute bottom-1.5 right-1.5 rounded px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider bg-moss-950/80 text-moss-300 backdrop-blur-sm">Concept</span>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Before only -->
                        <img src="<?= $before ?>" alt="Yard photo" class="card-img h-full w-full object-cover" loading="lazy">
                        <span class="absolute bottom-1.5 left-1.5 rounded px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider bg-black/60 text-slate-400 backdrop-blur-sm">Before</span>
                        <?php endif; ?>

                        <!-- View overlay -->
                        <div class="absolute inset-0 flex items-center justify-center bg-black/0 transition-all duration-200 group-hover:bg-black/25" aria-hidden="true">
                            <span class="scale-90 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white opacity-0 backdrop-blur-sm transition-all duration-200 group-hover:scale-100 group-hover:opacity-100">
                                View plan →
                            </span>
                        </div>
                    </div>

                    <!-- Card body -->
                    <div class="flex flex-1 flex-col px-3.5 py-3">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="min-w-0">
                                <?php if ($loc): ?>
                                <p class="flex items-center gap-1 text-xs font-semibold text-slate-200 truncate" title="<?= $loc ?>">
                                    <svg class="h-3 w-3 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span class="truncate"><?= $loc ?></span>
                                </p>
                                <?php endif; ?>
                                <p class="mt-0.5 text-[11px] text-slate-600"><?= $ago ?></p>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 <?= $badgeColor ?>"><?= htmlspecialchars($badge) ?></span>
                        </div>

                        <div class="mt-auto pt-2 border-t border-slate-800/60">
                            <span class="text-[11px] font-semibold text-moss-400 group-hover:text-moss-300 transition-colors">
                                View full plan →
                            </span>
                        </div>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-10 flex items-center justify-center gap-1.5" aria-label="Pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>"
               class="flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-900/60 px-3.5 py-2 text-xs font-semibold text-slate-300 transition hover:bg-slate-800 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500/50"
               aria-label="Previous page">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Prev
            </a>
            <?php endif; ?>

            <?php
            $range = range(max(1, $page - 2), min($totalPages, $page + 2));
            foreach ($range as $p):
                $active = $p === $page;
            ?>
            <a href="?page=<?= $p ?>"
               class="rounded-lg px-3.5 py-2 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500/50 <?= $active ? 'bg-moss-600 text-white shadow-lg shadow-moss-950/30' : 'border border-slate-700 bg-slate-900/60 text-slate-400 hover:bg-slate-800 hover:text-white' ?>"
               <?= $active ? 'aria-current="page"' : '' ?>>
                <?= $p ?>
            </a>
            <?php endforeach; ?>

            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>"
               class="flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-900/60 px-3.5 py-2 text-xs font-semibold text-slate-300 transition hover:bg-slate-800 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500/50"
               aria-label="Next page">
                Next
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        <?php endif; ?>
    </main>

    <!-- ── Footer CTA ──────────────────────────────────────────────────── -->
    <footer class="border-t border-slate-800/60 bg-slate-900/30 mt-4">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 flex flex-col items-center text-center gap-4">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-moss-600/20 ring-1 ring-moss-500/30" aria-hidden="true">
                <svg class="h-5 w-5 text-moss-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c4 4 6 8 6 12a6 6 0 01-12 0c0-4 2-8 6-12z" />
                </svg>
            </div>
            <div>
                <p class="font-display text-base font-semibold text-white">Ready to transform your yard?</p>
                <p class="mt-1 text-sm text-slate-500">One photo is all it takes. Get your personalised plan in minutes.</p>
            </div>
            <a href="index.php"
               class="rounded-xl bg-moss-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-moss-950/30 transition hover:bg-moss-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-400">
                Get my free consultation →
            </a>
            <p class="text-[10px] text-slate-700">Informational only. Verify local codes and HOA rules before planting.</p>
        </div>
    </footer>

</body>
</html>
