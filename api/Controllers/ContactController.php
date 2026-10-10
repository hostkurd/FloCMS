<?php
declare(strict_types=1);

namespace App\Api\Controllers;

use FloCMS\Api\Controller;
use FloCMS\Api\OpenApi\RequestBody;
use FloCMS\Api\OpenApi\ResponseSchema;
use FloCMS\Api\OpenApi\Summary;
use FloCMS\Api\OpenApi\Tag;
use FloCMS\Api\Validation\ValidatesRequests;
use FloCMS\Core\Http\Request;
use FloCMS\Core\Http\Response;

/**
 * Example: POST /api/v1/contact with a JSON body.
 *
 * Validation errors are a 422 with per-field messages; the route is limited
 * to 5 requests per minute per client ('throttle:forms') and a retry with
 * the same Idempotency-Key gets the first response ('idempotent').
 */
#[Tag('Contact')]
final class ContactController extends Controller
{
    use ValidatesRequests;

    #[Summary('Send a message to the site owner')]
    #[RequestBody([
        'type' => 'object',
        'required' => ['name', 'email', 'message'],
        'properties' => [
            'name' => ['type' => 'string', 'maxLength' => 100],
            'email' => ['type' => 'string', 'format' => 'email'],
            'phone' => ['type' => 'string', 'maxLength' => 30],
            'message' => ['type' => 'string', 'minLength' => 10, 'maxLength' => 5000],
        ],
    ])]
    #[ResponseSchema(202, ['type' => 'object', 'properties' => ['received' => ['type' => 'boolean']]], 'Message accepted')]
    #[ResponseSchema(422, description: 'Validation failed')]
    #[ResponseSchema(429, description: 'Too many requests')]
    public function store(Request $request): Response
    {
        $message = $this->validate($request, [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|min:10|max:5000',
        ]);

        // Store the message or send it by email here, e.g.
        // (new ContactModel())->create($message);

        return $this->success(['received' => true], 202);
    }
}
