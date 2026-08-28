<?php
/**
 * Vista: Análisis de Competencia SEO
 * Vista corregida - sin uso de self:: en ámbito global
 */
$pageTitle = 'Análisis de Competencia SEO';

// ═══════════════════════════════════════════════════════════════════
// VARIABLES CON FALLBACK SEGURO
// ═══════════════════════════════════════════════════════════════════
$analysis = $analysis ?? [
    'competitive_score' => 0,
    'comparison' => [
        'level' => 'poor',
        'words_vs_benchmark' => 0,
        'h2_vs_benchmark' => 0,
        'links_vs_benchmark' => 0,
        'score_vs_benchmark' => 0,
    ],
    'content' => [
        'word_count' => 0,
        'reading_time_minutes' => 0,
    ],
    'structure' => ['h2_count' => 0],
    'links' => [
        'total' => 0, 'internal' => 0, 'external' => 0,
        'nofollow' => 0, 'anchor_texts' => [],
    ],
    'benchmark' => [
        'word_count' => 0, 'h2_count' => 0,
        'internal_links' => 0, 'external_links' => 0,
        'reading_time' => 0, 'avg_competitive_score' => 0,
    ],
    'keywords' => [
        'main_keyword' => '',
        'keyword_density' => 0,
        'density_optimal' => false,
        'top_bigrams' => [],
    ],
    'recommendations' => [],
];

$page = $page ?? ['id' => 0];

$score     = (int)($analysis['competitive_score'] ?? 0);
$color     = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger');
$level     = $analysis['comparison']['level'] ?? 'poor';
$levelLabel = [
    'excellent' => 'Excelente',
    'good'      => 'Bueno',
    'fair'      => 'Regular',
    'poor'      => 'Necesita trabajo',
][$level] ?? 'Necesita trabajo';

// ═══════════════════════════════════════════════════════════════════
// ✅ FUNCIÓN GLOBAL (reemplaza self::diffBadge)
// Protegida con function_exists y prefijo _ para evitar colisiones
// ═══════════════════════════════════════════════════════════════════
if (!function_exists('_seoDiffBadge')) {
    /**
     * Renderiza un badge con la diferencia entre valores
     *
     * @param int|float $diff Diferencia a mostrar
     * @return string HTML del badge
     */
    function _seoDiffBadge($diff): string
    {
        $diff = (int)$diff;
        if ($diff > 0) {
            return '<span class="badge badge-success">+' . $diff . ' ✓</span>';
        }
        if ($diff < 0) {
            return '<span class="badge badge-danger">' . $diff . '</span>';
        }
        return '<span class="badge badge-info">= igual</span>';
    }
}
?>

<!-- ═══ HEADER ═══ -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0"><i class="bi bi-trophy text-primary"></i> Análisis de Competencia SEO</h2>
    <div class="d-flex gap-2">
        <a class="btn btn-ghost" href="<?= url("admin/seo/{$page['id']}/analyze") ?>">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <a class="btn btn-primary" href="<?= url("admin/pages/{$page['id']}/edit") ?>">
            <i class="bi bi-pencil"></i> Editar página
        </a>
    </div>
</div>

