<?php

declare(strict_types=1);

/**
 * Script de préchargement OPcache d'EcoShield-Minimal.
 *
 * Exécuté UNE SEULE FOIS au démarrage du conteneur (chemin pointé par
 * `opcache.preload` dans le Dockerfile). Il compile les classes du framework
 * Waffle en mémoire partagée afin que chaque worker — puis chaque invocation
 * Lambda « chaude » — démarre sans repayer le coût de compilation.
 */

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

(static function (): void {
    // Racine du projet (/app dans le conteneur).
    $projectDir = dirname(__DIR__);

    // On cible le framework complet pour le compiler en mémoire partagée.
    $roots = [
        $projectDir . '/vendor/waffle-commons',
    ];

    // Parcours récursif et compilation fichier par fichier.
    $compile = static function (string $directory) use (&$compile): void {
        if (!is_dir($directory)) {
            return;
        }

        $handle = opendir($directory);
        if ($handle === false) {
            return;
        }

        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;

            if (is_dir($path)) {
                $compile($path);
                continue;
            }

            if (!str_ends_with($path, '.php')) {
                continue;
            }

            // On exclut les tests et binaires : inutiles et parfois non chargeables
            // hors de leur contexte d'exécution.
            if (str_contains($path, '/tests/') || str_contains($path, '/bin/')) {
                continue;
            }

            try {
                opcache_compile_file($path);
            } catch (\Throwable $throwable) {
                // Échec volontairement silencieux : certaines classes ne peuvent
                // pas être compilées hors contexte. On journalise sans interrompre.
                error_log("Échec du préchargement pour {$path} : " . $throwable->getMessage());
            }
        }

        closedir($handle);
    };

    foreach ($roots as $root) {
        $compile($root);
    }
})();
