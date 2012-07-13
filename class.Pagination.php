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

  private $config = array();

  function __construct($config = array()) {

    $this->config = array_model(array(
        'current_page' => 'INT|1',
        'total_entries' => 'INT|100',
        'per_page' => 'INT|20',
        'base_url' => 'STRING',
        'size' => 'INT|10',
        'html_cell_normal' => 'STRING|<a href="%s">%s</a>',
        'html_cell_active' => 'STRING|<a href="%s" class="active">%s</a>',
        'html_cell_first' => 'STRING|<a href="%s">&#8592;</a>',
        'html_cell_last' => 'STRING|<a href="%s">&#8594;</a>',
        'html_wrapper' => 'STRING|<div class="pagination">%s</div>'
            ), $config);
  }

  public function __set($key, $val = FALSE) {
    $key = CAST_TO_STRING($key);

    if (isset($this->config[$key]))
      return $this->config[$key] = $val;
    return FALSE;
  }

  public function __get($key) {
    $key = CAST_TO_STRING($key);

    if (isset($this->config[$key]))
      return $this->config[$key];
    return FALSE;
  }

  public function generate() {
    $config_array = & $this->config;

    if ($config_array['per_page'] >= $config_array['total_entries'])
      return FALSE;

    $html = '';

    $config_array['per_page'] = CAST_TO_INT($config_array['per_page'], 1);

    $page_num_last = ceil($config_array['total_entries'] / $config_array['per_page']);

    if ($config_array['current_page'] > $page_num_last)
      $config_array['current_page'] = $page_num_last;
    elseif ($config_array['current_page'] < 1)
      $config_array['current_page'] = 1;

    $page_num_prev = $config_array['current_page'] > 1 ? $config_array['current_page'] - 1 : 1;
    $page_num_next = $config_array['current_page'] < $page_num_last ? $config_array['current_page'] + 1 : $page_num_last;

    if ($config_array['size']) {
      $half_size = floor($config_array['size'] / 2);

      $even = $config_array['size'] % 2 ? 1 : 0;

      $for_loops = $config_array['current_page'] + $half_size + $even;
      $i = $config_array['current_page'] - $half_size + 1;

      if ($config_array['current_page'] - $half_size < 1) {
        $for_loops = $config_array['size'];
        $i = 1;
      }

      if ($for_loops > $page_num_last) {
        $for_loops = $page_num_last;
        $i = $page_num_last - $config_array['size'] + 1;
      }

      if ($i < 1)
        $i = 1;
    }
    else {
      $for_loops = $page_num_last;
      $i = 1;
    }

    if ($config_array['current_page'] > 1) {
      if ($config_array['html_cell_first'])
        $html .= sprintf($config_array['html_cell_first'], site_url(sprintf($config_array['base_url'], 1)));
    }

    for ($s = 1; $i <= $for_loops; $i++, $s++) {
      $uri = site_url(sprintf($config_array['base_url'], $i));

      if ($config_array['current_page'] == $i)
        $html .= sprintf($config_array['html_cell_active'], $uri, $i);
      else
        $html .= sprintf($config_array['html_cell_normal'], $uri, $i);
    }

    if ($page_num_last > $config_array['current_page']) {
      if ($config_array['html_cell_last'])
        $html .= sprintf($config_array['html_cell_last'], site_url(sprintf($config_array['base_url'], $page_num_last)));
    }

    if (!$html)
      return '';

    if ($config_array['html_wrapper'])
      $html = sprintf($config_array['html_wrapper'], $html);

    return $html;
  }

}
