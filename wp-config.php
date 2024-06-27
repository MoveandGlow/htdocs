<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'Move&Glow' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'dsI^&1Q?V7=.QBCAJ+|QRoUq+PA.m_qLR%UAVH#XYfE~u:%|Y1q-1/BsT84gyj]{' );
define( 'SECURE_AUTH_KEY',  'LX,mJl;o^Z%*W<SzrV#x0dUrC:P8 *@`)a}9HV6}:B&gI1S9|wQcE|6R)C+.G!X{' );
define( 'LOGGED_IN_KEY',    'E(CaUI|=0q+:pICk_/PzsQ58,zn}NEgqb}.ARf;~9e4s4M>+`:eD{_~&G.Bd{jh5' );
define( 'NONCE_KEY',        'V18KCC8H?W0XpK;5U.~,Ev?a~(>k[aoyC:hF14Mqu[|?R-LqAK|m&?+qg?+bSvMR' );
define( 'AUTH_SALT',        '{ *Epszoutu2ebha)VPT@eZii}6{`RRBAWEzvdR-E~pf0|0wSC-9q#7`^^q!4n[_' );
define( 'SECURE_AUTH_SALT', 's1EEEuD2Q-{%zacxcJ6< im^7~^+n%TBg+hvrYDc/WHmlG$(2r=[|4-U[(Hqcl#p' );
define( 'LOGGED_IN_SALT',   '-;f)_Qy+e4ZiXhohv{d!<B9}a@_.i7%/X,^qSvhYz?JO]SjWNG2W0%}rh`!- +Yz' );
define( 'NONCE_SALT',       'x@ljZtXh-mM,N~X9AF_cZLA]`v,4@7z89S BIV^!IX,=$^,%#.xbzw>9d6%4YF%e' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
