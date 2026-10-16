<?php
declare(strict_types=1);

use FloCMS\CLI\Database\Migration;
use FloCMS\Core\Database;

/**
 * The settings table read by flocms-core (Config::getSetting(), offline_mode
 * written by `php flo down`), flocms-api's maintenance middleware and
 * flocms-admin's settings pages. Core's own settings are one row per language
 * (param, lang); flocms-admin stores one row per key with an empty lang and a
 * JSON value. IF NOT EXISTS: existing sites keep their table.
 */
return new class extends Migration
{
    public bool $transactional = false;

    public function up(Database $db): void
    {
        $db->query('CREATE TABLE IF NOT EXISTS `settings` (
            `param` VARCHAR(190) NOT NULL,
            `value` TEXT NULL,
            `lang` VARCHAR(10) NOT NULL DEFAULT \'\',
            PRIMARY KEY (`param`, `lang`)
        )' . $this->tableOptions($db));
    }

    public function down(Database $db): void
    {
        $db->query('DROP TABLE IF EXISTS `settings`');
    }
};
