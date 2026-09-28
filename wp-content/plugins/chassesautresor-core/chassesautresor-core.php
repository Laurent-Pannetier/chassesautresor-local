<?php
/**
 * Plugin Name: Chasses au Tresor Core
 * Description: Fonctionnalites metier de chassesautresor.com.
 * Version: 0.1.0
 * Text Domain: chassesautresor-com
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Points/PointsRepository.php';

if (!class_exists('PointsRepository', false)) {
    class_alias(ChassesAuTresor\Core\Points\PointsRepository::class, 'PointsRepository');
}
