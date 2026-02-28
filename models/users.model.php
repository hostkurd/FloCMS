<?php

class UsersModel extends Model
{
    /**
     * Hash passwords using PHP's modern password API.
     */
    private function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    /**
     * Verify a plain password against a stored hash.
     */
    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    private function objToArray(?object $row)
    {
        return $row ? (array) $row : false;
    }

    private function objsToArrays(array $rows): array
    {
        return array_map(fn ($r) => (array) $r, $rows);
    }

    public function save($data, $token, $id = null)
    {
        $fullname  = trim((string)($data['fullname'] ?? ''));
        $email     = trim((string)($data['email'] ?? ''));
        $phone     = str_replace(' ', '', (string)($data['phone'] ?? ''));
        $gender    = (string)($data['gender'] ?? '');
        $address   = (string)($data['address'] ?? '');
        $login     = trim((string)($data['login'] ?? ''));
        $password  = (string)($data['password'] ?? '');
        $role      = (int)($data['role'] ?? 0);
        $imagePath = (string)($data['imagePath'] ?? '');

        // INSERT
        if ($id === null) {
            if ($password === '') {
                // You can also throw an exception if your app expects it
                return false;
            }

            $insert = [
                'full_name'   => $fullname,
                'login'       => $login,
                'password'    => $this->hashPassword($password),
                'email'       => $email,
                'image'       => $imagePath,
                'address'     => $address,
                'phone'       => $phone,
                'gender'      => $gender,
                'role'        => $role,
                'status'      => 2,
                'is_verified' => 0,
                'token'       => (string)$token,
            ];

            return $this->db->table('users')->insert($insert);
        }

        // UPDATE
        $id = (int)$id;

        $update = [
            'full_name' => $fullname,
            'login'     => $login,
            'email'     => $email,
            'address'   => $address,
            'phone'     => $phone,
            'gender'    => $gender,
            'role'      => $role,
        ];

        // Only update password if provided
        if ($password !== '') {
            $update['password'] = $this->hashPassword($password);
        }

        if ($imagePath !== '') {
            $update['image'] = $imagePath;
        }

        return $this->db->table('users')->where('id', '=', $id)->update($update);
    }

    public function getByLogin($login)
    {
        $row = $this->db->table('users')->where('login', '=', (string)$login)->first();
        return $this->objToArray($row);
    }

    public function getByEmail($email)
    {
        $row = $this->db->table('users')->where('email', '=', (string)$email)->first();
        return $this->objToArray($row);
    }

    /**
     * Login helper: verify using password_verify only.
     * Call this from your controller.
     */
    public function authenticate(string $login, string $plainPassword)
    {
        $user = $this->getByLogin($login);
        if (!$user) return false;

        $hash = (string)($user['password'] ?? '');
        if ($hash === '') return false;

        if (!$this->verifyPassword($plainPassword, $hash)) {
            return false;
        }

        // Optional: rehash if PHP updates default algorithm/cost
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $newHash = $this->hashPassword($plainPassword);
            $this->db->table('users')->where('id', '=', (int)$user['id'])->update([
                'password' => $newHash
            ]);
            $user['password'] = $newHash;
        }

        return $user; // return user array on success
    }

    public function updateUser($data, $email)
    {
        $login    = trim((string)($data['login'] ?? ''));
        $password = (string)($data['password'] ?? '');

        $update = ['login' => $login];

        if ($password !== '') {
            $update['password'] = $this->hashPassword($password);
        }

        return $this->db->table('users')->where('email', '=', (string)$email)->update($update);
    }

    public function listUsers($limit, $pageid = 1, $keyword = null)
    {
        $pageid = max(1, (int)$pageid);
        $limit  = max(1, (int)$limit);
        $offset = ($pageid - 1) * $limit;

        $q = $this->db->table('users')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->offset($offset);

        if ($keyword !== null && trim((string)$keyword) !== '') {
            $kw = '%' . trim((string)$keyword) . '%';

            $q->where('full_name', 'LIKE', $kw)
              ->orWhere('email', 'LIKE', $kw)
              ->orWhere('phone', 'LIKE', $kw);
        }

        $rows = $q->get();
        return $this->objsToArrays($rows);
    }

    public function getByID($id)
    {
        $id = (int)$id;
        $row = $this->db->table('users')->where('id', '=', $id)->first();
        return $this->objToArray($row);
    }

    public function delete($id): bool
    {
        $id = (int)$id;
        return $this->db->table('users')->where('id', '=', $id)->delete();
    }

    public function getTotal(): int
    {
        return $this->db->table('users')->count();
    }

    public function isUserTokenExist($token): bool
    {
        $row = $this->db->table('users')->where('token', '=', (string)$token)->first();
        return (bool) $row;
    }

    public function isUserExist($id): bool
    {
        $id = (int)$id;
        $row = $this->db->table('users')->where('id', '=', $id)->first();
        return (bool) $row;
    }

    public function verifyUser($token, $verifyType = 'email'): bool
    {
        $verify_type = match ((string)$verifyType) {
            'email'  => 1,
            'manual' => 2,
            default  => 0,
        };

        return $this->db->table('users')
            ->where('token', '=', (string)$token)
            ->update([
                'is_verified' => 1,
                'verify_type' => $verify_type,
                'status'      => 1,
                'token'       => '',
            ]);
    }

    public function suspendUser($id): bool
    {
        $id = (int)$id;
        return $this->db->table('users')->where('id', '=', $id)->update(['status' => 3]);
    }

    public function unsuspendUser($id): bool
    {
        $id = (int)$id;
        return $this->db->table('users')->where('id', '=', $id)->update(['status' => 1]);
    }
}