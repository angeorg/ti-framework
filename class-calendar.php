<?php

/**
 * library.Calendar.php - Simple calendar/events class
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
