<?php

/**
 * framework.php - Main ti-framework initialization instructions
 *
 * Copyright (c) 2010, e01 <dimitrov.adrian@gmail.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *  - Neither the name of Incutio Ltd. nor the names of its contributors
 *    may be used to endorse or promote products derived from this software
 *    without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

// Set the framework version.
define( 'TI_FW_VERSION', '0.9.5' );

// Start the timer.
define( 'TI_TIMER_START', microtime( TRUE ) );

// Check for PHP version.
if ( version_compare(PHP_VERSION, '5.2.0', '<') ) {
  die( 'REQUIRE PHP >= 5.2' );
}

// Fix server vars, all credits to WordPress team.
$_SERVER = array_merge( array('SERVER_SOFTWARE' => '', 'REQUEST_URI' => ''), $_SERVER );

// Fix for IIS when running with PHP ISAPI
if ( empty( $_SERVER['REQUEST_URI'] ) ||
    ( php_sapi_name() != 'cgi-fcgi' && preg_match( '/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE'] ) ) ) {

  // IIS Mod-Rewrite
  if ( isset( $_SERVER['HTTP_X_ORIGINAL_URL'] ) ) {
    $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
  }
  // IIS Isapi_Rewrite
  elseif ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) ) {
    $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
  }
  else {
    // Use ORIG_PATH_INFO if there is no PATH_INFO
    if ( !isset( $_SERVER['PATH_INFO'] ) && isset( $_SERVER['ORIG_PATH_INFO'] ) ) {
      $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
    }

    // Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
    if ( isset( $_SERVER['PATH_INFO'] ) ) {
      if ( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'] ) {
        $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
      }
      else {
        $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
      }
    }

    // Append the query string if it exists and isn't null
    if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
      $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
    }
  }
}

// Fix for PHP as CGI hosts that set SCRIPT_FILENAME to something ending in php.cgi for all requests
if ( isset( $_SERVER['SCRIPT_FILENAME'] ) && ( strpos( $_SERVER['SCRIPT_FILENAME'], 'php.cgi' )
    == strlen( $_SERVER['SCRIPT_FILENAME'] ) - 7 ) ) {
  $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
}

// Fix for Dreamhost and other PHP as CGI hosts
if ( strpos( $_SERVER['SCRIPT_NAME'], 'php.cgi' ) !== FALSE ) {
  unset( $_SERVER['PATH_INFO'] );
}

// Fix empty PHP_SELF
$PHP_SELF = $_SERVER['PHP_SELF'];
if ( empty( $PHP_SELF ) ) {
  $_SERVER['PHP_SELF'] = $PHP_SELF = preg_replace( '/(\?.*)?$/', '', $_SERVER["REQUEST_URI"] );
}

// Set path framework.
define( 'TI_PATH_FRAMEWORK', dirname(__FILE__) );

// Set default home url.
defined( 'TI_HOME' ) or define( 'TI_HOME', 'index' );
if ( $_SERVER['REQUEST_URI'] == '/' ) {
  $_SERVER['REQUEST_URI'] = TI_HOME;
}

// Exit if favicon request detected.
if ( '/favicon.ico' == $_SERVER['REQUEST_URI'] ) {
  header( 'Content-Type: image/vnd.microsoft.icon' );
  header( 'Content-Length: 0' );
  exit;
}

if ( defined('TI_DOCUMENTATION') && is_string( TI_DOCUMENTATION ) && strcmp( $_SERVER['REQUEST_URI'], '/' . TI_DOCUMENTATION ) === 0 ) {
  include TI_PATH_FRAMEWORK . '/documentation.php';
  exit;
}

// Set appsecret salt.
defined( 'TI_APP_SECRET' ) or define( 'TI_APP_SECRET', md5(__FILE__) );

// Set the debugging mode to false.
defined( 'TI_DEBUG_MODE') or define( 'TI_DEBUG_MODE', FALSE );

define( 'TI_IS_CLI', (php_sapi_name() == 'cli' || empty($_SERVER['REMOTE_ADDR'])) );

// Detect application path.
if ( !defined('TI_PATH_APP') ) {
  if ( TI_IS_CLI ) {
    $i = get_included_files();
    define( 'TI_PATH_APP', dirname( array_shift($i) ) . '/application/' );
    unset( $i );
  }
  else {
    define('TI_PATH_APP', dirname( $_SERVER['SCRIPT_FILENAME'] )  . '/application/' );
  }

  if (!TI_PATH_APP) {
    die( 'Application path not defined.' );
  }
}

// Detect the webpath.
defined( 'TI_PATH_WEB' ) or define( 'TI_PATH_WEB', '/' . trim( dirname( $_SERVER['SCRIPT_NAME'] ), '/' ) );

// Internationalization.
defined( 'TI_LOCALE' )     or define( 'TI_LOCALE', 'en_US' );
defined( 'TI_TIMEZONE' )   or define( 'TI_TIMEZONE', 'GMT' );

// Set MVC folders, if they are not set already by the config file.
defined( 'TI_FOLDER_MODEL' )        or define( 'TI_FOLDER_MODEL', 'class' );
defined( 'TI_FOLDER_VIEW' )         or define( 'TI_FOLDER_VIEW', 'html' );
defined( 'TI_FOLDER_CONTROLLER' )   or define( 'TI_FOLDER_CONTROLLER', 'www' );
defined( 'TI_EXT_MODEL' )           or define( 'TI_EXT_MODEL', '.php' );
defined( 'TI_EXT_VIEW' )            or define( 'TI_EXT_VIEW', '.html' );
defined( 'TI_EXT_CONTROLLER' )      or define( 'TI_EXT_CONTROLLER', '.php' );
defined( 'TI_AUTORENDER' )          or define( 'TI_AUTORENDER', FALSE );
defined( 'TI_RULES_CACHE' )         or define( 'TI_RULES_CACHE', FALSE );

// Caches.
defined( 'TI_FOLDER_LOCALE' )       or define( 'TI_RULES_CACHE', 'locale' );
defined( 'TI_FOLDER_CACHE' )        or define( 'TI_FOLDER_CACHE', 'cache' );

// Correct the ip.
define( 'TI_IP', !empty($_SERVER['HTTP_CLIENT_IP'])
    ? $_SERVER['HTTP_CLIENT_IP']
    : ( !empty($_SERVER['HTTP_X_FORWARDED_FOR'])
        ? $_SERVER['HTTP_X_FORWARDED_FOR']
        : ( !empty($_SERVER['REMOTE_ADDR'])
            ? $_SERVER['REMOTE_ADDR']
            : '000.000.000.000'
        )
    ));

// Set debugging mode.
ini_set( 'log_errors', TRUE );
if ( TI_DEBUG_MODE ) {
  error_reporting( E_ALL );
  ini_set( 'display_errors', 1 );
  ini_set( 'display_startup_errors', TRUE );
}
else {
  error_reporting( 0 );
  ini_set( 'display_errors', 'stderr' );
  ini_set( 'display_startup_errors', FALSE );
}

// Reset some of PHP's configurations.
ini_set( 'mbstring.internal_encoding', 'UTF-8' );
ini_set( 'mbstring.func_overload', '7' );
ini_set( 'allow_url_fopen', '0' );
ini_set( 'register_globals', '0' );
ini_set( 'arg_separator.output', '&amp;' );
ini_set( 'url_rewriter.tags', '' );
ini_set( 'magic_quotes_gpc', '0' );
ini_set( 'magic_quotes_runtime', '0' );
ini_set( 'magic_quotes_sybase', '0' );
ini_set( 'session.use_trans_sid', 0 );
ini_set( 'session.cache_limiter', 'none' );
ini_set( 'session.gc_probability', 0 );
ini_set( 'session.use_only_cookies', 1 );

// Include core functions.
require TI_PATH_FRAMEWORK . '/functions.php';

// Instantinate session.
if ( !session_id() ) {
  session_start();
}
if ( ifsetor( $_SESSION['_ti_client'] ) !== md5( TI_IP . $_SERVER['HTTP_USER_AGENT'] )
    || ifsetor( $_SESSION['_ti_id'] ) !== md5( TI_APP_SECRET . session_id() ) ) {

  $_SESSION['_ti_client'] = md5( TI_IP . $_SERVER['HTTP_USER_AGENT'] );
  $_SESSION['_ti_id'] = md5( TI_APP_SECRET . session_id() );
}
session_regenerate_id();

// Set default timezone.
date_default_timezone_set( TI_TIMEZONE );

// Set main locale.
setlocale( LC_CTYPE, TI_LOCALE );

// Determine if the request is ajax or not.
define( 'TI_IS_AJAX', ( strtolower( ifsetor( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) == 'xmlhttprequest') );

// Set ti error handler.
set_error_handler( 'ti_error_handler' );

// Unregister globals.
unset( $_REQUEST, $_ENV, $HTTP_RAW_POST_DATA, $GLOBALS, $http_response_header, $argc, $argv );

// Register autoloader function.
spl_autoload_register( 'ti_autoloader' );

// Prevent outputing from the __application.
if ( !ob_list_handlers() ) {
  ob_start();
}

// Check for __application.php
if ( is_readable(TI_PATH_APP . '/__application.php') ) {
  include TI_PATH_APP . '/__application.php';
}

// Cleaning the buffer
ob_end_clean();

// Fire the init hook.
// @fire init
do_hook( 'init' );

// Let boot the app.
$Application = new Application( $_SERVER['REQUEST_URI'] );

// Fire the shutdown hook.
// @fire shutdown
do_hook( 'shutdown' );
