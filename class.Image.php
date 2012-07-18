<?php

/**
 * library.Image.php - Image operating class
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

class Image {

  private $im = NULL;
  public $quality = 85;

  /**
   * Load image for manipulation from existing file on the filesystem.
   *
   * @param string $filename
   *
   * @return boolean
   */
  function load_from_file($filename = '') {

    if ( !$file || !is_readable($filename) ) {
      return FALSE;
    }

    if ( !($image_info = getimagesize( $filename )) ) {
      return FALSE;
    }

    switch ($image_info['mime']) {
      case 'image/gif':
        if ( function_exists( 'imagecreatefromgif' ) && ( $this->im = imagecreatefromgif( $filename ) ) )  {
          return TRUE;
        }
        break;

      case 'image/jpeg':
        if ( function_exists( 'imagecreatefromjpeg' ) && ( $this->im = imagecreatefromjpeg( $filename ) ) )  {
          return TRUE;
        }
        break;

      case 'image/png':
        if ( function_exists( 'imagecreatefrompng' ) && ( $this->im = imagecreatefrompng( $filename ) ) )  {
          return TRUE;
        }
        break;

      case 'image/wbmp':
        if ( function_exists( 'imagecreatefromwbmp' ) && ( $this->im = imagecreatefromwbmp( $filename ) ) )  {
          return TRUE;
        }
        break;

      case 'image/xbm':
        if ( function_exists( 'imagecreatefromxbm' ) && ( $this->im = imagecreatefromxbm( $filename ) ) )  {
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

    if ($preserve_smaller) {
      $width_orig = imagesx( $this->im );
      $height_orig = imagesy( $this->im );
      if ( $width_orig < $width && $height_orig < $height ) {
        $this->im = $image;
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

    $black = imagecolorallocate( $image, 0, 0, 0 );

    list( $pos[0], $post[1] ) = explode( ' ', $position );

    if ( strpos( $position, 'LEFT' ) !== FALSE ) {
      $mark_x = 10;
    }
    else {
      $mark_x = imagesx( $image ) - 10 - strlen( $text ) * $fontsize;
    }

    if ( strpos($position, 'TOP') !== FALSE) {
      $mark_y = 10;
    }
    else {
      $mark_y = imagesy($image) - 10 - $fontsize;
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
  function wattermark_image($image, $size = 0, $position = 'RIGHT BOTTOM') {

    $wim = new Image;
    if (!$wim->load_from_file($filename)) {
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
