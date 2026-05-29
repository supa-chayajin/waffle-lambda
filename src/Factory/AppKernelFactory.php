<?php

declare(strict_types=1);

namespace Wfl\Factory;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\NullLogger;
use Waffle\Commons\Config\Config;
use Waffle\Commons\Container\Container;
use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Commons\Contracts\Core\KernelInterface;
use Waffle\Commons\ErrorHandler\Middleware\ErrorHandlerMiddleware;
use Waffle\Commons\ErrorHandler\Renderer\JsonErrorRenderer;
use Waffle\Commons\Http\Factory\ResponseFactory;
use Waffle\Commons\Pipeline\CoreRoutingMiddleware;
use Waffle\Commons\Pipeline\MiddlewareStack;
use Waffle\Commons\Routing\Router;
use Waffle\Commons\Security\Security;
use Wfl\Kernel\AppKernel;

/**
 * Code d'assemblage minimal d'EcoShield-Minimal.
 *
 * Monte les implémentations concrètes des composants Waffle dans l'ordre
 * canonique attendu par le Kernel, puis injecte les quatre dépendances
 * obligatoires (conteneur, configuration, sécurité, pipeline) AVANT que le
 * Runtime ne déclenche boot()/configure().
 *
 * Pipeline minimal demandé :
 *   ErrorHandlerMiddleware (prepend)
 *     -> CoreRoutingMiddleware
 *       -> ControllerDispatcher  (handler terminal auto-câblé par configure())
 */
final class AppKernelFactory
{
    /**
     * Construit le Kernel entièrement assemblé et prêt à être amorcé.
     */
    public static function create(string $env = Constant::ENV_PROD, bool $debug = false): KernelInterface
    {
        /** @var string $root */
        $root = APP_ROOT;
        $configDir = $root . DIRECTORY_SEPARATOR . APP_CONFIG;

        // 1. Conteneur PSR-11 concret (paquet waffle-commons/container).
        $container = new Container();

        // 2. Factory PSR-17 de réponses : requise par l'ErrorHandler ET injectée
        //    dans les contrôleurs par le ControllerDispatcher (pour jsonResponse()).
        $responseFactory = new ResponseFactory();
        $container->set(ResponseFactoryInterface::class, $responseFactory);

        // 3. Configuration (paquet waffle-commons/config) : analyse config/app.yaml
        //    avec interpolation des variables d'environnement (« %env(...)% »).
        //    L'environnement processus (Lambda / Docker) alimente le registre.
        $config = new Config(configDir: $configDir, environment: $env, env: getenv());

        // 4. Sécurité (paquet waffle-commons/security) : OBLIGATOIRE —
        //    AbstractKernel::configure() avorte si elle est absente. En mode
        //    minimal, aucun SecurityMiddleware n'est ajouté au pipeline ; seul le
        //    niveau (waffle.security.level) reste déclaré dans la configuration.
        $security = new Security($config);

        // 5. Pipeline PSR-15 (paquet waffle-commons/pipeline).
        $stack = new MiddlewareStack();

        // 5a. Gestionnaire d'erreurs : « prepend »-é afin d'englober tout le
        //     pipeline et de transformer chaque exception en réponse JSON propre.
        $errorRenderer = new JsonErrorRenderer($responseFactory, $debug);
        $stack->prepend(
            middleware: new ErrorHandlerMiddleware(renderer: $errorRenderer, logger: new NullLogger()),
        );

        // 5b. Routage : le Router découvre les attributs #[Route] dans le dossier
        //     des contrôleurs, puis le middleware relie la route résolue au
        //     pipeline. Sans ce chemin de configuration, aucune route n'existe.
        $controllersPath = $config->getString(key: 'waffle.paths.controllers');
        if (is_string($controllersPath)) {
            $router = new Router($root . DIRECTORY_SEPARATOR . $controllersPath);
            $router->boot(container: $container);
            $stack->add(middleware: new CoreRoutingMiddleware($router, $responseFactory));
        }

        // 6. Kernel : injection des quatre dépendances obligatoires via setters.
        //    Le handler terminal (ControllerDispatcher + ReflectionService +
        //    ArgumentResolver) est auto-câblé par AbstractKernel::configure().
        $kernel = new AppKernel();
        $kernel->setConfiguration($config);
        $kernel->setSecurity($security);
        $kernel->setContainerImplementation($container);
        $kernel->setMiddlewareStack($stack);

        return $kernel;
    }
}
