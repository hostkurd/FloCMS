<?php
declare(strict_types=1);

namespace App\Api\Controllers;

use App\Api\Resources\UserResource;
use FloCMS\Api\Controller;
use FloCMS\Api\OpenApi\ResponseSchema;
use FloCMS\Api\OpenApi\Summary;
use FloCMS\Api\OpenApi\Tag;
use FloCMS\Api\Pagination\PageRequest;
use FloCMS\Api\Query\QueryOptions;
use FloCMS\Api\ResponseFactory;
use FloCMS\Core\App;
use FloCMS\Core\Database;
use FloCMS\Core\Http\Request;
use FloCMS\Core\Http\Response;

/**
 * Example resource controller (admins with the users.manage permission):
 *
 *   GET /api/v1/users?page=2&per_page=50&sort=-id&filter[role]=2&filter[status][in]=1,2
 *   GET /api/v1/users/5
 */
#[Tag('Users')]
final class UsersController extends Controller
{
    #[Summary('List users', 'Paginated. Sort by id or full_name; filter by role and status.')]
    public function index(Request $request): Response
    {
        $page = PageRequest::fromRequest($request, defaultPerPage: 25, maxPerPage: 100);
        $options = QueryOptions::fromRequest(
            $request,
            sortable: ['id', 'full_name'],
            filterable: ['role' => ['eq', 'in'], 'status' => ['eq', 'in']],
            defaultSort: 'id',
        );

        $result = $options
            ->applyTo($this->db()->table('users')->select(['id', 'full_name', 'email', 'role', 'status', 'is_verified']))
            ->paginate($page->page, $page->perPage);

        return ResponseFactory::paginated($result, $request, UserResource::class);
    }

    #[Summary('Show a user')]
    #[ResponseSchema(404, description: 'User not found')]
    public function show(int $id): UserResource
    {
        $user = $this->db()->table('users')->where('id', '=', $id)->first();
        if ($user === null) {
            $this->fail('User not found.', 404);
        }

        return UserResource::make($user);
    }

    private function db(): Database
    {
        return App::db() ?? $this->fail('The database is not configured.', 503);
    }
}
