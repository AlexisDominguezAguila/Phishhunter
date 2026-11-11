<?php
/**
 * PhishHunter · Estadística para el panel docente
 * Medias, desviación estándar, prueba t de muestras relacionadas y d de Cohen (dz).
 */

function stat_mean(array $x): ?float
{
    return $x ? array_sum($x) / count($x) : null;
}

function stat_sd(array $x): ?float
{
    $n = count($x);
    if ($n < 2) return null;
    $m = stat_mean($x);
    $ss = 0.0;
    foreach ($x as $v) $ss += ($v - $m) ** 2;
    return sqrt($ss / ($n - 1));
}

/** Función beta incompleta regularizada I_x(a,b) (Numerical Recipes). */
function stat_betai(float $a, float $b, float $x): float
{
    if ($x <= 0) return 0.0;
    if ($x >= 1) return 1.0;
    $bt = exp(stat_lgamma($a + $b) - stat_lgamma($a) - stat_lgamma($b) + $a * log($x) + $b * log(1 - $x));
    if ($x < ($a + 1) / ($a + $b + 2)) {
        return $bt * stat_betacf($a, $b, $x) / $a;
    }
    return 1 - $bt * stat_betacf($b, $a, 1 - $x) / $b;
}

function stat_betacf(float $a, float $b, float $x): float
{
    $qab = $a + $b; $qap = $a + 1; $qam = $a - 1;
    $c = 1.0; $d = 1 - $qab * $x / $qap;
    if (abs($d) < 1e-30) $d = 1e-30;
    $d = 1 / $d; $h = $d;
    for ($m = 1; $m <= 200; $m++) {
        $m2 = 2 * $m;
        $aa = $m * ($b - $m) * $x / (($qam + $m2) * ($a + $m2));
        $d = 1 + $aa * $d; if (abs($d) < 1e-30) $d = 1e-30;
        $c = 1 + $aa / $c; if (abs($c) < 1e-30) $c = 1e-30;
        $d = 1 / $d; $h *= $d * $c;
        $aa = -($a + $m) * ($qab + $m) * $x / (($a + $m2) * ($qap + $m2));
        $d = 1 + $aa * $d; if (abs($d) < 1e-30) $d = 1e-30;
        $c = 1 + $aa / $c; if (abs($c) < 1e-30) $c = 1e-30;
        $d = 1 / $d; $del = $d * $c; $h *= $del;
        if (abs($del - 1) < 3e-12) break;
    }
    return $h;
}

function stat_lgamma(float $x): float
{
    $cof = [76.18009172947146, -86.50532032941677, 24.01409824083091, -1.231739572450155, 0.1208650973866179e-2, -0.5395239384953e-5];
    $y = $x; $tmp = $x + 5.5;
    $tmp -= ($x + 0.5) * log($tmp);
    $ser = 1.000000000190015;
    foreach ($cof as $c) $ser += $c / ++$y;
    return -$tmp + log(2.5066282746310005 * $ser / $x);
}

/**
 * Prueba t para muestras relacionadas (post − pre).
 * Devuelve n, medias, diferencia media, t, gl, p bilateral y d de Cohen (dz).
 */
function paired_t(array $pre, array $post): ?array
{
    $n = min(count($pre), count($post));
    if ($n < 2) return null;
    $diff = [];
    for ($i = 0; $i < $n; $i++) $diff[] = $post[$i] - $pre[$i];
    $md = stat_mean($diff);
    $sd = stat_sd($diff);
    $df = $n - 1;
    if (!$sd) {
        return ['n' => $n, 'mean_pre' => stat_mean($pre), 'mean_post' => stat_mean($post), 'diff' => $md,
                'sd_diff' => 0, 't' => null, 'df' => $df, 'p' => $md == 0 ? 1.0 : 0.0, 'dz' => null];
    }
    $t = $md / ($sd / sqrt($n));
    $p = stat_betai($df / 2, 0.5, $df / ($df + $t * $t));
    return ['n' => $n, 'mean_pre' => stat_mean($pre), 'mean_post' => stat_mean($post), 'diff' => $md,
            'sd_diff' => $sd, 't' => $t, 'df' => $df, 'p' => $p, 'dz' => $md / $sd];
}

/**
 * Prueba t de Welch para muestras independientes (a vs. b) y d de Cohen.
 * Útil para comparar la ganancia (post − pre) del grupo experimental y el de control.
 */
function welch_t(array $a, array $b): ?array
{
    $na = count($a); $nb = count($b);
    if ($na < 2 || $nb < 2) return null;
    $ma = stat_mean($a); $mb = stat_mean($b);
    $va = stat_sd($a) ** 2; $vb = stat_sd($b) ** 2;
    $se = sqrt($va / $na + $vb / $nb);
    $pooled = sqrt((($na - 1) * $va + ($nb - 1) * $vb) / ($na + $nb - 2));
    $d = $pooled ? ($ma - $mb) / $pooled : null;
    if (!$se) {
        return ['na' => $na, 'nb' => $nb, 'ma' => $ma, 'mb' => $mb, 'diff' => $ma - $mb, 't' => null, 'df' => $na + $nb - 2, 'p' => $ma == $mb ? 1.0 : 0.0, 'd' => $d];
    }
    $t = ($ma - $mb) / $se;
    $df = (($va / $na + $vb / $nb) ** 2) / ((($va / $na) ** 2) / ($na - 1) + (($vb / $nb) ** 2) / ($nb - 1));
    $p = stat_betai($df / 2, 0.5, $df / ($df + $t * $t));
    return ['na' => $na, 'nb' => $nb, 'ma' => $ma, 'mb' => $mb, 'diff' => $ma - $mb, 't' => $t, 'df' => $df, 'p' => $p, 'd' => $d];
}

function fmt_num($v, int $dec = 2): string
{
    return $v === null ? '—' : number_format((float) $v, $dec, ',', '.');
}

function fmt_p(?float $p): string
{
    if ($p === null) return '—';
    return $p < 0.001 ? '< 0,001' : number_format($p, 3, ',', '.');
}
