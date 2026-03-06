<?php

namespace FloCMS\Models;

use FloCMS\Core\Model;

class UsersModel extends Model
{
    private function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    private function normalizeUserArray(?object $row)
    {
        if (!$row) {
            return false;
        }

        $data = (array) $row;

        // Normalize DB column names for controller/view usage
        if (isset($data['full_name']) && !isset($data['fullname'])) {
            $data['fullname'] = $data['full_name'];
        }

        return $data;
    }

    private function normalizeUserList(array $rows): array
    {
        return array_map(function ($row) {
            $data = (array) $row;

            if (isset($data['full_name']) && !isset($data['fullname'])) {
                $data['fullname'] = $data['full_name'];
            }

            return $data;
        }, $rows);
    }

    public function save($data, $token, $id = null): bool
    {
        $fullname  = trim((string) ($data['fullname'] ?? ''));
        $email     = trim((string) ($data['email'] ?? ''));
        $phone     = str_replace(' ', '', (string) ($data['phone'] ?? ''));
        $gender    = (string) ($data['gender'] ?? '');
        $address   = (string) ($data['address'] ?? '');
        $login     = trim((string) ($data['login'] ?? ''));
        $password  = (string) ($data['password'] ?? '');
        $role      = (int) ($data['role'] ?? 0);
        $imagePath = (string) ($data['imagePath'] ?? '');

        if ($id === null) {
            if ($password === '') {
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
                'token'       => (string) $token,
            ];

            return (bool) $this->db->table('users')->insert($insert);
        }

        $id = (int) $id;

        $update = [
            'full_name' => $fullname,
            'login'     => $login,
            'email'     => $email,
            'address'   => $address,
            'phone'     => $phone,
            'gender'    => $gender,
            'role'      => $role,
        ];

        if ($password !== '') {
            $update['password'] = $this->hashPassword($password);
        }

        if ($imagePath !== '') {
            $update['image'] = $imagePath;
        }

        return (bool) $this->db->table('users')->where('id', '=', $id)->update($update);
    }

    public function getByLogin(string $login)
    {
        $row = $this->db->table('users')->where('login', '=', $login)->first();
        return $this->normalizeUserArray($row);
    }

    public function getByEmail(string $email)
    {
        $row = $this->db->table('users')->where('email', '=', $email)->first();
        return $this->normalizeUserArray($row);
    }

    public function authenticate(string $login, string $plainPassword)
    {
        $user = $this->getByLogin($login);

        if (!$user) {
            return false;
        }

        $hash = (string) ($user['password'] ?? '');

        if ($hash === '') {
            return false;
        }

        if (!$this->verifyPassword($plainPassword, $hash)) {
            return false;
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $newHash = $this->hashPassword($plainPassword);
            $this->rehashPassword((int) $user['id'], $newHash);
            $user['password'] = $newHash;
        }

        return $user;
    }

    public function rehashPassword(int $id, string $hash): bool
    {
        return (bool) $this->db
            ->table('users')
            ->where('id', '=', $id)
            ->update([
                'password' => $hash,
            ]);
    }

    public function updateUser($data, string $email): bool
    {
        $login    = trim((string) ($data['login'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $update = [
            'login' => $login,
        ];

        if ($password !== '') {
            $update['password'] = $this->hashPassword($password);
        }

        return (bool) $this->db
            ->table('users')
            ->where('email', '=', $email)
            ->update($update);
    }

    public function listUsers($limit, $pageid = 1, $keyword = null): array
    {
        $pageid = max(1, (int) $pageid);
        $limit  = max(1, (int) $limit);
        $offset = ($pageid - 1) * $limit;

        $q = $this->db->table('users')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->offset($offset);

        if ($keyword !== null && trim((string) $keyword) !== '') {
            $kw = '%' . trim((string) $keyword) . '%';

            $q->where('full_name', 'LIKE', $kw)
              ->orWhere('email', 'LIKE', $kw)
              ->orWhere('phone', 'LIKE', $kw);
        }

        $rows = $q->get();
        return $this->normalizeUserList($rows);
    }

    public function getByID($id)
    {
        $id = (int) $id;
        $row = $this->db->table('users')->where('id', '=', $id)->first();
        return $this->normalizeUserArray($row);
    }

    public function delete($id): bool
    {
        $id = (int) $id;
        return (bool) $this->db->table('users')->where('id', '=', $id)->delete();
    }

    public function getTotal(): int
    {
        return (int) $this->db->table('users')->count();
    }

    public function isUserTokenExist($token): bool
    {
        $row = $this->db->table('users')->where('token', '=', (string) $token)->first();
        return (bool) $row;
    }

    public function isUserExist($id): bool
    {
        $id = (int) $id;
        $row = $this->db->table('users')->where('id', '=', $id)->first();
        return (bool) $row;
    }

    public function verifyUser($token, $verifyType = 'email'): bool
    {
        $verify_type = match ((string) $verifyType) {
            'email'  => 1,
            'manual' => 2,
            default  => 0,
        };

        return (bool) $this->db->table('users')
            ->where('token', '=', (string) $token)
            ->update([
                'is_verified' => 1,
                'verify_type' => $verify_type,
                'status'      => 1,
                'token'       => '',
            ]);
    }

    public function suspendUser($id): bool
    {
        $id = (int) $id;
        return (bool) $this->db->table('users')->where('id', '=', $id)->update([
            'status' => 3,
        ]);
    }

    public function unsuspendUser($id): bool
    {
        $id = (int) $id;
        return (bool) $this->db->table('users')->where('id', '=', $id)->update([
            'status' => 1,
        ]);
    }
}