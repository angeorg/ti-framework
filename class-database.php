<?php

/**
 * library.Database.php - Database PDO extended driver db()
 *
 * Copyright (c) 2012, e01 <dimitrov.adrian@gmail.com>
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

if (! defined('TI_PATH_FRAMEWORK'))
  return exit;

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
