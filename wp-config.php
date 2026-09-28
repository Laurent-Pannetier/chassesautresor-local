<?php



/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'Xg0)fL>[5sU6ayz7+vT`_LzU?Fx$5Nh+ICk_@urwGXU7{m?)|Y^9M;q6?h5! #_o' );
define( 'SECURE_AUTH_KEY',   '[.?oaFU85-w2Qe-A-DaP<~cG-6CU9{Y%%h~S5EkoX`=3&`J%,9d#{A;sQBMH5O92' );
define( 'LOGGED_IN_KEY',     '>;moA3OK{/W{z!bouI|wlwb+#/dK}ZXyB--v36S_?lj|hl^Cmuj-Z0[NeH.IHI;n' );
define( 'NONCE_KEY',         'PXLMs`.q[l5 -%g!i[^,OKGT(76G*^$uwz)Q>JL(7);/#n{_)=1P|fC>zQ0u^Eqq' );
define( 'AUTH_SALT',         'y)QNrbxI+o;@{mZ>OU7p}+U>8|KVdeuLYWl3.JL.{gJy=l9GpCQ7u{2Ex)W4J|g|' );
define( 'SECURE_AUTH_SALT',  '7bqoz!;17ea4-E]hVh@{*6#YrMy:m%YFcwV`y2V|ARjW}*<$!,e}e%3<5w%ze|H;' );
define( 'LOGGED_IN_SALT',    'JYf~r>=(%_if?1|ERG.U{Vh-z!}OzKcpqb]rxnKvh;X`)(RH/,e<Y,VAA$Xk>4ZK' );
define( 'NONCE_SALT',        'oRR@UdPY5&tSwT)@Fg6!=LJ+LnD}P* |FERJa)if2CEKS0!wp7%hYF|q;v#.cP}u' );
define( 'WP_CACHE_KEY_SALT', '77YYk[}O[k&H,D;$yO{rSXqdaLi(*WDaRmjL?7F(T fen/5rp}BS0Q<MBFSnJ%:a' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
	define( 'WP_ENVIRONMENT_TYPE', 'development' ); // ajustez selon votre env
}

if ( WP_ENVIRONMENT_TYPE === 'development' ) {
	define( 'WP_DEBUG', false );
	define( 'WP_DEBUG_LOG', false );        // écrit dans wp-content/debug.log
	define( 'WP_DEBUG_DISPLAY', false );    // affiche à l’écran en dev
	@ini_set( 'display_errors', 1 );
	define( 'SCRIPT_DEBUG', true );        // charge les assets non minifiés
	// define( 'SAVEQUERIES', true );      // à activer ponctuellement (lent)
} else {
	define( 'WP_DEBUG', false );            // active le log sans affichage
	define( 'WP_DEBUG_LOG', false );
	define( 'WP_DEBUG_DISPLAY', false );
	@ini_set( 'display_errors', 0 );
	define( 'SCRIPT_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', '6383c245822a55601fee3e852ab81908' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
