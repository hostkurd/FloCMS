<?php
declare(strict_types=1);

namespace App\Api\Resources;

use FloCMS\Api\Resources\JsonResource;
use FloCMS\Core\Http\Request;

/**
 * What the API shows of a user. Columns not listed here (password, token,
 * login, ...) never reach the response.
 */
final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->full_name,
            'email' => $this->email,
            'role' => (int) $this->role,
            'status' => (int) $this->status,
            'verified' => (bool) $this->is_verified,
        ];
    }
}
