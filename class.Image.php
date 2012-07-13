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

  private $font = '';
  private $im = NULL;
  private $quality = 85;

  function __construct() {
    $this->im = NULL;
  }

  function __destruct() {
    unset($this->im);
  }

  function set_quality($quality) {
    $this->quality = CAST_TO_INT($quality);
  }

  function set_font($file = '') {
    if (is_readable($file))
      $this->font = realpath($file);
    else
      $this->font = '';
  }

  function load_from_file($filename) {
    if (!is_readable($filename))
      return FALSE;

    if (!($image_info = getimagesize($filename)))
      return FALSE;

    switch ($image_info['mime']) {
      case 'image/gif':
        if (function_exists('imagecreatefromgif'))
          if ($this->im = imagecreatefromgif($filename))
            return TRUE;
        break;

      case 'image/jpeg':
        if (function_exists('imagecreatefromjpeg'))
          if ($this->im = imagecreatefromjpeg($filename))
            return TRUE;
        break;

      case 'image/png':
        if (function_exists('imagecreatefrompng'))
          if ($this->im = imagecreatefrompng($filename))
            return TRUE;
        break;

      case 'image/wbmp':
        if (function_exists('imagecreatefromwbmp'))
          if ($this->im = imagecreatefromwbmp($filename))
            return TRUE;
        break;


      case 'image/xbm':
        if (function_exists('imagecreatefromxbm'))
          if ($this->im = imagecreatefromxbm($filename))
            return TRUE;
        break;

      case 'image/xpm':
        if (function_exists('imagecreatefromxpm'))
          if ($this->im = imagecreatefromxpm($filename))
            return TRUE;
        break;
    }

    $this->im = NULL;
    return FALSE;
  }

  function load_from_string($string) {

    if ($string && is_string($string) && ($i = imagecreatefromstring($string))) {
      $this->im = $i;
      return TRUE;
    }
    return FALSE;
  }

  function thumbnail($filename) {
    $thumbnail = FALSE;
    if (function_exists('exif_thumbnail'))
      $thumbnail = exif_thumbnail($filename, $width, $height, $type);

    if ($thumbnail === FALSE) {
      $tmp = $this->im;
      if (!$this->load_from_file($filename))
        return FALSE;
      $this->resize_to_size('256');
      document_clean();
      header('Content-type: image/jpeg');
      imagejpeg($this->im, NULL, 99);
      $this->im = $tmp;
      unset($tmp);
    }
    else {
      header('Content-type: image/' . $type);
      echo $thumbnail;
    }
  }

  function save_to_file($filename, $quality = FALSE, $permissions = NULL) {
    if (imagejpeg($this->im, $filename, $quality ? $quality : $this->quality)) {
      if ($permissions !== NULL)
        chmod($filename, $permissions);
      return TRUE;
    }
    return FALSE;
  }

  function output($quality = FALSE, $type = 'jpeg', $set_header = TRUE) {
    document_clean();
    header('Content-type: image/jpeg');
    switch ($type) {
      case 'gif' :
        if (imagegif($this->im, NULL))
          return TRUE;
      case 'png' :
        if (imagepng($this->im, NULL, 8))
          return TRUE;
      default:
        if (imagejpeg($this->im, NULL, $quality ? $quality : $this->quality))
          return TRUE;
    }
    return FALSE;
  }

  function output_get($quality = FALSE, $type = 'jpeg') {
    ob_start();
    $this->output($quality, $type, FALSE);
    return ob_get_clean();
  }

  function interlance() {
    imageinterlace($this->im, TRUE);
  }

  function resize_to_height($height, $preserve_smaller = FALSE) {
    $ratio = $height / imagesy($this->im);
    $width = imagesx($this->im) * $ratio;
    return $this->resize($width, $height, $preserve_smaller);
  }

  function resize_to_width($width, $preserve_smaller = FALSE) {
    $ratio = $width / imagesx($this->im);
    $height = imagesy($this->im) * $ratio;
    return $this->resize($width, $height, $preserve_smaller);
  }

  function resize_to_size($size, $preserve_smaller = TRUE) {
    $width_orig = imagesx($this->im);
    $height_orig = imagesy($this->im);

    if ($width_orig > $height_orig) {
      $ratio = $size / $width_orig;
      $height = $height_orig * $ratio;
      $width = $size;
    } else {
      $ratio = $size / $height_orig;
      $width = $width_orig * $ratio;
      $height = $size;
    }

    return $this->resize($width, $height, $preserve_smaller);
  }

  function resize($width, $height, $preserve_smaller = FALSE) {
    if ($preserve_smaller) {
      $width_orig = imagesx($this->im);
      $height_orig = imagesy($this->im);
      if ($width_orig < $width && $height_orig < $height)
        return TRUE;
    }
    $image_new = imagecreatetruecolor($width, $height);
    imagecopyresampled($image_new, $this->im, 0, 0, 0, 0, $width, $height, imagesx($this->im), imagesy($this->im));
    $this->im = $image_new;
    unset($image_new);
    return TRUE;
  }

  function resize_cropped($width, $height, $preserve_smaller = FALSE) {
    $width_orig = imagesx($this->im);
    $height_orig = imagesy($this->im);
    $ratio_orig = $width_orig / $height_orig;

    if ($preserve_smaller) {
      $width_orig = imagesx($this->im);
      $height_orig = imagesy($this->im);
      if ($width_orig < $width && $height_orig < $height)
        return $image;
    }

    if ($width / $height > $ratio_orig) {
      $new_height = $width / $ratio_orig;
      $new_width = $width;
    } else {
      $new_width = $height * $ratio_orig;
      $new_height = $height;
    }
    $x_mid = $new_width / 2;
    $y_mid = $new_height / 2;

    $image_proccess = imagecreatetruecolor(round($new_width), round($new_height));
    imagecopyresampled($image_proccess, $this->im, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig);

    $image_new = imagecreatetruecolor($width, $height);
    imagecopyresampled($image_new, $image_proccess, 0, 0, ($x_mid - ($width / 2)), ($y_mid - ($height / 2)), $width, $height, $width, $height);
    imagedestroy($image_proccess);

    $this->im = $image_new;
    imagedestroy($image_new);
    return TRUE;
  }

  function scale($scale = '100', $preserve_smaller = FALSE) {
    $width = imagesx($this->im) * $scale / 100;
    $height = imagesy($this->im) * $scale / 100;
    return resize($width, $height, $preserve_smaller);
  }

  function rotate($rotate = 90) {
    $this->im = imagerotate($this->im, CAST_TO_INT($rotate), 0);
  }

  function wattermark($image, $text = 'Wattermark', $fontsize = 18, $file = FALSE, $position = 'RIGHT BOTTOM') {
    if ($file && is_readable($file)) {
      $wattermark = imagecreatefrompng($file);
      $mark_w = imagesx($wattermark);
      $mark_h = imagesy($wattermark);

      if (strpos($position, 'LEFT') !== FALSE)
        $mark_x = 10;

      if (strpos($position, 'RIGHT') !== FALSE)
        $mark_x = imagesx($image) - 10 - $mark_w;

      else
        $mark_x = ceil(imagesx($image) - $mark_w);

      if (strpos($position, 'TOP') !== FALSE)
        $mark_y = 10;
      if (strpos($position, 'BOTTOM') !== FALSE)
        $mark_y = imagesy($image) - 10 - $mark_h;
      else
        $mark_y = ceil(imagesy($image) - $mark_h);

      imagecopy($this->im, $wattermark, $mark_x, $mark_y, 0, 0, $mark_w, $mark_h);
      return TRUE;
    }
    if ($text && $this->font) {
      $black = imagecolorallocate($image, 0, 0, 0);
      $font = $this->font;

      list($pos[0], $post[1]) = explode(' ', $position);

      if (strpos($position, 'LEFT') !== FALSE)
        $mark_x = 10;
      elseif (strpos($position, 'RIGHT') !== FALSE)
        $mark_x = imagesx($image) - 10 - strlen($text) * $fontsize;
      else
        $mark_x = ceil(imagesx($image) - strlen($text) * $fontsize);

      if (strpos($position, 'TOP') !== FALSE)
        $mark_y = 10;
      if (strpos($position, 'BOTTOM') !== FALSE)
        $mark_y = imagesy($image) - 10 - $fontsize;
      else
        $mark_y = ceil(imagesy($image) - $fontsize);

      imagettftext($this->im, $fontsize, 0, $mark_x, $mark_y, $black, $font, $text);

      return TRUE;
    }
    return FALSE;
  }

  function to_ascii() {
    $text = '';
    $width = imagesx($this->im);
    $height = imagesy($this->im);

    for ($h = 1; $h < $height; $h++) {
      for ($w = 1; $w <= $width; $w++) {
        $rgb = imagecolorat($this->im, $w, $h);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        $hex = '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

        if ($w == $width)
          $text .= '<br>';
        else
          $text .= '<span style="color:' . $hex . ';">#</span>';
      }
    }
    return $text;
  }

}
