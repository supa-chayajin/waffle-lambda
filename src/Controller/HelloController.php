<?php

declare(strict_types=1);

namespace Wfl\Controller;

use Psr\Http\Message\ResponseInterface;
use Waffle\Commons\Contracts\Routing\Attribute\Route;
use Waffle\Commons\Contracts\Routing\Constant as Routing;
use Waffle\Commons\Contracts\Security\Attribute\PublicAccess;
use Waffle\Commons\Contracts\Security\Attribute\Voter;
use Waffle\Commons\Contracts\Security\Csrf\Attribute\RequiresCsrfToken;
use Waffle\Core\BaseController;
use Waffle\Exception\RenderingException;
use Wfl\Dto\Message;
use Wfl\Voter\RestrictedAccess;

/**
 * Contrôleur unique du POC EcoShield-Minimal.
 *
 * L'attribut #[Route] de CLASSE est obligatoire : le RouteParser ignore toute
 * classe qui n'en porte pas. Le préfixe de chemin « / » et le préfixe de nom
 * « app » se combinent avec la route de méthode pour produire la route finale.
 */
#[Route(path: '/', name: 'app')]
final class HelloController extends BaseController
{
    /**
     * Route d'index : GET /.
     *
     * Combinaison classe + méthode :
     *   - chemin : « / » + « » = « / »
     *   - nom    : « app » + « _ » + « index » = « app_index »
     *
     * Renvoie une réponse JSON PSR-7 {"message": "hello waffle"}. La
     * ResponseFactory est injectée par le ControllerDispatcher avant l'appel.
     *
     * @throws RenderingException
     */
    #[PublicAccess]
    #[Route(path: '', methods: [Routing::METHOD_GET], name: 'index')]
    public function index(): ResponseInterface
    {
        return $this->jsonResponse(data: ['message' => 'hello Waffle!']);
    }

    /**
     * Route vérouillée : GET /locked.
     *
     * Combinaison classe + méthode :
     *   - chemin : « / » + « locked » = « /locked »
     *   - nom    : « app » + « _ » + « locked » = « app_locked »
     *
     * Renvoie une réponse JSON PSR-7 {"message": "Waffle is unlocked!"}. La
     * ResponseFactory est injectée par le ControllerDispatcher avant l'appel.
     *
     * @throws RenderingException
     */
    #[RequiresCsrfToken]
    #[Voter(name: RestrictedAccess::class)]
    #[Route(path: 'locked', methods: [Routing::METHOD_POST], name: 'locked')]
    public function locked(Message $message): ResponseInterface
    {
        return $this->jsonResponse(data: [
            'message' => printf(format: 'Waffle says Hi to %s !', values: $message->author),
        ]);
    }
}
