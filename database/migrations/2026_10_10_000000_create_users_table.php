<?php
declare(strict_types=1);

use FloCMS\CLI\Database\Migration;
use FloCMS\Core\Database;

/**
 * The users table used by models/UsersModel.php and the admin panel.
 * `php flo migrate` creates it on a new site; then create the first admin
 * with `php flo user:create`. IF NOT EXISTS: existing sites keep their table.
 */
return new class extends Migration
{
    public bool $transactional = false;

    public function up(Database $db): void
    {
        $db->query('CREATE TABLE IF NOT EXISTS `users` (
            ' . $this->id($db) . ',
            `full_name` VARCHAR(190) NOT NULL DEFAULT \'\',
            `login` VARCHAR(190) NOT NULL DEFAULT \'\',
            `password` VARCHAR(255) NOT NULL DEFAULT \'\',
            `email` VARCHAR(190) NOT NULL UNIQUE,
            `image` VARCHAR(255) NOT NULL DEFAULT \'\',
            `address` VARCHAR(255) NOT NULL DEFAULT \'\',
            `phone` VARCHAR(50) NOT NULL DEFAULT \'\',
            `gender` VARCHAR(20) NOT NULL DEFAULT \'\',
            `role` TINYINT NOT NULL DEFAULT 0,
            `status` TINYINT NOT NULL DEFAULT 2,
            `is_verified` TINYINT NOT NULL DEFAULT 0,
            `verify_type` TINYINT NOT NULL DEFAULT 0,
            `token` VARCHAR(255) NOT NULL DEFAULT \'\'
        )' . $this->tableOptions($db));
    }

    public function down(Database $db): void
    {
        $db->query('DROP TABLE IF EXISTS `users`');
    }
};
