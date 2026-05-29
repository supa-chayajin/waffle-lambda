<?php

declare(strict_types=1);

use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Commons\Runtime\WaffleRuntime;
use Wfl\Factory\AppKernelFactory;

// Chargement de l'autoloader Composer (framework Waffle + code applicatif).
require_once __DIR__ . '/../vendor/autoload.php';

// Racine applicative et dossier de configuration : résolus UNE SEULE FOIS au
// démarrage du worker, jamais par requête (mandat de statelessness FrankenPHP).
define('APP_ROOT', realpath(path: dirname(path: __DIR__)));
const APP_CONFIG = 'config';

// Lecture de l'environnement processus SANS superglobales ($_ENV / $_SERVER
// sont proscrits). getenv() suffit : Lambda et Docker injectent ces valeurs au
// lancement du conteneur.
$env = getenv(Constant::APP_ENV) ?: Constant::ENV_PROD;
$debug = filter_var(getenv(Constant::APP_DEBUG), FILTER_VALIDATE_BOOL);
$maxRequests = (int) (getenv('MAX_REQUESTS') ?: 500);

// 1. Assemblage applicatif.
// La Factory monte le conteneur, la configuration, la sécurité, le routeur et
// le pipeline, puis renvoie un Kernel entièrement câblé.
$kernel = AppKernelFactory::create(env: $env, debug: $debug);

// 2. Exécution résidente (runtime agnostique).
// Le Runtime amorce le Kernel UNE fois (boot + configure : conteneur et routes
// compilés en mémoire), puis traite les invocations Lambda « chaudes » de
// manière quasi instantanée via la boucle worker FrankenPHP.
new WaffleRuntime()
    ->loop(
        kernel: $kernel,
        maxRequests: $maxRequests,
    )
;
