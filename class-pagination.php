<?php

/**
 * library.Pagination.php - Pagination class for work with pages
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
  return FALSE;

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