<!-- ═══ SCORE COMPETITIVO ═══ -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <div style="position:relative;width:160px;height:160px;margin:0 auto 1rem">
                    <svg viewBox="0 0 100 100" style="transform:rotate(-90deg)">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="var(--border, #dee2e6)" stroke-width="8"/>
                        <circle cx="50" cy="50" r="45" fill="none" stroke="var(--bs-<?= $color ?>, #0d6efd)" stroke-width="8"
                                stroke-dasharray="<?= $score * 2.83 ?> 283" stroke-linecap="round"/>
                    </svg>
                    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center">
                        <div style="font-size:2.5rem;font-weight:700;color:var(--bs-<?= $color ?>, #0d6efd)"><?= $score ?></div>
                        <div class="text-small text-muted">/ 100</div>
                    </div>
                </div>
                <h4 class="text-<?= $color ?>"><?= e($levelLabel) ?></h4>
                <p class="text-muted text-small">Score competitivo global</p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3><i class="bi bi-bar-chart"></i> Comparativa con Top 10 de Google</h3></div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Métrica</th>
                            <th>Tu página</th>
                            <th>Benchmark</th>
                            <th>Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Palabras</td>
                            <td><strong><?= (int)($analysis['content']['word_count'] ?? 0) ?></strong></td>
                            <td><?= (int)($analysis['benchmark']['word_count'] ?? 0) ?></td>
                            <!-- ✅ CORREGIDO: _seoDiffBadge() en lugar de self::diffBadge() -->
                            <td><?= _seoDiffBadge($analysis['comparison']['words_vs_benchmark'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Subtítulos H2</td>
                            <td><strong><?= (int)($analysis['structure']['h2_count'] ?? 0) ?></strong></td>
                            <td><?= (int)($analysis['benchmark']['h2_count'] ?? 0) ?></td>
                            <td><?= _seoDiffBadge($analysis['comparison']['h2_vs_benchmark'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Enlaces totales</td>
                            <td><strong><?= (int)($analysis['links']['total'] ?? 0) ?></strong></td>
                            <td><?= (int)(($analysis['benchmark']['internal_links'] ?? 0) + ($analysis['benchmark']['external_links'] ?? 0)) ?></td>
                            <td><?= _seoDiffBadge($analysis['comparison']['links_vs_benchmark'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Tiempo de lectura</td>
                            <td><strong><?= (int)($analysis['content']['reading_time_minutes'] ?? 0) ?> min</strong></td>
                            <td><?= (int)($analysis['benchmark']['reading_time'] ?? 0) ?> min</td>
                            <td><?= _seoDiffBadge(($analysis['content']['reading_time_minutes'] ?? 0) - ($analysis['benchmark']['reading_time'] ?? 0)) ?></td>
                        </tr>
                        <tr>
                            <td>Score vs benchmark</td>
                            <td colspan="2">
                                <strong class="text-<?= $color ?>"><?= $score ?></strong> vs
                                <strong><?= (int)($analysis['benchmark']['avg_competitive_score'] ?? 0) ?></strong>
                            </td>
                            <td><?= _seoDiffBadge($analysis['comparison']['score_vs_benchmark'] ?? 0) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ═══ DETALLES DEL ANÁLISIS ═══ -->
<div class="row g-3 mb-4">
    <!-- Keywords -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3><i class="bi bi-key"></i> Keywords</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Keyword principal:</strong>
                    <span class="badge badge-primary ms-2">
                        <?= e($analysis['keywords']['main_keyword'] ?: 'No detectada') ?>
                    </span>
                </div>
                <div class="mb-3">
                    <strong>Densidad:</strong>
                    <span class="badge badge-<?= ($analysis['keywords']['density_optimal'] ?? false) ? 'success' : 'warning' ?> ms-2">
                        <?= number_format((float)($analysis['keywords']['keyword_density'] ?? 0), 2) ?>%
                        <?= ($analysis['keywords']['density_optimal'] ?? false) ? '(óptima)' : '(ideal: 1-3%)' ?>
                    </span>
                </div>
                <h6 class="mt-3">Top bigrams:</h6>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach (($analysis['keywords']['top_bigrams'] ?? []) as $kw => $count): ?>
                        <span class="badge badge-info"><?= e($kw) ?> <small>(<?= (int)$count ?>)</small></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Enlaces -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3><i class="bi bi-link-45deg"></i> Enlaces</h3></div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="stat-value"><?= (int)($analysis['links']['internal'] ?? 0) ?></div>
                        <div class="text-small text-muted">Internos</div>
                    </div>
                    <div class="col-4">
                        <div class="stat-value"><?= (int)($analysis['links']['external'] ?? 0) ?></div>
                        <div class="text-small text-muted">Externos</div>
                    </div>
                    <div class="col-4">
                        <div class="stat-value"><?= (int)($analysis['links']['nofollow'] ?? 0) ?></div>
                        <div class="text-small text-muted">Nofollow</div>
                    </div>
                </div>
                <hr>
                <h6>Anclas de enlace:</h6>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach (array_slice($analysis['links']['anchor_texts'] ?? [], 0, 8) as $a): ?>
                        <span class="badge badge-ghost"><?= e(mb_substr($a, 0, 30)) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ RECOMENDACIONES ═══ -->
<div class="card">
    <div class="card-header"><h3><i class="bi bi-lightbulb"></i> Recomendaciones priorizadas</h3></div>
    <div class="card-body">
        <?php if (empty($analysis['recommendations'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> ¡Excelente! Tu página cumple con todos los criterios competitivos.
            </div>
        <?php else: ?>
            <?php foreach ($analysis['recommendations'] as $r): ?>
                <?php
                $sevColor = [
                    'critical' => 'danger',
                    'high'     => 'warning',
                    'medium'   => 'info',
                    'low'      => 'secondary',
                ][$r['severity'] ?? 'low'] ?? 'secondary';

                $sevIcon = [
                    'critical' => 'exclamation-octagon',
                    'high'     => 'exclamation-triangle',
                    'medium'   => 'info-circle',
                    'low'      => 'lightbulb',
                ][$r['severity'] ?? 'low'] ?? 'lightbulb';
                ?>
                <div class="alert alert-<?= $sevColor ?> mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-<?= $sevIcon ?>"></i>
                                <strong>#<?= (int)($r['priority'] ?? 0) ?> · <?= e($r['title'] ?? '') ?></strong>
                                <span class="badge badge-<?= $sevColor ?>"><?= e($r['severity'] ?? '') ?></span>
                                <span class="badge badge-ghost">Impacto: <?= e($r['impact'] ?? '') ?></span>
                            </div>
                            <p class="mb-0 text-small"><?= e($r['description'] ?? '') ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>