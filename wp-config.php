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
define( 'AUTH_KEY',          '^7<i8iD(iv%>>;wARhU;a,=9@}S&tj2NE;P[+77 pv?#+Qaz6yZ3+j0*)po^F,I8' );
define( 'SECURE_AUTH_KEY',   'Q}Ae2)WCAh)`ipofMBCQX=S!|o+DoU`M+zilQFv;WJe4Ha<8j_:dh;V$8+vLN]ME' );
define( 'LOGGED_IN_KEY',     'zK2)hhn7RTx#h~&-7j &Ic17F7Vs7@..}49S}AgKiPTknMsh:-F H>@h%h:]%evt' );
define( 'NONCE_KEY',         'r49I]FV%H!G|A]+SI3k&_8lKGR+~* / J_vzo!o=V|vINZYg^<TMfn}%^H9vg8!5' );
define( 'AUTH_SALT',         '`Kk%Wc=n^+_S0HduDV-whQEO2OB@yX9hCvTIO!L(0-VpKaGi1c-rZX[v&-ENsD+D' );
define( 'SECURE_AUTH_SALT',  '| wkU)}:V6Y,H/8aI{x|Se>$_ 0@c(*tmso5=YQKE;GrD|[3a7ZZ6^fW5%IwetwK' );
define( 'LOGGED_IN_SALT',    'Y3LDCEKF20S>~ 2[K{6 tp%46o@hPOfRj[>(Z+$Vu,0.U6yk9RD8wL{hd0f3Fh) ' );
define( 'NONCE_SALT',        '#)3+QL`7ON0AF`,sTWqAJc~2%>y/Q V~-}Ap!7 pMj.-ff:n@E^I|xaM,F,,78Qo' );
define( 'WP_CACHE_KEY_SALT', '-(]SmMU5STBxe;NGgJY<,U(^r](/!J9tl=g|?V)%>+{X7xeQ,0g;Y$njF&[4Bj]7' );


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
if ( ! defined( 'WP_DEBUG' ) ) {
        //define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
