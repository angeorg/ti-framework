<?php

/**
 * library.Messagebug.php - Messagebus class for work in frontend
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
