<?php

/**
 * library.Application.php - Application class
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

if (!defined('TI_PATH_FRAMEWORK'))
  exit;

// Determine the controller extension lenght.
define( 'TI_EXT_CONTROLLER_N', strlen( TI_EXT_CONTROLLER ) * -1 );

/**
 * Find all routes in the application from controller folder.
 *
 * @access private
 *
 * @param string $adir
 * @param string $rdir
 *
 * @return array
 */
function _ti_application_find_routes_fd($adir, $rdir = '') {
  $files = array();
  if ( is_dir( $adir ) ) {
    $handle = opendir( $adir );
    if ( $handle ) {
      while ( FALSE !== ($file = readdir( $handle )) ) {
        if ( $file{0} !== '.' ) {
          if ( is_dir( $adir . '/' . $file ) ) {
            $files = array_merge( $files, _ti_application_find_routes_fd( $adir . '/' . $file, $rdir . '/' . $file ) );
          }
          elseif ( substr( $file, TI_EXT_CONTROLLER_N ) === TI_EXT_CONTROLLER ) {
            $files[] = $rdir . '/' . substr( $file, 0, TI_EXT_CONTROLLER_N );
          }
        }
      }
      closedir($handle);
    }
  }
  return $files;
}

/**
 * Get all routes in the application
 *
 * @access private
 *
 * @return array
 */
function _ti_application_routes() {

  static $routes = NULL;

  if ( $routes === NULL ) {
    if ( TI_RULES_CACHE && cache_exists( 'ti://rules-cache', TI_RULES_CACHE )) {
      $routes = explode("\n", cache_get( 'ti://rules-cache', TI_RULES_CACHE ));
      return $routes;
    }

    $routes = _ti_application_find_routes_fd( TI_PATH_APP . '/' . TI_FOLDER_CONTROLLER );
    asort( $routes );

    if ( TI_RULES_CACHE ) {
      cache_put( 'ti://rules-cache', implode( "\n", $routes ) );
    }
  }

  return $routes;
}

/**
 * Main Application class, provide a flexible MVC separation.
 */
class Application {

  static private $is_main = TRUE;
  static private $variables = array();
  private $arguments = array();

  /**
   * Load URL when define new object of Application with parameters.
   *
   * @see load() method.
   *
   * @access public
   *
   * @param string $url
   * @param string $return
   *
   * @return Application
   */
  public function __construct($url = '', $return = FALSE) {
    if ( $url ) {
      $this->load( $url, $return );
    }
    return $this;
  }

  /**
   * Load URL and process it.
   *
   * @fire application_routes
   *
   * @access public
   *
   * @param string $url
   * @param array $share_vars
   * @param bool $return
   *
   * @return string|NULL
   */
  function load($url = '') {

    $url = '/' . trim( $url, '/' );
    $this->arguments = array();

    // Protect private controllers.
    if (self::$is_main && preg_match('#\/(\_|\.)#', $url) ) {
      show_404();
    }

    if ( is_readable( TI_PATH_APP . '/' . TI_FOLDER_CONTROLLER . $url . TI_EXT_CONTROLLER )) {
      self::$is_main = FALSE;
      $rule = $url;
      unset( $url );
      include( TI_PATH_APP . '/' . TI_FOLDER_CONTROLLER . $rule . TI_EXT_CONTROLLER );
      if ( TI_AUTORENDER ) {
        $this->render( $rule );
      }
      return TRUE;
    }
    $rules = _ti_application_routes();
    $rules = do_hook( 'application_routes', $rules );
    foreach ($rules as $rule) {
      if (!$rule) {
        continue;
      }
      $pattern = strtr( preg_quote( $rule ), array('%s' => '([^\/]+)', '%d' => '([0-9]+)')) . '(?:\/(.*))?';
      if ( preg_match( '#^' . $pattern . '$#i', $url, $this->arguments ) || preg_match( '#^' . $pattern . '$#i', $url . '/index', $this->arguments ) ) {

        if ( strpos( end( $this->arguments ), '/' ) !== FALSE ) {
          $ends = array_pop( $this->arguments );
          $ends = explode( '/', $ends );
          $this->arguments = array_merge( $this->arguments, $ends );
          unset( $ends );
        }
        self::$is_main = FALSE;
        unset( $rules, $url, $pattern );
        if ( is_readable( TI_PATH_APP . '/' . TI_FOLDER_CONTROLLER . $rule . TI_EXT_CONTROLLER ) ) {
          include( TI_PATH_APP . '/' . TI_FOLDER_CONTROLLER . $rule . TI_EXT_CONTROLLER );
        }
        elseif ( is_readable( TI_PATH_APP . '/' . TI_FOLDER_CONTROLLER . $rule . '/index' . TI_EXT_CONTROLLER )) {
          include( TI_PATH_APP . '/' . TI_FOLDER_CONTROLLER . $rule . '/index' . TI_EXT_CONTROLLER );
        }
        else {
          // Switch back to $url.
          $url = $rule;
          break;
        }
        if ( TI_AUTORENDER ) {
          $this->render( $rule );
        }
        return TRUE;
      }
    }
    if (self::$is_main) {
      show_404();
    }
    else {
      show_error('Controller error', 'The controller <strong>' . $url . '</strong> not exists.');
    }
  }

  /**
   * Render the controller acording the view.
   *
   * @access public
   *
   * @param string $view
   *   the view from the TI_FOLDER_VIEW, if it is empty
   *   then framework will check if it is available
   *   view with same name as the controller.
   */
  public function render() {
    if ( func_num_args() > 0 ) {
      if ( is_readable( TI_PATH_APP . '/' . TI_FOLDER_VIEW  . '/' . func_get_arg(0) . TI_EXT_VIEW ) ) {
        extract( self::$variables, EXTR_REFS );
        return include( TI_PATH_APP . '/' . TI_FOLDER_VIEW  . '/' . func_get_arg(0) . TI_EXT_VIEW );
      }
      if ( !TI_AUTORENDER ) {
        show_error('Template error', 'The template <strong>' . func_get_arg(0) . '</strong> not exists.');
      }
    }
  }

  /**
   * Get Nth argument or return NULL if it is not exists.
   *
   * @param int $n
   *
   * @return string|NULL
   */
  public function arg($n = 0) {
    return isset( $this->args[$n] ) ? $this->args[$n] : NULL;
  }

  /**
   * Get all shared variables.
   *
   * @access public
   *
   * @return array
   */
  public function get_vars() {
    return self::$variables;
  }

  /**
   * Magic function that store variables as properties into Application superglobal
   *
   * @access public
   *
   * @param string $key
   * @param mixed $value
   *
   * @return bool
   */
  public function __set($key, $value = NULL) {
    return ( self::$variables[$key] = $value );
  }

  /**
   * Magic function that retrieve  property from Application superglobal
   *
   * @access public
   *
   * @param string $key
   *
   * @return mixed
   */
  public function &__get($key) {
    if ( isset( self::$variables[$key] ) ) {
      return self::$data[$key];
    }
    return NULL;
  }

}
