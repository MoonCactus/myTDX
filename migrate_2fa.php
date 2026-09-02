<?php
/**
 * Migration script to add 2FA configuration parameters to existing myTDX installations
 * 
 * This script adds the totp_enabled and totp_secret parameters to db/config.php
 * if they don't already exist.
 * 
 * Usage: php migrate_2fa.php
 */

require_once('common.php');
require_once('db/config.php');

// Load existing config
Config::loadConfig($config);

// Check if 2FA params already exist
$needsMigration = false;

if (!isset(Config::$config['totp_enabled'])) {
    Config::set('totp_enabled', 0);
    $needsMigration = true;
}

if (!isset(Config::$config['totp_secret'])) {
    Config::set('totp_secret', '');
    $needsMigration = true;
}

if ($needsMigration) {
    Config::save();
    echo "Migration completed. 2FA parameters added to config.\n";
    echo "You can now enable 2FA in Settings.\n";
} else {
    echo "2FA parameters already exist in config. No migration needed.\n";
}

?>
