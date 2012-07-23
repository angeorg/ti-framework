<?php

/**
 * ti-framework.php - the framework code
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

// For bootstrap instructions @see group "framework bootstrap".

/**
 * @defgroup generic classes
 * @{
 *
 * Define classes Application, Messagebus, Database, Calendar, Image, Pagination
 */

/**
 * Main Application class, provide a flexible MVC alike separation.
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
   * @param bool $return
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
   * @fire application_load_url
   *
   * @access public
   *
   * @param string $url
   *
   * @return string|NULL
   */
  public function load($url = '') {

    $url = '/' . trim( $url, '/' );

    $this->arguments = array();

    $url = do_hook( 'application_load_url', $url );

    // Protect private controllers.
    if (self::$is_main && preg_match( '#\/(\_|\.)#', $url ) ) {
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

    foreach ( $rules as $rule ) {
      if ( !$rule ) {
        continue;
      }
      $pattern = strtr( preg_quote( $rule ), array('%s' => '([^\/]+)', '%d' => '([0-9]+)')) . '(?:\/(.*))?';
      if ( preg_match( '#^' . $pattern . '$#i', $url, $this->arguments )
          || preg_match( '#^' . $pattern . '$#i', $url . '/index', $this->arguments ) ) {

        unset( $this->arguments[0] );
        $this->arguments = explode( '/', implode( '/', $this->arguments ) );
        // Is it correct to strip duplicated / (slashes), maybe sometimes they are placed correctly?!
        // $this->arguments = array_filter( $this->arguments );

        self::$is_main = FALSE;
        unset( $rules, $url, $pattern );

        // Checking for controller or controller directory (index.php alike)
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
    if ( self::$is_main ) {
      show_404();
    }
    else {
      show_error( 'Controller error', 'The controller <strong>' . $url . '</strong> not exists.' );
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
        include( TI_PATH_APP . '/' . TI_FOLDER_VIEW  . '/' . func_get_arg(0) . TI_EXT_VIEW );
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
    return isset( $this->arguments[$n] ) ? $this->arguments[$n] : NULL;
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
    $val = NULL;
    return $val;
  }

}

/**
 * Database wraper class for PDO
 */
class Database extends PDO {

  /**
   * Database table's prefix
   *
   * @var string
   */
  public $prefix = '';

  /**
   * Wrap table with quotes and prepend with the prefix.
   *
   * @access public
   *
   * @param string $tablename
   *
   * @return string
   */
  public function db_table($tablename = '') {
    return $this->db_column( $this->prefix . $tablename );
  }

  /**
   * Wrap column or table with quotes, acording to current database.
   *
   * @access public
   *
   * @param string $key
   *
   * @return string
   */
  public function db_column($column_name = '') {
    if ( $column_name ) {
      switch ( $this->getDriver() ) {
        case 'interbase': $sq = '"'; break;
        case 'mysql': default: $sq = '`';
      }
      return $sq . $column_name . $sq;
    }
    return '';
  }

  /**
   * Get current database type.
   *
   * @access public
   *
   * @return string
   */
  public function getDriver() {
    return $this->getAttribute( PDO::ATTR_DRIVER_NAME );
  }

  /**
   * Perform a query to pdo, it is PDO::query() wrapper
   *
   * @see PDO::query()
   *
   * @access public
   *
   * @param string $querystr
   * @param array $args
   *
   * @return object
   */
  public function query($querystr = '', $args = array()) {

    $args = CAST_TO_ARRAY( $args );

    $query = parent::prepare( $querystr );
    $query->setFetchMode( PDO::FETCH_OBJ) ;
    $query->execute( $args );

    if ( TI_DEBUG_MODE && (int) $query->errorCode() ) {
      show_error( 'Database error', vsprintf('<p><strong>%s</strong> %s</p><p>%s</p>', $query->errorInfo() ) );
    }

    return $query;
  }

  /**
   * Build keypair_clause from array, object or url string.
   *
   * @access public
   *
   * @param mixed $elements
   * @param args to return &$args
   * @param string $prepend_clause
   *   WHERE, HAVING or SET
   * @param string $separator
   *   AND, OR,  ',' comma
   *
   * @return string
   */
  function build_keypair_clause($elements = array(), &$args = array(), $prepend_clause = 'WHERE', $separator = 'AND') {

    $elements = CAST_TO_ARRAY( $elements );

    $prepend_clause = trim( $prepend_clause );
    $separator = trim( $separator );

    if ($prepend_clause == 'SET') {
      $separator = ',';
    }

    foreach ($elements as $key => $val) {
      if ($prepend_clause !== 'SET' && is_array($val) ) {
        $q[] = $this->db_column( $key ) . ' IN ( ' . str_repeat( '?', count($val) ) . ')';
        foreach ( $val as $v ) {
          $args[] = $v;
        }
      }
      else {
        $q[] = $this->db_column( $key ) . ' = ? ';
        $args[] = $val;
      }
    }

    $prepend_clause = ' ' . $prepend_clause . ' ';
    $separator = ' ' . $separator . ' ';

    $querystr = implode($separator, $q);

    if ($querystr) {
      return $prepend_clause . $querystr;
    }

  }

  /**
   * Insert record.
   *
   * @access public
   *
   * @param string $table
   * @param mixed $elements
   *
   * @return int
   */
  function insert($table , $elements) {

    $elements = CAST_TO_ARRAY( $elements );

    $querystr = 'INSERT INTO ' . $this->db_column($table);

    $keys = array();
    foreach ( array_keys( $elements ) as $key ) {
      $keys[] = $this->db_column( $key );
    }

    $querystr.= '(' . implode(',', $keys). ')';
    $querystr.= 'VALUES(' . implode( ',', array_fill( 0, count( $elements ), '?' ) ). ')';

    $query = $this->prepare( $querystr );

    return $query->execute( array_values( $elements ) );
  }

  /**
   * Delete records.
   *
   * @access public
   *
   * @param string $table
   *   if it is string, then it allow custom where clause
   *   if it is array|object then it will be converted.
   * @param mixed $condition
   *
   * @return int
   */
  function delete($table = '', $condition = array()) {

    if ( is_string( $condition ) ) {
      $cond_str = $condition;
    }
    else {
      $cond_str = $this->build_keypair_clause( $condition, $args, 'WHERE', 'AND' );
    }

    $querystr = 'DELETE FROM ' . $this->db_column( $table ) . $cond_str;

    $this->query( $querystr, $args );

    return $this->affected_rows();
  }

  /**
   * Update records.
   *
   * @access public
   *
   * @param string $table
   * @param mixed $elements
   *   if it is string, then it allow custom where clause
   *   if it is array|object then it will be converted.
   * @param mixed $condition
   *
   * @return int
   */
  function update($table = '', $elements = array(), $condition = array()) {

    $set_str = $this->build_keypair_clause($elements, $args, 'SET', ',');

    if ($set_str) {

      if (is_string($condition)) {
        $cond_str = $condition;
      }
      else {
        $cond_str = $this->build_keypair_clause($condition, $args, 'WHERE', 'AND');
      }

      $querystr = 'UPDATE ' .
          $this->db_column($table) .
          $set_str .
          $this->build_keypair_clause($condition, $args, 'WHERE', 'AND');
      $this->query($querystr, $args);
      return $this->affected_rows();
    }
    else {
      return 0;
    }
  }

  /**
   * Retrieve records from table.
   *
   * @access public
   *
   * @param string $table
   * @param array $columns
   * @param mixed $condition
   *
   * @return array
   */
  function select($table = '', $columns = array(), $condition = array()) {

    $args = array();
    $querystr = 'SELECT';

    if (!$columns || $columns == '*') {
      $querystr .= ' * ';
    }
    else {
      $columns = CAST_TO_ARRAY( $columns );
      foreach ( $columns as &$col ) {
        $col = $this->db_column( $col );
      }
      $querystr .= ' ' . implode( ', ', $columns );
    }

    $querystr .= ' FROM ' . $table;

    if (is_string($condition)) {
      $querystr .= ' WHERE ' . $condition;
    }
    else {
      $querystr .= $this->build_keypair_clause($condition, $args, 'WHERE', 'AND');
    }

    return $this->query( $querystr, $args )->fetchAll( PDO::FETCH_OBJ );
  }

}

/**
 * Messagebus class.
 */
class Messagebus {

  /**
   * Allowed HTML tags to be used in message body.
   *
   * @var $allowed_html_tags
   *
   * @access public
   */
  public $allowed_html_tags = '<p><div><em><u><b><strong><i><img><a>';

  /**
   * Add message to the messagebu's queue.
   *
   * @access public
   *
   * @param string $Text
   * @param string $Title
   * @param string $Class
   * @param array|object $Attributes
   *
   * @return boolean
   */
  public function add($Text = '', $Title = '', $Class = '', $Attributes = array()) {

    $Attributes = CAST_TO_OBJECT( $Attributes );

    $o = new stdClass;
    $o->Title = strip_tags( CAST_TO_STRING($Title) );
    $o->Text = strip_tags( CAST_TO_STRING( $Text ), $this->allowed_html_tags );
    $o->Class = htmlentities( CAST_TO_STRING($Class) );
    $o->Attributes = $Attributes;

    $m = session_get( '_ti_mbus' );
    if ( !is_array($m )) {
      $m = array();
    }
    $m[] = $o;
    session_set( '_ti_mbus', $m );
    return TRUE;
  }

  /**
   * Get all messages in the queue.
   *
   * @access public
   *
   * @return array
   */
  public function get_all() {
    return (array) session_get( '_ti_mbus' );
  }

  /**
   * Get number of messages in the queue.
   *
   * @access public
   *
   * @return int
   */
  public function count() {
    return count( session_get( '_ti_mbus' ) );
  }

  /**
   * Clear messages in the queue.
   *
   * @access public
   */
  public function clear() {
    session_set( '_ti_mbus', array() );
  }

}

/**
 * Calendar based events.
 */
class Calendar {

  /**
   * Link template (spritnf suitable string)
   *
   * @var $link_template
   *
   * @access public
   */
  public $link_template = 'blog/%Y-%m-%d';

  /**
   * Link all days, instead of these with events only
   *
   * @var $link_all_days
   *
   * @access public
   */
  public $link_all_days = TRUE;

  /**
   * First day of week is monday.
   *
   * @var $first_is_monday
   *
   * @access public
   */
  public $first_is_monday = TRUE;

  /**
   * Show weekdays names, instead of numbers.
   *
   * @var $show_weekday_names
   *
   * @access public
   */
  public $show_weekday_names = TRUE;

  /**
   * Markup to wrap the table (printf suitable string)
   *
   * @access public
   *
   * @var $html_table_open
   */
  public $html_table_wrap = '<table class="table-calendar">%s</table>';

  private $events_dates = array();
  private $html = '';

  /**
   * Add event to the calendar.
   *
   * @access public
   *
   * @param string $date
   *
   * @return boolean
   */
  public function add_event($date = '', $text = '') {

    if (is_array($date) || is_object($date)) {
      foreach ( $date as $key => $val ) {
        $this->add_event( $key, $val );
      }
    }
    else {
      $date = CAST_TO_STRING( $date );
    }

    if ( !$date ) {
      return FALSE;
    }

    if ( !isset($this->events_dates[$date]) ) {
      $this->events_dates[$date] = array();
    }

    $this->events_dates[$date][] = $text;

    return TRUE;
  }

  /**
   * Generate the calendar's html
   *
   * @access public
   *
   * @param date $YMD
   *
   * @return Calendar
   */
  public function generate($YMD = '') {

    $this->html = '';

    $YMD = explode('-', $YMD);

    if (!$YMD[0]) {
      $YMD[0] = date('Y');
    }

    if (!isset($YMD[1])) {
      $YMD[1] = date('m');
    }

    if (!isset($YMD[2])) {
      $YMD[2] = date('d');
    }

    $Y = str_pad( CAST_TO_INT( $YMD[0] ), 4, 0, STR_PAD_LEFT );
    $M = str_pad( CAST_TO_INT( $YMD[1], 1, 12 ), 2, 0, STR_PAD_LEFT );
    $D = str_pad( CAST_TO_INT( $YMD[2], 1, 31 ), 2, 0, STR_PAD_LEFT );

    $day_weekday = date( 'w', mktime( 0, 0, 0, $M, 1, $Y ) );
    if ( $day_weekday === 0 ) {
      $day_weekday = 7;
    }

    $this->html .= '<tr>';

    if ( $this->show_weekday_names) {
      for ( $i = 0; $i < 7; $i++ ) {
        $this->html .= '<th>' . $i . '</th>';
      }
      $this->html .= '</tr><tr>';
    }

    for ($i = 1; $i < ($this->first_is_monday ? $day_weekday : $day_weekday + 1); $i++) {
      $this->html .= '<td class="hidden">&nbsp;</td>';
    }

    $month_days = date('t', mktime(0, 0, 0, $M, 1, $Y));

    for ($i = 1; $i <= $month_days; $i++) {

      $date = $Y . '-' . $M . '-' . $i;
      $link = site_url(strftime($this->link_template, strtotime($date)));

      if (isset($this->events_dates[$date])) {
        $events = count($this->events_dates[$date]);
      }
      else {
        $events = 0;
      }

      $title = $events ? sprintf(__('Events on this date - %d.'), $events) : __('There is no events on this date');

      if ($this->link_all_days) {
        $this->html .= '<td><a href="' . $link . '" title="' . $title . '">' . $i . '</a></td>';
      }
      elseif ($events) {
        $this->html .='<td><a href="' . $link . '" title="' . $title . '">' . $i . '</a></td>';
      }
      else {
        $this->html .='<td>' . $i . '</td>';
      }

      if ($this->first_is_monday) {
        if ($day_weekday === 7) {
          $this->html .= "</tr><tr>";
          $day_weekday = 1;
          continue;
        }
      }
      else {
        if ($day_weekday === 7) {
          $day_weekday = 1;
          continue;
        }
        elseif ($day_weekday === 6) {
          $this->html .= '</tr><tr>';
        }
      }
      $day_weekday++;
    }

    for (; $day_weekday <= ($this->first_is_monday ? 7 : 6); $day_weekday++) {
      $this->html .= '<td>&nbsp;</td>';
    }

    $this->html .= '</tr>';

    if ( strpos( $this->html_table_wrap, '%s' ) !== FALSE ) {
      $this->html = sprintf( $this->html_table_wrap, $this->html );
    }

    return $this;
  }

  /**
   * Output the generated calendar html.
   *
   * @access public
   */
  public function show() {
    echo $this->html;
  }

  /**
   * Return the generated calendar html.
   *
   * @access public
   *
   * @return string
   */
  public function get_html() {
    return $this->html;
  }

}

/**
 * Simple class for work with images.
 */
class Image {

  /**
   * Quality value of images (used in jpeg files)
   *
   * @var int
   */
  public $quality = 85;

  private $im = NULL;

  /**
   * Load image for manipulation from existing file on the filesystem.
   *
   * @param string $filename
   *
   * @return boolean
   */
  function load_from_file($filename = '') {

    if ( !$filename || !is_readable($filename) ) {
      return FALSE;
    }

    if ( !($image_info = getimagesize( $filename )) ) {
      return FALSE;
    }

    switch ($image_info['mime']) {
      case 'image/gif':
        if ( function_exists( 'imagecreatefromgif' ) && ( $this->im = imagecreatefromgif( $filename ) ) ) {
          return TRUE;
        }
        break;

      case 'image/jpeg':
        if ( function_exists( 'imagecreatefromjpeg' ) && ( $this->im = imagecreatefromjpeg( $filename ) ) ) {
          return TRUE;
        }
        break;

      case 'image/png':
        if ( function_exists( 'imagecreatefrompng' ) && ( $this->im = imagecreatefrompng( $filename ) ) ) {
          return TRUE;
        }
        break;

      case 'image/wbmp':
        if ( function_exists( 'imagecreatefromwbmp' ) && ( $this->im = imagecreatefromwbmp( $filename ) ) ) {
          return TRUE;
        }
        break;

      case 'image/xbm':
        if ( function_exists( 'imagecreatefromxbm' ) && ( $this->im = imagecreatefromxbm( $filename ) ) ) {
          return TRUE;
        }
        break;

      case 'image/xpm':
        if ( function_exists( 'imagecreatefromxpm' ) && ( $this->im = imagecreatefromxpm( $filename ) ) ) {
          return TRUE;
        }
        break;
    }

    $this->im = NULL;
    return FALSE;
  }

  /**
   * Load image for manipulation from a file content (string)
   *
   * @param string $content
   *
   * @return boolean
   */
  function load_from_string($content = '') {

    if ( $content && is_string( $content ) && ($i = imagecreatefromstring( $content ) ) ) {
      $this->im = $i;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Save current image into file.
   *
   * @param string $filename
   *   output filepath
   * @param int $quality
   *   override the object quality with custom 0-100
   * @param int $permissions
   *
   * @return boolean
   */
  function save_to_file($filename, $quality = FALSE, $permissions = NULL) {

    if ( imagejpeg($this->im, $filename, ( $quality ? $quality : $this->quality)) ) {
      if ($permissions !== NULL) {
        chmod($filename, $permissions);
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get current image resource.
   *
   * @return resource
   */
  function image() {
    return $this->im;
  }

  /**
   * Get current image height
   *
   * @return int
   */
  function height() {
    return imagesy( $this->im );
  }

  /**
   * Get current image with.
   *
   * @return int
   */
  function width() {
    return imagesx( $this->im );
  }

  /**
   * Render the image directly.
   *
   * @param int $quality
   * @param string $type
   *   gif, jpeg, png
   *
   * @return boolean
   */
  function output($quality = FALSE, $type = 'jpeg', $send_header = TRUE) {

    switch ($type) {
      case 'gif' :
        if ( imagegif($this->im, NULL) ) {
          if ( $send_header ) {
            @header( 'Content-type: image/gif' );
          }
          return TRUE;
        }
      case 'png' :
        if ( imagepng($this->im, NULL, 8) ) {
          if ( $send_header ) {
            @header( 'Content-type: image/png' );
          }
          return TRUE;
        }
      default:
        if ( imagejpeg($this->im, NULL, ($quality ? $quality : $this->quality) )) {
          if ( $send_header ) {
            @header( 'Content-type: image/jpeg' );
          }
          return TRUE;
        }
    }
    return FALSE;
  }

  /**
   * Get and return the output from output() method
   *
   * @param int $quality
   * @param string $type
   *
   * @return string
   */
  function output_get($quality = FALSE, $type = 'jpeg') {
    ob_start();
    $this->output( $quality, $type, FALSE );
    return ob_get_clean();
  }

  /**
   * Interlance the current image.
   *
   * @return bool
   */
  function interlance() {
    return (bool) imageinterlace( $this->im, TRUE );
  }

  /**
   * Resize current image to match te specific height
   *
   * @param int $height
   * @param bool $preserve_smaller
   *
   * @return bool
   */
  function resize_to_height($height, $preserve_smaller = TRUE) {
    $ratio = $height / imagesy( $this->im );
    $width = imagesx( $this->im ) * $ratio;
    return $this->resize( $width, $height, $preserve_smaller );
  }

  /**
   * Resize current image to specific with.
   *
   * @param int $width
   * @param bool $preserve_smaller
   *
   * @return bool
   */
  function resize_to_width($width, $preserve_smaller = TRUE) {
    $ratio = $width / imagesx( $this->im );
    $height = imagesy( $this->im ) * $ratio;
    return $this->resize ($width, $height, $preserve_smaller );
  }

  /**
   * Resize current image to specific size, mean image will be
   * no heigher and widther than this size.
   *
   * @param int $size
   * @param bool $preserve_smaller
   *
   * @return bool
   */
  function resize_to_size($size, $preserve_smaller = TRUE) {

    $width_orig = imagesx( $this->im );
    $height_orig = imagesy( $this->im );

    if ( $width_orig > $height_orig ) {
      $ratio = $size / $width_orig;
      $height = $height_orig * $ratio;
      $width = $size;
    }
    else {
      $ratio = $size / $height_orig;
      $width = $width_orig * $ratio;
      $height = $size;
    }

    return $this->resize( $width, $height, $preserve_smaller );
  }

  /**
   * Resize image to absolute width and height.
   *
   * @param int $width
   * @param int $height
   * @param bool $preserve_smaller
   *
   * @return bool
   */
  function resize($width, $height, $preserve_smaller = TRUE) {

    if ( $preserve_smaller ) {
      $width_orig = imagesx( $this->im );
      $height_orig = imagesy( $this->im );
      if ( $width_orig < $width && $height_orig < $height ) {
        return TRUE;
      }
    }

    $image_new = imagecreatetruecolor( $width, $height );
    return imagecopyresampled( $this->im, $this->im, 0, 0, 0, 0, $width, $height, imagesx( $this->im ), imagesy( $this->im ) );
  }

  /**
   * Resize and crop current image.
   *
   * @param int $width
   * @param int $height
   * @param true $preserve_smaller
   *
   * @return bool
   */
  function resize_cropped($width, $height, $preserve_smaller = TRUE) {

    $width_orig = imagesx( $this->im );
    $height_orig = imagesy( $this->im );
    $ratio_orig = $width_orig / $height_orig;

    if ( $preserve_smaller ) {
      $width_orig = imagesx( $this->im );
      $height_orig = imagesy( $this->im );
      if ( $width_orig < $width && $height_orig < $height ) {
        return TRUE;
      }
    }

    if ( $width / $height > $ratio_orig ) {
      $new_height = $width / $ratio_orig;
      $new_width = $width;
    }
    else {
      $new_width = $height * $ratio_orig;
      $new_height = $height;
    }
    $x_mid = $new_width / 2;
    $y_mid = $new_height / 2;

    $image_proccess = imagecreatetruecolor( round( $new_width ), round( $new_height ) );
    imagecopyresampled( $image_proccess, $this->im, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig );

    $image_new = imagecreatetruecolor( $width, $height );
    imagecopyresampled( $image_new, $image_proccess,
        0, 0, ($x_mid - ($width / 2)), ($y_mid - ($height / 2)),
        $width, $height, $width, $height);
    imagedestroy( $image_proccess );

    $this->im = $image_new;
    imagedestroy( $image_new );
    return TRUE;
  }

  /**
   * Scale the current image.
   *
   * @param int $scale
   * @param bool $preserve_smaller
   *
   * @return bool
   */
  function scale($scale = '100', $preserve_smaller = TRUE) {

    $width = imagesx( $this->im ) * $scale / 100;
    $height = imagesy( $this->im ) * $scale / 100;
    return $this->resize( $width, $height, $preserve_smaller );
  }

  /**
   * Rotate the current image.
   *
   * @param int $rotate
   *
   * @return bool
   */
  function rotate($rotate = 90) {

    return (( $this->im = imagerotate($this->im, CAST_TO_INT($rotate), 0) ));
  }

  /**
   * Add wattermark to the iamge.
   *
   * @param string $text
   * @param int $fontsize
   * @param string $font
   *   path to TTF font for using
   * @param string $position
   *   TOP BOTTOM LEFT RIGHT
   *
   * @return bool
   */
  function wattermark_text($text = '', $fontsize = 18, $font = '', $position = 'RIGHT BOTTOM') {

    $text = trim( CAST_TO_STRING( $text ));

    if ( !$text ) {
      return FALSE;
    }

    $fontsize = CAST_TO_INT( $fontsize, 1, 120 );

    $black = imagecolorallocate( $this->im, 0, 0, 0 );

    list( $pos[0], $post[1] ) = explode( ' ', $position );

    if ( strpos( $position, 'LEFT' ) !== FALSE ) {
      $mark_x = 10;
    }
    else {
      $mark_x = imagesx( $this->im ) - 10 - strlen( $text ) * $fontsize;
    }

    if ( strpos($position, 'TOP') !== FALSE) {
      $mark_y = 10;
    }
    else {
      $mark_y = imagesy( $this->im ) - 10 - $fontsize;
    }

    return (bool) imagettftext( $this->im, $fontsize, 0, $mark_x, $mark_y, $black, $font, $text );

  }

  /**
   * Add wattermark to the image.
   *
   * @param string $image
   *   path to image that will be used for wattermark
   * @param int $size
   * @param string $position
   *   TOP BOTTOM LEFT RIGHT
   *
   * @return bool
   */
  function wattermark_image($imagefile, $size = 0, $position = 'RIGHT BOTTOM') {

    if ( !$imagefile ) {
      return FALSE;
    }

    $wim = new Image;
    if (!$wim->load_from_file( $imagefile )) {
      return FALSE;
    }

    if ($size) {
      $wim->resize_to_size( CAST_TO_INT( $size, 8, 640 ), TRUE );
    }

    $mark_w = $wim->width();
    $mark_h = $wim->height();

    if (strpos($position, 'LEFT') !== FALSE) {
      $mark_x = 10;
    }
    else {
      $mark_x = $this->width() - 10 - $mark_w;
    }

    if (strpos($position, 'TOP') !== FALSE) {
      $mark_y = 10;
    }
    else {
      $mark_y = $this->height() - 10 - $mark_h;
    }

    return (bool) imagecopy($this->im, $wim->image(), $mark_x, $mark_y, 0, 0, $mark_w, $mark_h);

  }

  /**
   * Convert current image to ascii.
   *
   * @param bool $binary
   *    create 1010 like ascii
   *
   * @return string
   */
  function to_ascii($binary = TRUE) {

    $text = '';
    $width = imagesx($this->im);
    $height = imagesy($this->im);

    for ($h = 1; $h < $height; $h++) {

      for ($w = 1; $w <= $width; $w++) {

        $rgb = imagecolorat($this->im, $w, $h);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        if ($binary) {
          $hex = '#' . str_pad( dechex( $r ), 2, '0', STR_PAD_LEFT )
          . str_pad( dechex( $g ), 2, '0', STR_PAD_LEFT )
          . str_pad( dechex( $b ), 2, '0', STR_PAD_LEFT );

          if ($w == $width) {
            $text .= '<br />';
          }
          else {
            $text .= '<span style="color:' . $hex . ';">#</span>';
          }
        }
        else {
          if ( $r + $g + $b > 382 ) {
            $text .= '0';
          }
          else {
            $text .= '1';
          }
        }
      }

    }

    return $text;
  }

}

/**
 * Simple pagination class
 */
class Pagination {

  /**
   * Current page
   */
  public $current_page = 1;

  /**
   * Total num of records to paginate
   */
  public $total_rows = 100;

  /**
   * How many per page to show
   */
  public $per_page = 20;

  /**
   * Base url, printf format
   */
  public $base_url = '';

  /**
   * How many cells to show
   */
  public $size = 10;

  /**
   * Format of normal cell (printf format)
   */
  public $html_cell_normal = '<a href="%s">%s</a>';

  /**
   * Format of active cell (printf format)
   */
  public $html_cell_active = '<a href="%s" class="active">%s</a>';

  /**
   * Format of first cell (printf format)
   */
  public $html_cell_first = '<a href="%s">&#8592;</a>';

  /**
   * Format of last cell (printf format)
   */
  public $html_cell_last = '<a href="%s">&#8594;</a>';

  /**
   * Format of wrapper (printf format)
   */
  public $html_wrapper = '<div class="pagination">%s</div>';

  private $html = '';

  /**
   * Constructor, if array or object is passed, then initialize the object with vals.
   *
   * @param mixed
   *
   * @return Pagination
   */
  function __construct($config = array()) {

    $config = do_hook( 'pagination_config', $config );

    if ( $config ) {
      foreach ( CAST_TO_ARRAY( $config ) as $key => $val ) {
        if ( isset($this->{$key}) ) {
          $this->{$key} = $val;
        }
      }
    }
    return $this;
  }

  /**
   * Generate the paginator
   *
   * @return Pagination
   */
  public function generate() {

    if ( $this->per_page >= $this->total_rows ) {
      return FALSE;
    }

    $html = '';

    $this->per_page = CAST_TO_INT($this->per_page, 1);

    $page_num_last = ceil( $this->total_rows / $this->per_page );

    if ( $this->current_page > $page_num_last ) {
      $this->current_page = $page_num_last;
    }
    elseif ($this->current_page < 1) {
      $this->current_page = 1;
    }

    $page_num_prev = $this->current_page > 1 ? $this->current_page - 1 : 1;
    $page_num_next = $this->current_page < $page_num_last ? $this->current_page + 1 : $page_num_last;

    if ( $this->size ) {
      $half_size = floor($this->size / 2);

      $even = $this->size % 2 ? 1 : 0;

      $for_loops = $this->current_page + $half_size + $even;
      $i = $this->current_page - $half_size + 1;

      if ($this->current_page - $half_size < 1) {
        $for_loops = $this->size;
        $i = 1;
      }

      if ($for_loops > $page_num_last) {
        $for_loops = $page_num_last;
        $i = $page_num_last - $this->size + 1;
      }

      if ($i < 1) {
        $i = 1;
      }
    }
    else {
      $for_loops = $page_num_last;
      $i = 1;
    }

    if ( $this->current_page > 1 ) {
      if ( $this->html_cell_first ) {
        $html .= sprintf( $this->html_cell_first, site_url( sprintf( $this->base_url, 1 ) ) );
      }
    }

    for ($s = 1; $i <= $for_loops; $i++, $s++) {
      $uri = site_url( sprintf( $this->base_url, $i ) );

      if ($this->current_page == $i) {
        $html .= sprintf( $this->html_cell_active, $uri, $i );
      }
      else {
        $html .= sprintf( $this->html_cell_normal, $uri, $i );
      }
    }

    if ( $page_num_last > $this->current_page ) {
      if ( $this->html_cell_last ) {
        $html .= sprintf( $this->html_cell_last, site_url( sprintf( $this->base_url, $page_num_last ) ) );
      }
    }

    if ( !$html ) {
      return '';
    }

    if ( $this->html_wrapper ) {
      $html = sprintf( $this->html_wrapper, $html );
    }

    $this->html = $html;

    return $this;
  }

  /**
   * Output the generated calendar html.
   */
  public function show() {
    echo $this->html;
  }

  /**
   * Return the generated calendar html.
   *
   * @return string
   */
  public function get_html() {
    return $this->html;
  }

}

/**
 * @} End of "defgroup framework classes".
 */

/**
 * @defgroup generic functions
 * @{
 *
 * declare framework's generic functions
 */

/**
 * Fix $_SERVER SERVER_SOFTWARE and REQUEST_URI variables for future using.
 *
 * @thanks WordPress
 *
 * @return void
 */
function _ti_fix_server_vars() {
  // Correct the requested URL
  if ( TI_DISABLE_MOD_REWRITE ) {
    if ( empty($_SERVER['REQUEST_URI']) ) {
      $_SERVER['REQUEST_URI'] = '/';
    }
  }
  else {
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
  }

  // Set TI_HOME if we are on root.
  if ( $_SERVER['REQUEST_URI'] == '/' ) {
    $_SERVER['REQUEST_URI'] = TI_HOME;
  }

}

/**
 * Wrapper for session_start() used from ti-framework bootstrap
 *
 * @return void
 */
function _ti_session_start() {
  // Instantinate session.
  if ( !session_id() ) {
    session_start();
  }
  if ( ifsetor( $_SESSION['_ti_client'] ) !== md5( get_ip() . $_SERVER['HTTP_USER_AGENT'] )
      || ifsetor( $_SESSION['_ti_id'] ) !== md5( TI_APP_SECRET . session_id() ) ) {

    $_SESSION['_ti_client'] = md5( get_ip() . $_SERVER['HTTP_USER_AGENT'] );
    $_SESSION['_ti_id'] = md5( TI_APP_SECRET . session_id() );
  }
  session_regenerate_id();
}

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
 * @fire application_routes
 *
 * @access private
 *
 * @return array
 */
function _ti_application_routes() {

  static $routes = NULL;

  if ( $routes === NULL ) {

    if ( TI_RULES_CACHE && cache_exists( 'ti://rules-cache', TI_RULES_CACHE )) {
      $routes = explode( NL, cache_get( 'ti://rules-cache', TI_RULES_CACHE ) );
    }
    else {
      $routes = _ti_application_find_routes_fd( TI_PATH_APP . '/' . TI_FOLDER_CONTROLLER );
      asort( $routes );
      if ( TI_RULES_CACHE ) {
        cache_put( 'ti://rules-cache', implode( NL, $routes ) );
      }
    }
  }

  return do_hook( 'application_routes', $routes );
}

/**
 * Load URL when define new object of Application with parameters.
*
* @see Application->load().
*
* @param string $url
* @param string $return
*
* @return Application
*/
function Application($url = '', $return = FALSE) {
  $app = new Application( $url, $return );
  return $app;
}

/**
 * Message Bus class
 * Store messages per page here.
 * If this function is called with argument text,
 * then it will add text to the messagebus, some kind of shortcut.
 *
 * @param string $text
 *
 * @return TI_Messagebus
 */
function mbus($text = '') {

  static $mbus = NULL;
  if ($mbus === NULL) {
    $mbus = new Messagebus;
  }
  $text = trim($text);
  if (!empty($text)) {
    $mbus->add($text);
  }
  return $mbus;
}

/**
 * PDO accesspoint.
 *
 * <?php
 *   // Example calling.
 *   db()->query(...);
 *
 *   // Example calling nondefault (defined with TI_DB_fb)
 *   db('fb')->query(...);
 *
 *   // Modify the driver params when init the PDO
 *   add_hook('pdo_options', function($options, $data, $driver) {
 *
 *     if ($driver == 'mysql') {
 *       $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
 *     }
 *
 *     return $options;
 *
 *   });
 * ?>
 *
 * @see http://www.php.net/manual/en/pdo.drivers.php
 *   About mysql driver before PHP 5.3.6, ti-framework have
 *   built in competability for charset, so it is not ignored.
 *
 * @fire pdo_options
 *
 * @param string $db_id
 *   default is empty, pointing to the TI_DB configuration
 *   db() return db object with config from TI_DB
 *   db('1') return db object with config from TI_DB_1
 *   db('ib') return db object with config from TI_DB_ib
 *
 * @return PDO
 */
function db($db_id = '') {

  if ($db_id) {
    $db_id = '_' . $db_id;
  }

  static $databases = array();

  if ( !defined('TI_DB' . $db_id) ) {
    show_error('System error', 'Database <strong>' . $db_id . '</strong> not configured.');
    return NULL;
  }

  $hash = md5( $db_id );

  if ( isset( $databases[$hash] ) ) {
    return $databases[$hash];
  }

  if ( !extension_loaded('PDO') ) {
    show_error('System error', 'Database PDO extension not available.');
    return NULL;
  }

  $dburi = constant( 'TI_DB' . $db_id );

  parse_str( str_replace(';', '&', $dburi), $cred );

  $username = ifsetor( $cred['username'] );
  $password = ifsetor( $cred['password'] );
  $prefix = ifsetor( $cred['prefix'] );
  unset( $cred['username'], $cred['password'], $cred['prefix'] );

  $dsn = http_build_query( $cred, NULL, ';' );
  $dsn = urldecode( $dsn );

  foreach (PDO::getAvailableDrivers() as $driver) {
    if (isset($cred[$driver . ':dbname'])) {
      try {
        $options = array();
        if ($driver == 'mysql' && version_compare(PHP_VERSION, '5.3.6', '<=')
            && isset($cred['charset'])) {
          $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $cred['charset'];
        }
        $options = do_hook( 'pdo_options', $options, $cred, $driver );
        $databases[$hash] = new Database( $dsn, $username, $password, $options );
        $databases[$hash]->prefix = $prefix;
        return $databases[$hash];
      }
      catch ( PDOException $e ) {
        show_error('System error', 'Can\'t connect to database. <br >' . $e->getMessage());
      }
    }
  }

  show_error('System error', 'Database driver not found.');
  return NULL;
}

/**
 * Is the application run in command line mode
 *
 * @return bool
 */
function is_cli() {
  return PHP_SAPI == 'cli';
}

/**
 * Determine if SSL is used.
 *
 * @return bool
 *   True if SSL, false if not used.
 */
function is_ssl() {
  if ( !empty( $_SERVER['HTTPS'] ) && ( strtolower( $_SERVER['HTTPS'] ) == 'on' || $_SERVER['HTTPS'] == '1' ) ) {
    return TRUE;
  }
  elseif ( !empty( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] == '443' ) ) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Is the application run from ajax query
 *
 * @fire is_ajax
 *
 * @return bool
 */
function is_ajax() {
  return do_hook( 'is_ajax', TI_IS_AJAX );
}

if (!function_exists('get_ip')):
/**
 * Get the IP of visitor
 *
 * @fire get_ip
 *
 * @return string
 */
function get_ip() {
  static $ip = NULL;
  if ( $ip === NULL ) {
    // Correct the ip.
    $ip = array_match_first(
        'is_ip', array(
            ifsetor($_SERVER['HTTP_CLIENT_IP']),
            ifsetor($_SERVER['HTTP_X_FORWARDED_FOR']),
            ifsetor($_SERVER['REMOTE_ADDR']) ),
        '000.000.000.000' );
  }
  return do_hook( 'get_ip', $ip );
}
endif;

/**
 * ti-framework class autoloader function.
 *
 * @fire __autoload
 *
 * @param string $library
 *
 * @return bool
 */
function ti_autoloader($library = '') {

  $library = strtolower( $library );

  $possible_files = array(
      TI_PATH_APP . '/' . TI_FOLDER_INC . '/class-' . $library . TI_EXT_INC,
      TI_PATH_APP . '/' . TI_FOLDER_INC . '/class.' . $library . TI_EXT_INC,
      TI_PATH_APP . '/' . TI_FOLDER_INC . '/' . $library . TI_EXT_INC,
  );

  $possible_files = do_hook( '__autoload', $possible_files, $library );

  foreach ( $possible_files as $file ) {
    if ( is_readable( $file ) ) {
      include_once( $file );
      return TRUE;
    }
  }

  if ( !class_exists( $library ) ) {
    show_error( 'System error', 'Library <strong>' . $library . '</strong> not exists.' );
    return FALSE;
  }

}

/**
 * Is access from mobile device?
 *
 * <?php
 *   // Using the hook to force result.
 *   add_hook('is_mobile', function() {
 *     return TRUE;
 *   });
 * ?>
 *
 * @fire is_mobile
 *
 * @return bool
 */
function is_mobile() {

  $is_mobile = FALSE;

  $user_agent = $_SERVER['HTTP_USER_AGENT'];
  $accept = $_SERVER['HTTP_ACCEPT'];

  if ( stripos( $user_agent, 'tablet' ) !== FALSE ) {
    $is_mobile = FALSE;
  }
  elseif ( preg_match( '#(ipod|android|symbian|blackbarry|gsm|phone|mobile|opera mini)#', $user_agent ) ) {
    $is_mobile = TRUE;
  }
  elseif ( strpos( $accept, 'text/vnd.wap.wml' ) > 0 || strpos( $accept, 'application/vnd.wap.xhtml+xml') > 0  ) {
    $is_mobile = TRUE;
  }
  elseif ( isset( $_SERVER['HTTP_X_WAP_PROFILE'] ) || isset( $_SERVER['HTTP_PROFILE'] ) ) {
    $is_mobile = TRUE;
  }

  $is_mobile = do_hook( 'is_mobile', $is_mobile, $user_agent, $accept );

  return CAST_TO_BOOL( $is_mobile );
}

/**
 * Check for matching the URL on parameter
 *
 * @fire match_url
 *
 * @param string $url
 *
 * @return bool
 */
function match_url($pattern = '') {
  $pattern = '/' . strtr( preg_quote( trim( $pattern, '/' ) ), array( '%s' => '([^\/]+)', '%d' => '([0-9]+)' ) ) . '\/(.+)?';
  $matched = preg_match( '#^' . $pattern . '$#', $_SERVER['REQUEST_URI'] ) ? TRUE : FALSE;
  $matched = do_hook( 'match_url', $matched, $pattern, $_SERVER['REQUEST_URI'] );
  return $matched;
}

/**
 * Generate url for application's path
 *
 * <?php
 *   // Example usage with one argument
 *   echo '<a href="' . site_url('path/to/page') . '">example link</a>';
 *
 *   // Example usage with N arguments
 *   echo '<a href="' . site_url('path', 'to', $pagename) . '">example link</a>';
 *
 *   // Replace the domain
 *   add_hook('site_url', function($url) {
 *     return str_replace($url, 'http://example.com', 'http://example2.com');
 *   });
 * ?>
 *
 * @fire site_url
 *
 * @param string $url
 *
 * @return string
 *   complete url
 */
function site_url($url = '') {

  if ( func_num_args() > 1 ) {
    $url = func_get_args();
  }

  if ( is_array($url) ) {
    $url = implode( '/', $url );
  }

  $url = preg_replace( '#\/{2,}#', '/', $url );

  $url = trailingslashit( TI_PATH_WEB ) .
  ( TI_DISABLE_MOD_REWRITE ? '?' : '' ) .
  trim( $url, '/' ) . '/';

  return do_hook( 'site_url', $url );
}

/**
 * Get the current url
 *
 * @return string
 */
function current_url() {
  return site_url( $_SERVER['REQUEST_URI'] );
}

/**
 * Get the base url for application, it is always trailingslash,
 * because the requirement for TI_PATH_WEB
 *
 * @param string library name
 *
 * @return string
 */
function base_url() {
  return trailingslashit( TI_PATH_WEB );
}

/**
 * Appends a trailing slash.
 *
 * Will remove trailing slash if it exists already before adding a trailing
 * slash. This prevents double slashing a string or path.
 *
 * The primary use of this is for paths and thus should be used for paths. It is
 * not restricted to paths and offers no specific path support.
 *
 * @thanks WordPress team
 *
 * @uses untrailingslashit() Unslashes string if it was slashed already.
 *
 * @param string $string
 *   What to add the trailing slash to.
 *
 * @return string
 *   String with trailing slash added.
 */
function trailingslashit($string) {
  return untrailingslashit( $string ) . '/';
}

/**
 * Removes trailing slash if it exists.
 *
 * The primary use of this is for paths and thus should be used for paths. It is
 * not restricted to paths and offers no specific path support.
 *
 * @thanks WordPress team
 *
 * @param string $string
 *   What to remove the trailing slash from.
 *
 * @return string
 *   String without the trailing slash.
 */
function untrailingslashit($string) {
  return rtrim( $string, '/' );
}

/**
 * Redirect page to another one
 *
 * @param string|NULL $url
 *   destination url or current url
 * @param int $time_to_wait
 *   time to wait before redirect
 */
function redirect($url = NULL, $time_to_wait = 0) {

  if ($url === NULL) {
    $url = $_SERVER['REQUEST_URI'];
  }

  $url = site_url($url);
  if (headers_sent()) {
    echo
    '<html><head><title>Redirecting you...</title>',
    '<meta http-equiv="refresh" content="', $time_to_wait, ';url=', $url, '" />',
    '</head><body onload="location.replace(\'' . $url . '\')">',
    'You should be redirected to this URL:<br/>',
    '<a href="' . $url . '">' . $url . '</a><br/><br/>',
    'If you are not, please click on the link above.<br/>',
    '</body></html>';
  }
  elseif ( $time_to_wait ) {
    header( 'Refresh: ' . $time_to_wait . '; url=' . $url );
  }
  else {
    header( 'Location: ' . $url );
  }
  exit;
}

/**
 * Redirect to given address with status 301 (moved permanently)
 *
 * @param string $url
 *   destination url
 */
function redirect_301($url = NULL) {
  if ( headers_sent() ) {
    return FALSE;
  }
  header( 'HTTP/1.1 301 Moved Permanently' );
  redirect( $url );
}

/**
 * Parse PO file and convert it to associated array
 *
 * @param string $file
 *   filepath to the po
 *
 * @return array
 *   array(
 *     msgid => msgstr,
 *     msgid1 => msgstr2
 *     ...
 *   )
 */
function po_to_array($file = '') {

  $file = CAST_TO_STRING( $file );

  if ( is_readable( $file ) ) {

    $is_msgid = FALSE;
    $last_msgid = '';
    $array = array();

    foreach ( file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {

      if ( $line{0} == '#' ) {
        continue;
      }

      if ( strpos($line, 'msgid  ') === 0 ) {
        $last_msgid = trim(substr($line, 6), '" ');
        $is_msgid = TRUE;
      }
      elseif ( strpos($line, 'msgstr ' ) === 0 ) {
        $array[$last_msgid] = trim( substr($line, 7), '" ' );
        $is_msgid = FALSE;
      }
      else {
        if ( $is_msgid ) {
          $last_msgid .= NL . trim( $line, '" ' );
        }
        else {
          $array[$last_msgid] .= NL . trim( $line, '" ' );
        }
      }
    }
    return $array;
  }
  return FALSE;
}

/**
 * Locate locale and if exists, load
 * the locale can be .php (array file), or .po file
 *
 * <?php
 *
 *   load_locale('en_US');
 *   // this will load path/to/app/locale/en_US.php
 *   // or
 *   // this will load path/to/app/locale/en_US.po
 *   // depends what is available
 *
 * ?>
 *
 * example content of path/to/app/locale/en_US.php
 * <?php
 *   return array(
 *     'hello' => 'hello',
 *     'string' => 'String',
 *   );
 * ?>
 *
 * example content of path/to/app/locale/en_US.po
 *
 * msgid "hello"
 * msgstr "hello"
 *
 * msgid "string"
 * msgstr "String"
 *
 * @param string $locale
 *   locale name
 *
 * @return bool
 */
function load_locale($locale = '') {

  global $_LOCALE;

  if ( is_readable( TI_PATH_APP . '/' . TI_FOLDER_LOCALE . '/' . $locale . '.php') ) {
    $_lang = include( TI_PATH_APP . '/' . TI_FOLDER_LOCALE . '/' . $locale . '.php' );
  }

  elseif ( is_readable( TI_PATH_APP . '/' . TI_FOLDER_LOCALE . '/' . $locale . '.po') ) {
    $_lang = po_to_array( TI_PATH_APP . '/' . TI_FOLDER_LOCALE . '/' . $locale . '.po' );
  }

  else {
    show_error('System error', 'Locale <strong>' . $locale . '</strong> not exists.');
    return FALSE;
  }

  $_LOCALE = array_merge( $_LOCALE, CAST_TO_ARRAY( $_lang ) );
  return TRUE;
}

/**
 * Get string translation
 *
 * <?php
 *   // override the string translation
 *   add_hook('__', function($string) {
 *
 *     if ($string == 'hello') {
 *       return 'Здравей';
 *     }
 *
 *     return $string;
 *
 *   });
 * ?>
 *
 * @fire __
 *
 * @param string $string
 *   localized string
 *
 * @return string
 */
function __($string = '') {
  global $_LOCALE;
  $s1 = isset( $_LOCALE[$string] ) ? $_LOCALE[$string] : $string;
  $s1 = do_hook( '__', $string, $s1 );
  return $s1;
}

/**
 * Echo string translation
 *
 * @param string word to translate
 */
function _e($string = '') {
  echo __( $string );
}

/**
 * Plural translation
 *
 * <?php
 *
 *   echo _n('Cat', 'Cat', 1);
 *   // Cat
 *
 *   echo _n('Comment', 'Comments', 2);
 *   // Comments
 * ?>
 *
 * @param string $string_single
 * @param string $string_plura
 * @param int $number
 *
 * @return string
 */
function _n($string_single = '', $string_plural = '', $number = 1) {
  global $_LOCALE;
  return $number === 1 ? __( $string_single ) : __( $string_plural );
}

/**
 * Elapsed time from application start
 *
 * @return double
 */
function elapsed_time() {
  return round( microtime(TRUE) - TI_TIMER_START, 5);
}

/**
 * Currently memory usage
 *
 * @return double
 */
function memory_usage() {
  return memory_get_usage( TRUE );
}

/**
 * Set application content type to text/html with charset UTF-8
 *
 * @return bool
 */
function set_document_html() {
  if ( headers_sent() ) {
    return FALSE;
  }
  header( 'Content-type: text/html; charset=UTF-8;', TRUE );
  return TRUE;
}

/**
 * Set document no cache headers.
 *
 * @return bool
 */
function set_document_nocache() {
  if ( headers_sent() ) {
    return FALSE;
  }
  header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', TRUE );
  header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT', TRUE );
  header( 'Cache-Control: no-cache, must-revalidate, max-age=0', TRUE );
  header( 'Pragma: no-cache', TRUE );
  return TRUE;
}

/**
 * Set application header to downloadable file
 *
 * @param string $filename
 * @param int $size
 *
 * @return bool
 */
function set_document_downloadable($filename = '', $size = 0) {

  if ( headers_sent() ) {
    return FALSE;
  }

  $filename = sanitize_string( CAST_TO_STRING( $filename ) );

  if ( is_readable( $filename ) ) {
    $size = filesize( $filename );
    $filename = basename( $filename );
  }
  else {
    if (!$size) {
      $size = 0;
    }
    $filename = basename( current_url() );
  }

  if ( $size ) {
    header( 'Accept-Ranges: bytes', TRUE );
    header( 'Content-Length: ' . $size, TRUE );
  }
  header( 'Content-Disposition: attachment; filename="' . $filename . '"', TRUE );
  header( 'Content-Transfer-Encoding: binary', TRUE );
  header( 'Content-Description: File Transfer' );
  header( 'Cache-control: private', TRUE );

  return TRUE;
}

/**
 * Simple HTTP document authorisation
 *
 * <?php
 *   // Simple page authorization.
 *   document_auth( 'Please authorize!', function($user, $pass) {
 *     // Callback takes two parameters:
 *     // 1st - $username
 *     // 2nd - $password
 *
 *     if ($user == 'user1' && $pass == 'pass1') {
 *       return TRUE;
 *     }
 *     else {
 *       return FALSE;
 *     }
 *
 *   });
 * ?>
 *
 * @param string $callback
 *   callback that check user password
 *   can be function name or closure
 *   it should accept two parameters and return bool
 * @param string $message
 *
 * @return bool
 */
function document_auth($callback = '', $message = '') {

  if ( headers_sent() ) {
    $callback = create_function( '', 'return FALSE;' );
  }

  $success = FALSE;
  if ( is_callable( $callback ) && !empty( $_SERVER['PHP_AUTH_USER'] ) && !empty( $_SERVER['PHP_AUTH_PW'] )) {

    // The callback exists, also user data are sent.
    $success = $callback(
        string_sanitize( CAST_TO_STRING( $_SERVER['PHP_AUTH_USER'] ) ),
        string_sanitize( CAST_TO_STRING( $_SERVER['PHP_AUTH_PW'] ) ) );

    // Unset the PHP_AUT_USER and PHP_AUTH_PW.
    unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
  }

  if ($success) {
    return $success;
  }

  if (!$message) {
    $message = $_SERVER['SERVER_NAME'];
  }
  else {
    $message = CAST_TO_STRING( $message );
  }

  header( 'WWW-Authenticate: Basic realm="' . htmlspecialchars( $message ) . '"' );
  header( 'HTTP/1.1 401 Unauthorized' );
  header( 'Status: 401 Access Denied' );

  return FALSE;
}

/**
 * Clean document content for current moment
 *
 * @return TRUE|string
 *   content or just TRUE
 */
function document_clean() {
  if ( ob_list_handlers() ) {
    return ob_clean();
  }
  return TRUE;
}

/**
 * Show 404 page
 *
 * @param string $message
 */
function show_404($message = '') {

  if ( !headers_sent() ) {
    header('HTTP/1.1 404 Not Found');
  }

  show_error('404 Page Not Found', '<p>The page you requested &quot;' . current_url() . '&quot; was not found.</p><p>&nbsp;</p><div>' . $message . '</div>', 404);
}

/**
 * Show page for errors
 *
 * @param string $title
 * @param int $errno
 * @param string $message
 */
function show_error($title = 'Error', $message = 'An error occurred.', $errno = 000) {

  if ( is_readable( TI_PATH_APP . '/' . TI_FOLDER_CONTROLLER .'/' . $errno . TI_EXT_CONTROLLER) ) {
    include TI_PATH_APP . '/' . TI_FOLDER_CONTROLLER .'/' . $errno . TI_EXT_CONTROLLER;
    exit;
  }
  else {
    echo
    '<!doctype html><html><head><title>' . htmlspecialchars( $title ). '</title></head>',
    '<body><h1>' . $title . '</h1><div>' . $message . '</div></body></html>';
    exit;
  }
}

/**
 * Framework's error handle, show/hide errors, make logs
 * Internal callback, please do not use it directly.
 *
 * @fire error_handler
 *
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 */
function ti_error_handler($errno, $errstr, $errfile, $errline) {

  do_hook( 'error_handler', $errno, $errstr, $errfile, $errline );

  if ( !TI_DEBUG_MODE ) {
    return TRUE;
  }

  elseif ( TI_DEBUG_MODE === -1 ) {
    error_log('TI# URL: ' . URL . '[' . $errline . '][' . $errfile . '] ' . $errstr);
    return TRUE;
  }
  else
    echo
    '<div style="border:1px dotted red;" class="ti_error_handler">',
    '<p><strong>[', $errno, ']</strong> ', $errstr, '</p>',
    '<p><strong>[', $errline, '] ', $errfile, '</strong></p>',
    '</div>';
}

/**
 * Check if number is between given values
 *
 * @param int|float $value
 * @param int|float $minimum
 * @param int|float $maximum
 *
 * @return bool
 */
function between($value = 0, $min = 0, $max = 0) {
  return $min <= $value && $value <= $max ? TRUE : FALSE;
}

// Backward competability for ifsetor in PHP < 5.6.
if ( !function_exists('ifsetor') ):
/**
 * Return variable value, if variable not exists, then create it
* This function is backward competability from php 5.6
*
* @param mixed &$var
* @param mixed $fallback
*
* @return mixed
*/
function ifsetor(&$var, $fallback = NULL) {
  if ( !isset($var) ) {
    $var = $fallback;
  }
  return $var;
}
endif;

if ( !function_exists( 'ifdefor' ) ) {
  /**
   * Return constant value, if constant not exists, then create it
   *
   * @param string $constant
   * @param scalar $fallback
   *
   * @return scalar
   */
  function ifdefor($constant, $fallback = NULL) {
    if ( !defined ($constant ) ) {
      define( $constant, $fallback );
    }
    return constant( $constant );
  }
}

/**
 * Sanitize string from bad characters
 *
 * @param string
 *
 * @return string
 */
function string_sanitize($string = '') {
  return str_replace( array(
      "\0", "\xc2\xad", "\xcc\xb7", "\xcc\xb8", "\xe1\x85\x9F", "\xe1\x85\xA0",
      "\xe2\x80\x80", "\xe2\x80\x81", "\xe2\x80\x82", "\xe2\x80\x83", "\xe2\x80\x84",
      "\xe2\x80\x85", "\xe2\x80\x86", "\xe2\x80\x87", "\xe2\x80\x88", "\xe2\x80\x89",
      "\xe2\x80\x8a", "\xe2\x80\x8b", "\xe2\x80\x8e", "\xe2\x80\x8f", "\xe2\x80\xaa",
      "\xe2\x80\xab", "\xe2\x80\xac", "\xe2\x80\xad", "\xe2\x80\xae", "\xe2\x80\xaf",
      "\xe2\x81\x9f", "\xe3\x80\x80", "\xe3\x85\xa4", "\xef\xbb\xbf", "\xef\xbe\xa0",
      "\xef\xbf\xb9", "\xef\xbf\xba", "\xef\xbf\xbb", "\xE2\x80\x8D"), '', $string);
}

if ( function_exists( 'is_utf8' ) ):
/**
 * Checks whether a string is valid UTF-8.
*
* All functions designed to filter input should use drupal_validate_utf8
* to ensure they operate on valid UTF-8 strings to prevent bypass of the
* filter.
*
* When text containing an invalid UTF-8 lead byte (0xC0 - 0xFF) is presented
* as UTF-8 to Internet Explorer 6, the program may misinterpret subsequent
* bytes. When these subsequent bytes are HTML control characters such as
* quotes or angle brackets, parts of the text that were deemed safe by filters
* end up in locations that are potentially unsafe; An onerror attribute that
* is outside of a tag, and thus deemed safe by a filter, can be interpreted
* by the browser as if it were inside the tag.
*
* The function does not return FALSE for strings containing character codes
* above U+10FFFF, even though these are prohibited by RFC 3629.
*
* @thanks Drupal team
*
* @param $text
*   The text to check.
*
* @return bool
*   TRUE if the text is valid UTF-8, FALSE if not.
*/
function is_utf8($text) {

  if ( strlen($text) == 0 ) {
    return TRUE;
  }
  // With the PCRE_UTF8 modifier 'u', preg_match() fails silently on strings
  // containing invalid UTF-8 byte sequences. It does not reject character
  // codes above U+10FFFF (represented by 4 or more octets), though.
  return ( preg_match('/^./us', $text ) == 1 );
}
endif;

/**
 * Cast given value to integer
 * This function can cast any-type variables,
 * and safe return integer value (objects, arrays, resources,..)
 *
 * @param mixed $var
 * @param int $min
 *   minimal value
 * @param int $max
 *   maximal value
 *
 * @return int
 */
function CAST_TO_INT($var = 0, $min = NULL, $max = NULL) {
  if (is_array($var) && count($var) === 1) {
    $var = array_shift($var);
  }
  $var = is_int($var) ? $var : (is_scalar($var) ? (int) $var : 0);
  if ($min !== NULL && $var < $min) {
    return $min;
  }
  elseif ($max !== NULL && $var > $max) {
    return $max;
  }
  return $var;
}

/**
 * Cast given value to float
 * This function can cast any-type variables,
 * and safe return float value (objects, arrays, resources,..)
 *
 * @param mixed $var
 * @param int $min
 *   minimal value
 * @param int $max
 *   maximal value
 *
 * @return double
 */
function CAST_TO_FLOAT($var = 0, $min = NULL, $max = NULL) {
  if (is_array($var) && count($var) === 1) {
    $var = array_shift($var);
  }
  $var = is_float($var) ? $var : (is_scalar($var) ? (double) $var : 0);
  if ($min !== NULL && $var < $min) {
    return (double) $min;
  }
  elseif ($max !== NULL && $var > $max) {
    return (double) $max;
  }
  return $var;
}

/**
 * Cast given value to boolean
 * if value is single char '0', 'n' or 'no', then will return false
 * also, if value is empty, will return false
 * otherwise will return true
 *
 * @param mixed $var
 *
 * @return bool
 */
function CAST_TO_BOOL($var = FALSE) {

  return (bool) filter_var($var, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
}

/**
 * Cast given value to string
 * This function can cast any-type variables,
 * and safe return string value (objects, arrays, resources,..)
 * also if it is array or object it concatinate their items (properties)
 *
 * @param mixed $var
 * @param int $length
 *   of casted string
 * @param string $implode_arrays
 *   if value is array or object, glue that will implode it
 *
 * @return string
 */
function CAST_TO_STRING($var = '', $length = FALSE, $implode_arrays = ' ') {
  if ( is_array($var) || is_object($var) ) {
    $var = implode_r( $implode_arrays, $var );
  }
  else if ( is_resource($var) ) {
    $var = '';
  }
  $var = (string) $var;
  if ($length !== FALSE && is_int($length) && $length > 0) {
    return substr( $var, 0, $length );
  }
  return $var;
}

/**
 * Cast given value to array
 * This function can cast any-type variables,
 * and safe return array value (objects, arrays, resources,..)
 *
 * <?php
 *   CAST_TO_ARRAY('t1=test&t2=test2&hello=world');
 *   // array(
 *   //    't1' => 'test',
 *   //    't2' => 'test2',
 *   //    'hello' => 'world',
 *   // );
 *
 *   echo CAST_TO_ARRAY('hello world'); // array('hello world')
 * ?>
 *
 * @param mixed value
 *
 * @return array
 */
function CAST_TO_ARRAY( $var = array() ) {
  if ( is_array($var) ) {
    return $var;
  }
  elseif ( is_object($var) ) {
    return (array) $var;
  }
  elseif ( is_string($var) && strpos($var, '&') !== FALSE ) {
    parse_str( $var, $var );
    return $var;
  }
  elseif ( is_scalar($var)) {
    return array($var);
  }
  else {
    return array();
  }
}

/**
 * Cast given value to object
 * This function can cast any-type variables,
 * and safe return array value (objects, arrays, resources,..)
 *
 * <?php
 *   CAST_TO_OBJECT('t1=test&t2=test2&hello=world');
 *   // stdClass {
 *   //     $t1 = 'test';
 *   //     $t2 = 'test2';
 *   //     $hello = 'world';
 *   // }
 *
 *   CAST_TO_OBJECT('hello world') // array('hello world')
 * ?>
 *
 * @param mixed value
 *
 * @return object
 */
function CAST_TO_OBJECT($var = NULL) {
  if ( is_object($var) ) {
    return $var;
  }
  if ( is_array($var) ) {
    return (object) $var;
  }
  elseif ( is_string($var) && strpos($var, '&') !== FALSE ) {
    parse_str( $var, $var );
    return (object) $var;
  }
  elseif ( is_scalar($var)) {
    return (object) $var;
  }
  else {
    return (object) NULL;
  }
}

/**
 * This function working in same way as array_merge(),
 * if strict mode is enabled it strip elements that are not in the model.
 *
 * @param array $array
 * @param array $model
 *
 * @return array
 */
function array_model($array = array(), $model = array(), $strict_mode = TRUE) {

  $model = CAST_TO_ARRAY( $model );

  if ( !$array ) {
    return $model;
  }

  $array = CAST_TO_ARRAY( $array );

  if ( !array_is_assoc( $model ) ) {
    $model = array_fill_keys( $model, NULL );
  }

  if ( $strict_mode ) {
    $the_array = array();
    foreach ($model as $key => $default_value ) {
      $the_array[$key] = isset( $array[$key] ) ? $array[$key] : $default_value;
    }
  }
  else {
    $the_array = array_merge( $model, $array );
  }

  return $the_array;
}

/**
 * Cast array elements to types.
 *
 * @param array|object|string $array
 * @param array|object|string $arry_types
 *
 * @return array
 */
function array_cast($array = array(), $array_types = array()) {

  $array = CAST_TO_ARRAY( $array );
  $array_types = CAST_TO_ARRAY( $array_types );

  $types = array('INT', 'FLOAT', 'DOUBLE', 'TIME', 'DATE', 'DATETIME', 'BOOL', 'ARRAY', 'OBJECT', 'BOOL');

  foreach ( $array as $key => &$val ) {
    if ( !empty($array_types[$key] ) ) {
      $type = strtoupper($array_types[$key]);
      if (in_array($type, $types)) {
        $fn = 'CAST_TO_' . $type;
        $val = $fn($val);
      }
    }
  }

  return $array;
}

/**
 * Check if array is associated or not
 *
 * @param array
 *
 * @return bool
 */
function array_is_assoc($array = array()) {
  return array_keys($array) !== range(0, count($array) - 1);
}

/**
 * Get specified element from array.
 *
 * @param array $array
 * @param int|string $element
 * @param mixed $fallback
 *
 * @return mixed
 */
function array_get_element($array = array(), $element = 0, $fallback = NULL) {
  return isset($array[$element]) ? $array[$element] : $fallback;
}

/**
 * Group array elements by key.
 *
 * @param array
 * @param int|string $key
 *
 * @return array
 */
function array_group_by($array = array(), $key = 0) {

  if (!$key) {
    return $array;
  }
  $tmp = array();
  foreach ($array as $e) {
    if (isset($e[$key])) {
      if (! isset($tmp[$e[$key]])) {
        $tmp[$e[$key]] = array();
      }
      $tmp[$e[$key]][] = $e;
    }
  }
  return $tmp;
}

/**
 * Get array element by directory like path string.
 *
 * @param string $path
 * @param array $array
 *
 * @return array
 */
function array_get_by_path($path = '/', $array = array()) {

  $path = trim( preg_replace( '/\/{2,}/', '/', $path ), '/ ' );

  if (!$path) {
    return $array;
  }

  $path = explode( '/', $path );

  $current_pointer = & $array;
  foreach ( $path as $segment ) {
    if ( isset( $current_pointer[$segment] ) ) {
      $current_pointer = & $current_pointer[$segment];
    }
    else {
      return NULL;
    }
  }
  return $current_pointer;
}

/**
 * Return first element that callback return true for it.
 *
 * <?php
 *
 *   echo array_match_first( 'is_email', array('x', 'test@example.com', 'ti@example.com'), 'a123@example.com' );
 *   // test@example.com
 *
 *   echo array_match_first( 'is_email', array('x', 'test', 'ti'), 'a123@example.com' );
 *   // a123@example.com
 *
 * @param callable $callback
 * @param array $array
 *
 * @return mixed
 */
function array_match_first( $callback, $array = array(), $fallback = NULL ) {
  $array = CAST_TO_ARRAY( $array );
  if ( is_callable( $callback ) && $array ) {
    foreach ( $array as $element ) {
      if ( call_user_func( $callback, $element ) ) {
        return $element;
      }
    }
  }
  return $fallback;
}

/**
 * Recursive implode of array.
 *
 * @param string $glue
 * @param array $pieces
 *
 * @return array
 */
function implode_r($glue = '', $pieces = array()) {
  if ( is_array($glue) ) {
    $pieces = $glue;
    $glue = ', ';
  }
  foreach ( $pieces as &$p ) {
    if ( is_array($p) ) {
      $p = implode_r( $glue, $p );
    }
    elseif ( is_object($p) ) {
      $p = implode_r( $glue, CAST_TO_ARRAY($p) );
    }
  }
  return implode( $glue, $pieces );
}

/**
 * Explode string for a specific number of chunks.
 * Work like as explode() but instead of $limit, here $elements_number,
 * means the returner array, always will be $elements_number long.
 *
 * <?php
 *   print_r( explode_n(',', 'hello,world', 3, '' ) );
 *   // array(
 *   //   0 => 'hello',
 *   //   1 => 'world',
 *   //   2 => '',
 *   // );
 * ?>
 *
 * @param string $delimeter
 * @param string $string
 * @param int $elements_number
 * @param mixed $default_value
 *
 * @return array
 */
function explode_n($delimeter = ',', $string = '', $elements_number = 1, $default_value = NULL) {

  $elements_number = CAST_TO_INT( $elements_number, 1 );

  $array = explode( $delimeter, $string, ( $elements_number ? $elements_number : NULL ));

  if ( !$array ) {
    return array_fill( 0, $elements_number, $default_value );
  }
  elseif ( count( $array ) === $elements_number ) {
    return $array;
  }
  else {
    return array_pad( $array, $elements_number, $default_value );
  }
}

/**
 * Recursive copy of directory.
 *
 * @param string $source
 * @param string $destination
 * @param int $directory_permission
 * @param int $file_permission
 *
 * @return int
 *   count file copied
 */
function copydir($source, $destination, $directory_permission = 0755, $file_permission = 0755) {

  $cf = 0;
  if ( !is_dir($source) ) {
    return 0;
  }
  $dir = opendir($source);

  if ( !is_dir( $destination ) || !mkdir( $destination, $directory_permission, TRUE )) {
    return 0;
  }

  if ( !$dir ) {
    return 0;
  }

  while ( FALSE !== ( $file = readdir( $dir ) ) ) {
    if ( $file != '.' && $file != '..' ) {
      if ( is_dir( $source . '/' . $file ) ) {
        $cf += copydir( $source . '/' . $file, $destination . '/' . $file, $directory_permission, $file_permission );
        chmod( $destination . '/' . $file, $file_permission );
      }
      elseif ( copy( $source . '/' . $file, $destination . '/' . $file ) ) {
        $cf++;
        chmod( $destination . '/' . $file, $directory_permission );
      }
    }
  }
  closedir( $dir );
  return $cf;
}

/**
 * Perform a regex (perl derived) search for files in directory.
 *
 * @param string $directory
 *   directory root to search in (subfolders are included)
 * @param string $pattern
 *   regex pattern
 *
 * @return array
 *   list with matched files
 */
function find_file($directory = '.', $pattern = '', $skip_hidden = TRUE) {

  $list = array();

  if ( !( $dir = opendir( $directory ) ) ) {
    return $list;
  }

  while ( FALSE !== ($file = readdir( $dir)) ) {

    if ( $skip_hidden && $file{0} == '.' ) {
      continue;
    }

    if ( is_dir( $directory . '/' . $file ) ) {
      $list += find_file( $directory . '/' . $file, $pattern, $skip_hidden );
    }
    elseif ( preg_match($pattern, $directory . '/' . $file ) ) {
      $list[] = $directory . '/' . $file;
    }
  }
  closedir( $dir );
  return $list;
}

/**
 * Check the string is a php serialized object or not.
 * copied from wordpress wp-includes/functions.php
 *
 * @thanks WordPress team
 *
 * @param string $data
 *
 * @return bool
 */
function is_serialized($data) {
  // if it isn't a string, it isn't serialized
  if ( ! is_string( $data ) ) {
    return false;
  }
  $data = trim( $data );
  if ( 'N;' == $data ) {
    return true;
  }
  $length = strlen( $data );
  if ( $length < 4 ) {
    return false;
  }
  if ( ':' !== $data[1] ) {
    return false;
  }
  $lastc = $data[$length-1];
  if ( ';' !== $lastc && '}' !== $lastc ) {
    return false;
  }
  $token = $data[0];
  switch ( $token ) {
    case 's' :
      if ( '"' !== $data[$length-2] ) {
        return false;
      }
    case 'a' :
    case 'O' :
      return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
    case 'b' :
    case 'i' :
    case 'd' :
      return (bool) preg_match( "/^{$token}:[0-9.E-]+;\$/", $data );
  }
  return false;
}

/**
 * Get mimetype of the file
 *
 * @param string $file
 *   path to file
 * @param string $fallback_type
 *   if system has no tools to determine type, return fallback
 *
 * @return string
 */
function filemime($file = '', $fallback_type = 'application-octet/stream') {

  if (function_exists( 'finfo_open' ) ) {
    $finfo = finfo_open( FILEINFO_MIME );
    $mimetype = finfo_file( $finfo, $file );
    finfo_close( $finfo );
    $mimetype = explode( ';', $mimetype );
    return array_shift( $mimetype );
  }
  elseif (function_exists( 'mime_content_type' )) {
    return mime_content_type( $file );
  }
  else {
    $it = exif_imagetype( $file );
    if ($it) {
      return image_type_to_mime_type( $it );
    }
    elseif (PHP_OS !== 'Windows') {
      return exec('file -b --mime-type "' . escapeshellcmd( CAST_TO_STRING($file) ) . '"');
    }
  }
  return $fallback_type;
}

/**
 * Send mail based on php's mail() function, or custom one if hook send_mail_function is implemented.
 *
 * @fire send_mail_headers
 * @fire send_mail_function
 *
 * @see mail()
 *
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param mixed $header
 *
 * @return bool
 */
function send_mail($to = '', $subject = '(No subject)', $message = '', $header = '') {

  if ( is_string($header) ) {
    $header = explode("\r\n", $header);
  }
  else {
    $header = CAST_TO_ARRAY($header);
  }

  $header = array_merge(array(
      'MIME-Version' => '1.0',
      'Content-type' => 'text/html; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8bit',
      'X-Mailer' => 'PHP-' . phpversion(),
  ), $header );

  $header = do_hook( 'send_mail_headers', $header );

  $header_ = '';
  foreach ($header as $key => $val) {
    $header_ .= $key . ': ' . $val . (PHP_OS == 'Windows' ? "\n.." : "\r\n");
  }

  unset($header);

  $send_mail_fn = 'mail';

  $send_mail_fn = do_hook( 'send_mail_function', $send_mail_fn );

  return $send_mail_fn( $to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $header_ );
}

/**
 * Perform a http request.
 * It is using libCURL if it's available or fallback to php's fsockopen() if not.
 *
 * @fire http_query_headers
 *
 * @param string $url
 * @param string $method
 * @param mixed $data
 * @param string $timeout
 *
 * @return bool|string
 *   FALSE if error ocure, or string if all is okay and content is obtained.
 */
function http_query($url, $method = 'GET', $data = NULL, $timeout = 30) {

  $default = array(
      'scheme' => 'http',
      'host' => 'localhost',
      'port' => '80',
      'user' => '',
      'pass' => '',
      'path' => '/',
      'query' => '',
      'fragment' => '',
  );

  $query = array_model( $default, parse_url($url) );

  $method = strtoupper( $method );
  if (!in_array($method, array('GET', 'POST', 'OPTIONS'))) {
    return FALSE;
  }

  $data = CAST_TO_ARRAY( $data );

  if ( $method === 'GET' ) {
    parse_str( $query['query'], $query_data );
    $query['query'] = http_build_query( array_merge( $query_data, $query['query'] ), NULL, '&' );
    unset( $query_data );
  }

  // Set headers.
  $headers = array(
      'Content-type' => 'application/x-www-form-urlencoded',
      'User-Agent' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)',
  );
  if ($query['query']) {
    $headers['Content-length'] = strlen( $query['query'] );
  }
  $headers = do_hook( 'http_query_headers', $headers, $query );

  // Prefere curl before fsockopen.
  if ( function_exists( 'curl_init' )) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ( $method === 'POST' ) {
      curl_setopt( $ch, CURLOPT_POST, 1 );
      curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $response = (string) curl_exec($ch);
    curl_close($ch);
    return $response;
  }
  // Fallback to fsockopen
  else {
    $fp = @fsockopen( $query['host'], $query['port'], $errno, $errstr, $timeout );
    if ( !$fp ) {
      error_log( 'ti-framework: http_query:fsockopen[' . $errno . '] ' . $errstr );
      return FALSE;
    }
    else {
      fputs( $fp, $method . ' ' . $query['path'] . ' HTTP/1.1' . "\r\n" );
      fputs( $fp, 'Host: ' . $query['host'] . "\r\n" );
      foreach ($headers as $key => $val) {
        fputs( $fp, $key . ': ' . $val . "\r\n" );
      }
      fputs( $fp, 'Connection: close' . "\r\n\r\n" );
      if ( $query['query'] ) {
        fputs( $fp, $query['query'] . "\r\n\r\n" );
      }
      $result = '';
      while ( !feof($fp) ) {
        $result .= fgets( $fp, 4096 );
      }
      fclose($fp);
      $result = explode( "\r\n\r\n", $result, 2 );
      return isset( $result[1] ) ? $result[1] : '';
    }
  }
}

/**
 * Check if filepath is writeable, if file exists, just check if it is writeable,
 * if it does not exists, then create temporary, id if it's okay, then return true.
 *
 * @param string $filename
 *
 * @return bool
 */
function is_writable_real( $filename = '') {

  if ( file_exists( $filename ) ) {
    return is_writable( $filename );
  }
  else {
    if ( !$handle = fopen( $filename, 'ab' ) ) {
      return FALSE;
    }
    fclose( $handle );
    unlink( $filename );
    return TRUE;
  }
}

/**
 * TI-Framework md5 based hashing method.
 *
 * @param string $string
 *
 * @return string
 */
function make_hash($string = '') {

  if ( !$string ) {
    return '';
  }

  return md5( TI_APP_SECRET . str_rot13( CAST_TO_STRING($string) ) );
}

/**
 * Computes a Hash-based Message Authentication Code (HMAC) using the SHA1 hash function.
 *
 * @param string $key
 * @param string $data
 *
 * @return string
 */
function hmacsha1($key = '', $data = '') {

  $blocksize = 64;
  $hashfunc = 'sha1';

  if ( strlen($key) > $blocksize ) {
    $key=pack( 'H*', $hashfunc($key) );
  }

  $key=str_pad( $key, $blocksize,chr( 0x00 ) );
  $ipad = str_repeat( chr( 0x36 ), $blocksize );
  $opad = str_repeat( chr( 0x5c ), $blocksize );
  $hmac = pack( 'H*', $hashfunc( ($key^$opad) . pack( 'H*', $hashfunc( ($key^$ipad) . $data ) ) ) );

  return $hmac;
}

/**
 * Get max allowed file upload size in bytes.
 *
 * @return int
 */
function fileupload_get_size_limit() {

  static $fileupload_get_size_limit = 0;

  if ( $fileupload_get_size_limit ) {
    return $fileupload_get_size_limit;
  }

  $x = array(
      (int) ini_get( 'upload_max_filesize' ),
      (int) ini_get( 'post_max_size' ),
      (int) ini_get( 'memory_limit' ),
  );

  $x = array_filter($x);

  return min($x) * 1048576;
}

// Backward competable for sys_get_tem_dir().
if ( !function_exists('sys_get_temp_dir') ):
/**
 * Returns directory path used for temporary files
*
* @see http://php.net/manual/en/function.sys-get-temp-dir.php
*
* @return string
*/
function sys_get_temp_dir() {
  if (($temp = getenv('TMP')) !== FALSE) {
    return $temp;
  }
  elseif (($temp = getenv('TEMP')) !== FALSE) {
    return $temp;
  }
  elseif (($temp = getenv('TMPDIR')) !== FALSE) {
    return $temp;
  }
  elseif (($temp = ini_get('upload_tmp_dir')) !== NULL) {
    return $temp;
  }
  else {
    return TI_PATH_APP . '/tmp';
  }
}
endif;

// Backward competable for parse_ini_string().
if ( !function_exists('parse_ini_string') ):
/**
 * Parse a configuration string
*
* @see http://bg2.php.net/parse_ini_string
*
* @param string $string
* @param bool $process_sections
*
* @return array
*/
function parse_ini_string($string, $process_sections = FALSE) {

  $lines = explode(NL, CAST_TO_STRING($string));
  $return = array();
  $in_sect = FALSE;

  foreach ($lines as $line) {
    $line = trim($line);

    if (!$line || $line[0] == '#' || $line[0] == ';') {
      continue;
    }

    if ($line[0] == '[' && $end_index = strpos($line, ']')) {
      $in_sect = substr($line, 1, $end_index - 1);
      continue;
    }

    if (strpos($line, '=')) {
      $keyval = explode('=', $line, 2);
    }
    else {
      $keyval = array('', $line);
    }

    if ($process_sections && $in_sect) {
      $return[$in_sect][trim($keyval[0], '"\' ')] = trim($keyval[1], '"\' ');
    }
    else {
      $return[trim($keyval [0], '"\' ')] = trim($keyval[1], '"\' ');
    }
  }
  return $return;
}
endif;

/**
 * Quick way to set cookie.
 * Better to use cookie_set() instead of php's cookie functions.
 *
 * @param string $name
 * @param mixed $value
 * @param int $expire
 * @param string $path
 * @param string $domain
 * @param bool $secure
 * @param bool $httponly
 *
 * @return bool
 */
function cookie_set($name, $value = '', $expire = 0, $path = '', $domain = '', $secure = FALSE, $httponly = FALSE) {
  $value = CAST_TO_STRING($value);

  $_COOKIE[$name] = $value;
  return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
}

/**
 * Get cookie value.
 *
 * @param string $name
 * @param mixed $fallback
 *
 * @return mixed
 */
function cookie_get($name, $fallback = NULL) {
  return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $fallback;
}

/**
 * Store value in the session bus.
 *
 * @param string|array $key
 * @param mixed $value
 *
 * @return bool
 */
function session_set($key = '', $value = '') {

  if (is_array($key) || is_object($key)) {
    foreach ($key as $k => $v) {
      session_set($k, $v);
    }
    return TRUE;
  }

  return ($_SESSION[CAST_TO_STRING($key)] = $value);
}

/**
 * Get value stored in the session.
 *
 * @param string $key
 * @param mixed $fallback
 *
 * @return mixed
 */
function session_get($key = '', $fallback = NULL) {

  if (is_array($key) || is_object($key)) {
    $result = array();
    foreach ($key as $k) {
      $result[$k] = session_get($k, $fallback);
    }
    return $result;
  }
  else {
    $key = CAST_TO_STRING($key);
    return isset( $_SESSION[$key] ) ? $_SESSION[$key] : $fallback;
  }
}

/**
 * Create nonce, for safe private urls or keys
 *
 * @param string $id
 */
function create_nonce($id = 'global') {
  $n = make_hash( microtime() );
  session_set( '_ti_nonce_' . make_hash( $id ), $n );
  return $n;
}

/**
 * Check nonce.
 *
 * @param string $id
 * @param string $nonce_key
 *
 * @return bool
 */
function check_nonce($id = 'global', $nonce_key = '') {
  return ( strcmp( CAST_TO_STRING($nonce_key), session_get('_ti_nonce_' . make_hash( $id )) === 0));
}

/**
 * Add hook callback.
 *
 * <?php
 *   add_hook('test', 'hook_hello_world');
 *   function hook_hello_world($content, $action = '') {
 *     if ($action === 'test1') {
 *       return $content . '!';
 *     }
 *     return $content;
 *   }
 *
 *   // PHP 5.3
 *   add_hook( 'test', function($content) {
 *     return $content . ' :)';
 *   });
 * ?>
 *
 * @param string $hook_name
 * @param string|callback $function_name
 * @param int $priority
 *
 * @return bool
 */
function add_hook($hook_name, $function, $priority = 10) {

  global $_HOOKS;

  if ( !is_array($_HOOKS) ) {
    $_HOOKS = array();
  }

  if ( !isset($_HOOKS[$hook_name]) ) {
    $_HOOKS[$hook_name] = array();
  }

  if ( !isset($_HOOKS[$hook_name][$priority]) ) {
    $_HOOKS[$hook_name][$priority] = array();
  }

  $_HOOKS[$hook_name][$priority][] = $function;

  return TRUE;
}

/**
 * Fire a hook.
 *
 * <?php
 *   $a = 'Hello World';
 *
 *   add_hook('test', 'hook_hello_world');
 *   function hook_hello_world($content, $action = '') {
 *     if ($action === 'test1') {
 *       return $content . '!';
 *     }
 *     return $content;
 *   }
 *
 *   // PHP 5.3
 *   add_hook( 'test', function($content) {
 *     return $content . ' :)';
 *   });
 *
 *   echo do_hook( 'test', $a, 'test1'); // Hello World! :)
 *   echo do_hook( 'test', $a, 'nope');  // Hello World :)
 * ?>
 *
 * @param string $hook_name
 * @param mixed $value
 * @param mixed $arg1
 * @param mixed $arg2
 * @param mixed ...
 * @param mixed $argN
 *
 * @return mixed
 */
function do_hook($hook_name, $value = NULL) {

  if ( !has_hook( $hook_name ) ) {
    return $value;
  }

  global $_HOOKS;

  ksort( $_HOOKS[$hook_name] );

  $args = func_get_args();
  unset( $args[0] );

  foreach ( $_HOOKS[$hook_name] as $hook_priority ) {
    foreach ( $hook_priority as $hook ) {
      if ( is_callable($hook) ) {
        $value = call_user_func_array($hook, $args);
      }
    }
  }

  return $value;
}

/**
 * Clear all callbacks for a hook_name.
 *
 * @param string $hook_name
 *
 * @return bool
 */
function delete_hook($hook_name) {

  if (!has_hook($hook_name)) {
    return FALSE;
  }

  global $_HOOKS;

  $_HOOKS[$hook_name] = array();

  return TRUE;
}

/**
 * Check if there is a callbacks for a hook.
 *
 * @param string $hook_name
 *
 * @return FALSE|int
 *   return the number of callbacks assigned or FALSE if there is no.
 */
function has_hook($hook_name) {
  global $_HOOKS;
  return empty( $_HOOKS[$hook_name] ) ? FALSE : count( $_HOOKS[$hook_name] );
}

/**
 * Put data in to cache bin.
 *
 * @param string $key
 * @param mixed $data
 *
 * @return bool
 */
function cache_put($key = '', $data = NULL) {

  $file = md5( dirname($key) ) . md5( $key );

  $directory = TI_PATH_APP . '/' . TI_FOLDER_CACHE . '/';

  if ( !is_dir($directory) && !mkdir( $directory, 0700, TRUE )) {
    return FALSE;
  }

  return file_put_contents( $directory . $file, CAST_TO_STRING( $data ) ) ? TRUE : FALSE;
}

/**
 * Get data for a cache id from a bin.
 *
 * @param string $key
 * @param unknown_type $expire
 *
 * @return bool
 */
function cache_get($key = '', $expire = '3600') {
  $cache_exists = cache_exists( $key, $expire );
  if ($cache_exists === FALSE) {
    return FALSE;
  }
  return file_get_contents( $cache_exists );
}

/**
 * Check if cache exist for a key and check if it is not expired.
 *
 * @param string $key
 * @param int $expire
 *
 * @return string|bool
 *   file to the cache file.
 */
function cache_exists($key = '', $expire = '3600') {

  $file = md5( dirname($key) ) . md5( $key );

  $directory = TI_PATH_APP . '/' . TI_FOLDER_CACHE . '/';

  if (is_readable($directory . $file)) {
    if (filemtime($directory . $file) < (time() + $expire)) {
      return $directory . $file;
    }
  }

  return FALSE;
}

/**
 * Delete cache for given key.
 *
 * @param string $key
 */
function cache_delete($key = '') {

  $file = TI_PATH_APP . '/' . TI_FOLDER_CACHE . '/' . md5( dirname( $key ) ) . '-' . md5( $key );

  if ( file_exists($file) ) {
    return unlink($file);
  }

  return TRUE;
}

/**
 * Escape value to be suitable to use in forms.
 *
 * @param mixed $string
 *
 * @return string
 */
function esc_attr($string = '') {
  return htmlspecialchars( CAST_TO_STRING($string), ENT_QUOTES );
}

/**
 * For selected helper.
 *
 * @param mixed $current
 * @param mixed $default
 * @param bool $return
 *
 * @return NULL|string
 */
function selected($current = '', $default = 1, $return = TRUE) {
  if ( CAST_TO_STRING($current) === CAST_TO_STRING($default) ) {
    if ( $return ) {
      return ' selected="selected"';
    }
    else {
      echo ' selected="selected"';
    }
  }
}

/**
 * For checked helper.
 *
 * @param mixed $current
 * @param mixed $default
 * @param bool $return
 *
 * @return NULL|string
 */
function checked($current = '', $default = 1, $return = TRUE) {
  if ( CAST_TO_STRING($current) === CAST_TO_STRING($default) ) {
    if ($return) {
      return ' checked="checked"';
    }
    else {
      echo ' checked="checked"';
    }
  }
}

/**
 * Build options list suitable for use in <select> tag.
 *
 * @param array $array
 * @param scalar $default_value
 * @param bool $return
 *
 * @return string
 */
function form_options($array = array(), $default_value = NULL, $return = TRUE) {

  $array = CAST_TO_ARRAY( $array );

  if (!array_is_assoc($array)) {
    $array = array_combine( array_values($array), array_values($array) );
  }

  $options = '';

  foreach ( $array as $key => $val ) {
    $options .= '<option value="' . esc_attr($key) . '"' . selected( $key, $default_value ). '>' . esc_attr($val) . '</option>';
  }

  if ($return) {
    return $options;
  }

  echo $options;
}

/**
 * Find links in text and make them clickable (anchors).
 *
 * @param string $text
 * @param int $anchor_length
 *
 * @return string
 */
function make_clickable($text = '', $anchor_length = 40, $attributes = array('target' => '_blank')) {

  $text = CAST_TO_STRING( $text );
  $anchor_length = CAST_TO_INT( $anchor_length );

  $attributes = CAST_TO_ARRAY( $attributes );
  $custom_attributes = '';
  foreach ($attributes as $key => $val) {
    $custom_attributes .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
  }

  $text = preg_replace( '@(?<![.*">])\b(?:(?:https?|ftp|file)://|[a-z]\.)[-A-Z0-9+&#/%=~_|$?!:,.]*[A-Z0-9+&#/%=~_|$]@ei', '\'<a href="\0"'.$custom_attributes.'>\'.substr_middle("\\0", ' . $anchor_length . ').\'</a>\'', $text );
  $text = preg_replace('#\b([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6})\b#ei', '\'<a href="mailto:\'.urlencode("\\0").\'" '.$custom_attributes.'>\'.substr_middle("\\0", ' . $anchor_length . ').\'</a>\'', $text);

  return $text;
}

/**
 * Strip all attributes from html tags.
 *
 * @param string $html
 */
function strip_attributes($html = '') {
  return preg_replace( '/<\s*([a-z]+)([^>]*)>/i', '<//1>', $html );
}

/**
 * Evalute php
 *
 * @param string $string
 * @param array $local_variables
 * @param bool $return
 * @param string &$result
 *
 * @return string output
 */
function evalute_php($string, $local_variables = array(), $return = FALSE, &$result = NULL) {

  if ( $return ) {
    ob_start();
  }
  extract( CAST_TO_ARRAY($local_variables) );
  unset( $local_variables );

  $result = eval( 'error_reporting(' . CAST_TO_INT(DEBUG_MODE) . ');?>' . $string . '<?php ');

  if ($return) {
    return ob_get_clean();
  }
}

/**
 * Evalute math expressions, and return calculated in a double format,
 * or return FALSE if cause a error.
 *
 * <?php
 *   var_dump( evalute_math( '1+1(3x3)+1*2+8-4/2' ) );
 *   // (double) 18
 * ?>
 *
 * @param string $math_string
 * @param string &$sanitized_string
 *
 * @return double|FALSE
 */
function evalute_math( $string = '', &$sanitized_string = '' ) {

  // Replace next chars with more suitable ones.
  $string = strtr( $string, array('\\' => '/', ',' => '.', 'x' => '*'));

  // Replace all except numbers and math signs with empty.
  $string = preg_replace( '#[^0-9\.\+\-\/\*\(\)]#', '', $string );

  $string = trim( $string );
  $string = trim( $string, '*/' );

  // Remove all duplicating simbols and left the first one.
  $string = preg_replace( '#([\*\/\+\-])[\*\/\+\-]+#', '\\1', $string );

  $string = preg_replace( '#\s+#', '', $string );

  // Remove all simbols left to closing bracket.
  $string = preg_replace( '#(\d+)\s*\(#', '\\1*(', $string );

  //Remove all symbols right to closing bracket.
  $string = preg_replace( '#\)\s*(\d+)#', ')*\\1', $string );

  // Autoclosing missed cloed brackets.
  $bracket_c = substr_count( $string, ')' );
  $bracket_o = substr_count( $string, '(' );
  if ($bracket_c > $bracket_o) {
    $string = str_repeat('(', $bracket_c-$bracket_o) . $string;
  }
  elseif ($bracket_c < $bracket_o) {
    $string = $string . str_repeat(')', $bracket_o-$bracket_c);
  }

  // Removing mispeling brackets.
  $string = preg_replace( '#\(([^0-9(-]+)#', '(', $string );
  $string = preg_replace( '#([^0-9)]+)\)#', ')', $string );

  // Remove () and )(
  $string = strtr( $string, array(')(' => '', '()' => ''));

  // Autoclosing missed cloed brackets.
  $bracket_c = substr_count( $string, ')' );
  $bracket_o = substr_count( $string, '(' );
  if ($bracket_c > $bracket_o) {
    $string = str_repeat( '(', $bracket_c-$bracket_o ) . $string;
  }
  elseif ($bracket_c < $bracket_o) {
    $string = $string . str_repeat(')', $bracket_o-$bracket_c);
  }

  $string = @preg_replace_callback( '#([\d.]+)#',
      create_function( '$matches', 'return (float) $matches[0];' ),
      $string );

  $sanitized_string = $string;

  $string = 'return ' . $string . ';';

  // Evalute the sanitized math expression.
  $fx = create_function( '$string', 'return ' . $string . ';' );
  $x = $fx( $string );

  if ( is_numeric($x) ) {
    return (double) $x;
  }

  return FALSE;
}

/**
 * Check if string is compa separated numerics.
 *
 * <?php
 *   is_123_set('1,2,3,4');
 *   // TRUE
 *
 *   is_123_set('1,2,a,b,c');
 *   // FALSE
 * ?>
 *
 * @param string $string
 * @param int $min
 *   minimum values
 * @param int $max
 *   maximum values
 *
 * @return bool
 */
function is_123_set($string = '', $min = 0, $max = NULL) {
  return preg_match('/^[0-9]+(\s{0,1}\,\s{0,1}[0-9]+){' .
      ($min ? intval($min) - 1 : 0) . ',' .
      ($max ? intval($max) - 1 : NULL) . '}$/', CAST_TO_STRING($string)) ? TRUE : FALSE;
}

/**
 * Check if string is coma separated A-Za-z
 *
 * <?php
 *   is_123_set('a,b,cd,ef');
 *   // TRUE
 *
 *   is_123_set('1,2,a,b,c');
 *   // FALSE
 * ?>
 *
 * @param string $string
 * @param int $min
 *   minimum values
 * @param int $max
 *   maximum values
 *
 * @return bool
 */
function is_abc_set($string = '', $min = 0, $max = NULL) {
  return preg_match('/^[a-z]+(\s{0,1}\,\s{0,1}[a-z]+){' .
      ($min ? intval($min) - 1 : 0) . ',' .
      ($max ? intval($max) - 1 : NULL) . '}$/i', CAST_TO_STRING($string)) ? TRUE : FALSE;
}

/**
 * Check if string is coma separated alphanumeric
 *
 * <?php
 *   is_123_set('1,2,3,4');
 *   // TRUE
 *
 *   is_123_set('1,2,a,b,c');
 *   // TRUE
 * ?>
 *
 * @param string $string
 * @param int $min
 *   minimum values
 * @param int $max
 *   maximum values
 *
 * @return bool
 */
function is_123abc_set($string = '', $min = 0, $max = NULL) {
  return preg_match('/^[0-9a-z]+(\s{0,1}\,\s{0,1}[0-9a-z]+){' .
      ($min ? intval($min) - 1 : 0) . ',' .
      ($max ? intval($max) - 1 : NULL) . '}$/i', CAST_TO_STRING($string)) ? TRUE : FALSE;
}

/**
 * Check if string is coma separated wordset
 *
 * @param string $string
 * @param int $min
 *   minimum values
 * @param int $max
 *   maximum values
 *
 * @return bool
 */
function is_word_set($string = '', $min = 0, $max = NULL) {
  return preg_match('/^[^\/\\$&?]+(\s{0,1}\,\s{0,1}[^\/\\$&?]+){' .
      ($min ? intval($min) - 1 : 0) . ',' .
      ($max ? intval($max) - 1 : NULL) . '}$/i', CAST_TO_STRING($string)) ? TRUE : FALSE;
}

/**
 * Check if string is valid email.
 *
 * @fire is_email
 *
 * @param string $string
 * @param int $min
 *   minimum values
 * @param int $max
 *   maximum values
 *
 * @return bool
 */
function is_email($string = '') {
  $pattern = '^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.(?:[A-Z]{2}|com|org|net|edu|gov|mil|biz|info|mobi|name|aero|asia|jobs|museum)$i';
  $result = preg_match( $pattern, CAST_TO_STRING($string) ) ? TRUE : FALSE;
  return do_hook( 'is_email', $result, $string );
}

/**
 * Check if string is valid ip address
 *
 * @param string $string
 * @param bool $ipv6
 *
 * @return bool
 */
function is_ip($string = '', $ipv6 = FALSE) {
  return (bool) filter_var( $string, FILTER_VALIDATE_IP, ( $ipv6 ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4 ) );
}

/**
 * Convert minutes to hours (HH:MM)
 *
 * @param int $mins
 * @param string $format
 *
 * @return string
 */
function min_to_hour($mins = 0, $format = '%02d:%02d') {
  return sprintf( $format, floor($mins / 60), round(abs($mins) % 60, 2) );
}

/**
 * Convert seconds to hours (HH:MM:SS)
 *
 * @param int $seconds
 * @param string $format
 *
 * @return string
 */
function sec_to_min_hour($secs = 0, $format = '%02d:%02d:%02d') {
  return sprintf( $format, floor($secs / 3600), (floor($secs / 60) % 60), round(abs($secs) % 60, 2) );
}

/**
 * Determines the difference between two timestamps.
 *
 * The difference is returned in a human readable format such as "1 hour",
 * "5 mins", "2 days".
 *
 * @thanks WordPress team
 *
 * @param int $from Unix timestamp from which the difference begins.
 * @param int $to Optional. Unix timestamp to end the time difference. Default becomes time() if not set.
 *
 * @return string Human readable time difference.
 */
function human_time_diff( $from, $to = '' ) {

  if ( empty($to) ) {
    $to = time();
  }
  $diff = (int) abs($to - $from);

  if ($diff <= 3600) {
    $mins = round($diff / 60);
    if ($mins <= 1) {
      $mins = 1;
    }
    /* translators: min=minute */
    $since = sprintf(_n('%s min', '%s mins', $mins), $mins);
  }

  elseif (($diff <= 86400) && ($diff > 3600)) {
    $hours = round($diff / 3600);
    if ($hours <= 1) {
      $hours = 1;
    }
    $since = sprintf(_n('%s hour', '%s hours', $hours), $hours);
  }

  elseif ($diff >= 86400) {
    $days = round($diff / 86400);
    if ($days <= 1) {
      $days = 1;
    }
    $since = sprintf(_n('%s day', '%s days', $days), $days);
  }

  return $since;
}

/**
 * Simple alternator implementation.
 *
 * <?php
 *   echo alternator( 'one', 'two', 'three' ); // one
 *   echo alternator( 'one', 'two', 'three' ); // two
 *   echo alternator( 'one', 'two', 'three' ); // three
 *   echo alternator( 'one', 'two', 'three' ); // one
 *   echo alternator( 'one', 'two', 'three' ); // two
 * ?>
 *
 * @param mixed $arg1
 * @param mixed $arg2
 * @param mixed ...
 * @param mixed $argN
 *
 * @return mixed
 */
function alternator() {

  static $i;

  if ( func_num_args() == 0 ) {
    $i = 0;
    return '';
  }
  $args = func_get_args();
  return $args[ ($i++ % count($args)) ];
}

/**
 * Calculate random string from a charset.
 *
 * @param int $len
 * @param string $charlist
 *
 * @return string
 */
function randstr($len = 4, $charlist = 'a-zA-Z0-9') {

  $charlist = str_replace(
      array('0-9', 'a-z', 'A-Z'),
      array(
          '0123456789',
          'wertyuiopasdfghjklzxcvbnm',
          'QWERTYUIOPASDFGHJKLZXCVBNM',
      ),
      $charlist
  );

  $string = '';
  $charlist_len = strlen($charlist) - 1;

  for ($i = 0; $i < $len; $i++) {
    $string .= $charlist{rand(0, $charlist_len)};
  }
  return $string;
}

/**
 * Convert size to byte format.
 *
 * @param int $num
 * @param int $precision
 *
 * @return string
 */
function byte_format($num = 0, $precision = 2) {
  if ($num < 1024) {
    return sprintf(__('%s b'), round( $num, $precision ));
  }
  elseif ($num < 1048576) {
    return sprintf(__('%s KB'), round( $num / 1024, $precision ));
  }
  elseif ($num < 1073741824) {
    return sprintf(__('%s MB'), round( $num / 1048576, $precision ));
  }
  elseif ($num < 1099511627776) {
    return sprintf(__('%s GB'), round( $num / 1073741824, $precision ));
  }
  elseif ($num < 1125899906842624) {
    return sprintf(__('%s TB'), round( $num / 1099511627776, $precision ));
  }
  else {
    return sprintf(__('%s PB'), round( $num / 1125899906842624, $precision ));
  }
}

/**
 * Make path like string more readable.
 *
 * <?php
 *   echo path_to_human('category/test-products');
 *   // Category Test Products
 * ?>
 *
 * @param string $string
 *
 * @return string
 */
function path_to_human($string = '') {
  $string = CAST_TO_STRING( $string );
  $string = preg_replace( '/[\t\s\_\-\.\=\?\+]/', ' ', $string );
  $string = preg_replace( '/\s{2,}/', ' ', $string );
  $string = ucwords( strtolower($string) );
  return $string;
}

/**
 * Make text look like a path.
 *
 * <?php
 *   echo human_to_path('Category/Test Products');
 *   // category/test-products
 *
 *   echo human_to_path('33 Example MS Word document.docx');
 *   // 33-example-ms-word-document.docx
 * ?>
 *
 * @param string $string
 *
 * @return string
 */
function human_to_path($string = '') {
  $string = CAST_TO_STRING( $string );
  $string = preg_replace( '/[\s\}\{\]\[\|\,\?\<\>]/', '-', $string );
  $string = preg_replace( '/\[\-\_\.]{2,}/', '$1', $string );
  $string = preg_replace( '{(.)\1+}', '$1', $string );
  $string = strtolower( transliterate($string) );
  return $string;
}

/**
 * Convert path a like string to assoc array.
 *
 * <?php
 *   echo path_to_assoc('category/example/tag/test');
 *   // array( 'category' => 'example', 'tag' => 'test' )
 * ?>
 *
 * @param string $path
 * @param int $offset
 *
 * @return array
 */
function path_to_assoc($path = '', $offset = 0) {
  $array = trim( preg_replace( '#\/{2,}#', '/', $path ), '/ \\' );
  if ($offset) {
    $array = array_splice( $array, $offset );
  }
  $assoc_array = array();
  while ( $array ) {
    $key = array_shift($array);
    if ( $key !== FALSE ) {
      $assoc_array[$key] = array_shift( $array );
    }
  }
  return $assoc_array;
}

/**
 * Convert assoc array to path.
 *
 * <?php
 *   echo assoc_to_path(array( 'category' => 'example', 'tag' => 'test' )); // category/example/tag/test
 * ?>
 *
 * @param array $array.
 *
 * @return string
 */
function assoc_to_path($array = array()) {
  $path = array();
  foreach ( CAST_TO_ARRAY($array) as $key => $val ) {
    $path[] = $key;
    $path[] = $val;
  }
  return implode( '/', $path );
}

/**
 * Text formatter. Convert some chars to special ones, get more visual kandy.
 *
 * @fire text_pretty_format
 *
 * @param string $string
 *
 * @return string
 */
function text_pretty_format($string = '') {

  $string = strtr($string, array(
      '---' => '&#8212;&#8212;',
      '--' => '&#8212;',
      '...' => '&#133;',
      '>>' => '&#187;',
      '<<' => '&#171;',
      '(tm)' => '&#8482;',
      '+-' => '&#177;',
      '(c)' => '&#169',
      '(r)' => '&#174',
      '$' => '&#36;'
  ));
  $string = preg_replace( '#\"([^\"]*)\"#', '&ldquo;$1&rdquo;', $string );
  $string = preg_replace( '#\'([^\']*)\'#', '&lsquo;$1&rsquo;', $string );

  do_hook( 'text_pretty_format', $string );

  return $string;
}

/**
 * Get excerpt from a text.
 *
 * @param string $string
 * @param int $limit
 * @param string $end_char
 *
 * @return string
 */
function excerpt($string = '', $limit = 100, $end_char = '&#8230;') {

  $string = CAST_TO_STRING($string);

  preg_match( '/^\s*+(?:\S++\s*+){1,' . CAST_TO_INT($limit) . '}/', $string, $matches );

  if ( strlen($string) == strlen($matches[0]) ) {
    $end_char = '';
  }

  unset( $string );

  return rtrim( $matches[0] ) . $end_char;
}

/**
 * Same like a substr() but it remove the middle and presafe start and ends.
 *
 * @param string $string
 * @param int $length
 * @param string $separate
 */
function substr_middle($string = '', $length = 255, $separate = '&#8230;') {

  $string = CAST_TO_STRING($string);
  $string_length = strlen($string);
  $length = CAST_TO_INT($length);
  $separate = CAST_TO_STRING($separate);

  if ( !$length || ($string_length <= $length + strlen($separate)) ) {
    return $string;
  }

  return substr( $string, 0, ceil($length / 2)) . $separate . substr($string, -1 * ceil($length / 2) );
}

/**
 * Strip duplicated chars.
 *
 * <?php
 *   echp strip_duplicated_chars('Helllo MyyFriend __1'); // Helo MyFriend _1
 * ?>
 *
 * @param string $string
 *
 * @return string
 */
function strip_duplicated_chars($string = '') {
  return preg_replace( '{(.)\1+}', '$1', $string );
}

/**
 * Upper word by a char.
 *
 * <?php
 *   echo ucwords_by_char('hello-world', '-'); // Hello World
 * ?>
 *
 * @param string $string
 * @param string $char
 *
 * @return string
 */
function ucwords_by_char($string = '', $char = '-') {
  return strtr( ucwords( strtr( trim( $string ), $char, ' ' . $char ) ), ' ', $char );
}

/**
 * Strip more than one whitespace.
 *
 * @param string $text
 *
 * @return string
 */
function strip_whitespaces($text = '') {
  return trim( preg_replace( '/(\t|\s)+/', ' ', $text ) );
}

/**
 * Decimal to roman
 *
 * <?php
 *   echo dec_to_roman(8); // VIII
 *   echo dec_to_roman(21); // XXI
 * ?>
 *
 * @param int $decimal
 *
 * @return string
 */
function dec_to_roman($decimal = 0) {

  $decimal = CAST_TO_INT( $decimal );
  $result = '';

  $romanians = array(
      'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90,
      'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);

  foreach ( $romanians as $key => $val ) {
    $result .= str_repeat( $key, $decimal / $val );
    $decimal = $decimal % $val;
  }
  return $result;
}

/**
 * Number to month name.
 *
 * @param int $num
 * @param bool $long_names
 *
 * @return string
 */
function num_to_month($num = 0, $long_names = FALSE) {
  $months = $long_names
  ? array(1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april', 5 => 'may', 6 => 'june',
      7 => 'july', 8 => 'august', 9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december')
      : array(1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr', 5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'aug',
          9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dec');
  $num = abs( CAST_TO_INT($num) );
  return isset( $months[$num] ) ? __( $months[$num] ) : '';
}

/**
 * Get sql style timestamp
 *
 * @param int $timestamp
 *
 * @return string
 */
function sql_now($timestamp = 0) {
  return !$timestamp
  ? date( 'Y-m-d H:i:s' )
  : date( 'Y-m-d H:i:s', $timestamp );
}

/**
 * Get html5 suitable input type from sql column type
 *
 * @fire sql_type_to_widget
 *
 * @param string $type
 *
 * @return string
 */
function sql_type_to_widget( $type = '' ) {

  $type = CAST_TO_STRING( $type );

  if ( preg_match('/^(tinyint\(1\)|bool|boolean)/i', $type) ) {
    $widget = 'BOOLEAN';
  }

  elseif ( preg_match('/^(tinyint|mediumint|int|smallint|bigint|numeric|real|double|float)/i', $type)) {
    $widget = 'NUMBER';
  }

  elseif ( preg_match('/^(tinytext|text|mediumtext|longtext)/i', $type) ) {
    $widget = 'TEXTAREA';
  }

  elseif ( preg_match('/^(tinyblob|blob|mediumblob|longblob)/i', $type) ) {
    $widget = 'FILE';
  }

  elseif ( preg_match('/^(timestamp|datetime)/i', $type) ) {
    $widget = 'DATETIME';
  }

  elseif ( preg_match('/^date/i', $type) ) {
    $widget = 'DATE';
  }

  elseif ( preg_match('/^time/i', $type) ) {
    $widget = 'TIME';
  }

  elseif ( preg_match('/^set\(.*\)/i', $type) ) {
    $widget = 'CHECKBOX';
  }

  elseif ( preg_match('/^enum\(.*\)/i', $type) ) {
    $widget = 'SELECT';
  }

  else {
    $widget = 'TEXT';
  }

  return do_hook( 'sql_type_to_widget', $widget, $type );
}

/**
 * Check if string is correct time interval.
 *
 * @param string $string
 *
 * @return bool
 */
function sql_is_time_interval( $string = '' ) {
  return preg_match( '/^\d{1,3}\s(MINUTE|HOUR|DAY|WEEK|MONTH|YEAR)$/i', CAST_TO_STRING( $string ) )
  ? TRUE
  : FALSE;
}

/**
 * Get available options in enum/set column type
 *
 * @param string $columntype
 *
 * @return array
 */
function sql_get_enum_values($columntype = '') {

  $columntype = CAST_TO_STRING($columntype);

  if ( preg_match('/^set\((.*)\)/i', $columntype, $matches) || preg_match('/^enum\((.*)\)/i', $columntype, $matches) ) {
    if ( count($matches) > 0 ) {
      $matches = explode( ',', array_pop( $matches ) );
      foreach($matches as $key => $val) {
        $matches[$key] = trim( $val, '\'"' );
      }
      return $matches;
    }
  }

  return array();
}

/**
 * TI-framework transliteration implementation.
 *
 * <?php
 *   echo transliterate('Здравей Свят!'); // Zdravei Svyat!
 * ?>
 *
 * @fire transliterate
 *
 * @param string $string
 * @param bool $from_latin
 *
 * @return string
 */
function transliterate($string = '', $from_latin = FALSE) {

  if ( !is_string($string) ) {
    return '';
  }

  $ctable = array(
      // cyr
      'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'E', 'Ж' => 'ZH', 'З' => 'Z', 'Й' => 'I',
      'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U',
      'Ф' => 'F', 'Х' => 'KH', 'Ц' => 'TS', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SHT', 'Ы' => 'Y', 'Э' => 'E', 'Ю' => 'YU',
      'Я' => 'YA', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
      'и' => 'i', 'й' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
      'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sht', 'ы' => 'y',
      'э' => 'e', 'ю' => 'yu', 'я' => 'ya', 'Ъ' => 'A', 'ъ' => 'a', 'Ь' => 'Y', 'ь' => 'y',
      // greek
      'Σ' => 'S', 'σ' => 's', 'ς' => 's', 'Ψ' => 'PS', 'Ω' => 'O', 'Ξ' => 'X', 'Θ' => 'TH', 'Δ' => 'D', 'ή' => 'NG', 'ΤΘ' => 'TTH',
      'ΤΖ' => 'TJ', 'γ' => 'g', 'ζ' => 'z', 'ξ' => 'x', 'φ' => 'F', 'Φ' => 'f', 'ω' => 'o', 'ι' => 'i', 'δ' => 'd', 'β' => 'b',
      'α' => 'a', 'π' => 'pe', 'ϻ' => 'sin', 'ϝ' => 'waw',
      // simple mandarin
      'ㄔ' => 'c', 'ㄚ' => 'a', 'ㄞ' => 'ai', 'ㄢ' => 'an', 'ㄤ' => 'ar', 'ㄅ' => 'b', 'ㄅ' => 'be', 'ㄠ' => 'sw', 'ㄓ' => 'jw'
  );

  $ctable = do_hook( 'transliterate', $ctable );

  if ( $from_latin ) {
    array_flip( $ctable );
  }

  return strtr( $string, $ctable );
}

/**
 * @} End of "defgroup generic functions".
 */


/**
 * @defgroup framework bootstrap
 * @{
 *
 * Booting the ti-framework, after all functions and classes are defined.
 */

// Set the framework version.
define( 'TI_FW_VERSION', '0.9.9.0' );

// Start the timer.
define( 'TI_TIMER_START', microtime( TRUE ) );

// Check for PHP version.
if ( version_compare( PHP_VERSION, '5.2.0', '<' ) ) {
  die( 'REQUIRE PHP >= 5.2.0' );
}

// Define NL (new line) constant.
defined( 'NL' ) or define('NL', "\n");

// Set path framework.
define( 'TI_PATH_FRAMEWORK', pathinfo( __FILE__, PATHINFO_DIRNAME ) );

// Set disabled mod rewrite.
defined( 'TI_DISABLE_MOD_REWRITE' ) or define( 'TI_DISABLE_MOD_REWRITE',  FALSE );

// Set default home url.
defined( 'TI_HOME' ) or define( 'TI_HOME', 'index' );

// Set appsecret salt.
defined( 'TI_APP_SECRET' ) or define( 'TI_APP_SECRET', 'ti-framework' );

// Set the debugging mode to false.
defined( 'TI_DEBUG_MODE') or define( 'TI_DEBUG_MODE', FALSE );

// Detect application path.
if ( !defined( 'TI_PATH_APP' ) ) {
  define( 'TI_PATH_APP', pathinfo( realpath( $_SERVER['SCRIPT_FILENAME'] ), PATHINFO_DIRNAME )  . '/application' );
  if ( !TI_PATH_APP ) {
    die( 'Application path not defined.' );
  }
}

// Detect the webpath.
defined( 'TI_PATH_WEB' ) or define( 'TI_PATH_WEB', trailingslashit( pathinfo( $_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME ) ) );

// Internationalization.
defined( 'TI_LOCALE' )              or define( 'TI_LOCALE', 'en_US' );
defined( 'TI_FOLDER_LOCALE' )       or define( 'TI_FOLDER_LOCALE', 'locale' );
defined( 'TI_TIMEZONE' )            or define( 'TI_TIMEZONE', 'GMT' );

// Set MVC alike folders, if they are not set already by the config file.
defined( 'TI_FOLDER_INC' )          or define( 'TI_FOLDER_INC', 'includes' );
defined( 'TI_FOLDER_VIEW' )         or define( 'TI_FOLDER_VIEW', 'html' );
defined( 'TI_FOLDER_CONTROLLER' )   or define( 'TI_FOLDER_CONTROLLER', 'www' );
defined( 'TI_EXT_INC' )             or define( 'TI_EXT_INC', '.php' );
defined( 'TI_EXT_VIEW' )            or define( 'TI_EXT_VIEW', '.html' );
defined( 'TI_EXT_CONTROLLER' )      or define( 'TI_EXT_CONTROLLER', '.php' );
define(  'TI_EXT_CONTROLLER_N',     strlen( TI_EXT_CONTROLLER ) * -1 );

defined( 'TI_AUTOLOAD_FILE' )       or define( 'TI_AUTOLOAD_FILE', '__application.php' );
defined( 'TI_AUTORENDER' )          or define( 'TI_AUTORENDER', TRUE );
defined( 'TI_RULES_CACHE' )         or define( 'TI_RULES_CACHE', FALSE );
defined( 'TI_FOLDER_CACHE' )        or define( 'TI_FOLDER_CACHE', 'cache' );

// Fix server vars for $_SERVER['REQUEST_URI'].
_ti_fix_server_vars();

// Set debugging mode.
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

// Determine if the request is ajax or not.
define( 'TI_IS_AJAX', ( strtolower( ifsetor( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest') ) );

// Start sesion.
defined( 'TI_DISABLE_SESSION' ) or define( 'TI_DISABLE_SESSION', FALSE );
if ( !TI_DISABLE_SESSION ) {
  _ti_session_start();
}

// Set default timezone.
date_default_timezone_set( TI_TIMEZONE );

// Set main locale.
setlocale( LC_CTYPE, TI_LOCALE );

// Set ti error handler.
set_error_handler( 'ti_error_handler' );

// Unregister globals.
unset( $_REQUEST, $_ENV, $HTTP_RAW_POST_DATA, $GLOBALS, $http_response_header, $argc, $argv );

// If is not cli and the REMOTE_ADDR is empty, then something is wrong.
if ( !is_cli() && empty( $_SERVER['REMOTE_ADDR'] ) ) {
  error_log( 'ti-framework: Request from unknown user.' );
  die( 'Permission denied' );
}

// Exit if favicon request detected.
if ( '/favicon.ico' == $_SERVER['REQUEST_URI'] ) {
  header( 'Content-Type: image/vnd.microsoft.icon' );
  header( 'Content-Length: 0' );
  exit;
}

// Show documentation or continue with the app.
if ( defined('TI_DOCUMENTATION') && is_string( TI_DOCUMENTATION ) && $_SERVER['REQUEST_URI'] === '/' . TI_DOCUMENTATION ) {
  include TI_PATH_FRAMEWORK . '/documentation.php';
  exit;
}

// Register autoloader function.
spl_autoload_register( 'ti_autoloader' );

// Check for __application.php
if ( is_readable( TI_PATH_APP . '/' . TI_AUTOLOAD_FILE ) ) {
  include TI_PATH_APP . '/' . TI_AUTOLOAD_FILE;
}

// Let boot the app.
$Application = new Application( $_SERVER['REQUEST_URI'] );

// Fire the shutdown hook.
// @fire shutdown
do_hook( 'shutdown' );

/**
 * @} End of "defgroup framework bootstrap".
 */
