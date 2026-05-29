<?php

declare(strict_types=1);

namespace Wfl\Controller;

use Psr\Http\Message\ResponseInterface;
use Waffle\Commons\Contracts\Routing\Attribute\Route;
use Waffle\Core\BaseController;
use Waffle\Exception\RenderingException;

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
    #[Route(path: '', name: 'index', methods: ['GET'])]
    public function index(): ResponseInterface
    {
        return $this->jsonResponse(data: ['message' => 'hello waffle']);
    }
}
