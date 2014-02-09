<?php
/**
 * Simple class to convert between rgb values and ansi xterm-256 codes.
 *
 * @author Michael Payne <benighted@gmail.com>
 * @version 1.0.0
 */
class AnsiToRgb {
  /**
   * Separate r, g, b components from rgb value.
   * @param integer $rgb
   * @return array
   */
  private static function _explodeRgb ($rgb) {
    return array(
      (($rgb >> 16) & 0xff),
      (($rgb >> 8) & 0xff),
      (($rgb) & 0xff)
    );
  }

  /**
   * Combine r, g, b components into rgb value.
   * @param integer $r
   * @param integer $g
   * @param integer $b
   * @return integer
   */
  private static function _implodeRgb ($r, $g, $b) {
    return ($r << 16) + ($g << 8) + $b;
  }

  /**
   * Convert an xterm-256 code to an rgb value
   * @param integer $ansi
   * @return integer
   */
  private static function _convertToRgb ($ansi) {
    if ($ansi < 16) { // system colors
      if ($ansi == 7) return self::_implodeRgb(0xc0, 0xc0, 0xc0);
      if ($ansi == 8) return self::_implodeRgb(0x80, 0x80, 0x80);

      $intensity = $ansi < 8 ? 0x80 : 0xff;
      $ansi %= 8; // makes the matching simpler
      return self::_implodeRgb(
        (in_array($ansi, array(1,3,5,7)) ? $intensity : 0x00),
        (in_array($ansi, array(2,3,6,7)) ? $intensity : 0x00),
        (in_array($ansi, array(4,5,6,7)) ? $intensity : 0x00)
      );
    }

    if ($ansi > 231) { // greyscale
      return self::_implodeRgb(
        0x08 + (($ansi - 232) * 10),
        0x08 + (($ansi - 232) * 10),
        0x08 + (($ansi - 232) * 10)
      );
    }

    $intensities = array(0x00, 0x5f, 0x87, 0xaf, 0xd7, 0xff);
    $ansi -= 16; // makes the math simpler
    $r = $intensities[$ansi / 36];
    $g = $intensities[($ansi % 36) / 6];
    $b = $intensities[($ansi % 36 % 6)];
  
    return self::_implodeRgb($r, $g, $b);
  }

  /**
   * Convert an xterm-256 code to an rgb hex code
   * @param integer $ansi
   * @return string
   */
  private static function _convertToRgbHex ($ansi) {
    return str_pad(
      dechex(self::_convertToRgb($ansi)),
      6, '0', STR_PAD_LEFT
    );
  }

  /**
   * Convert separated red, green, and blue components to xterm-256 code. 
   * @param integer $r
   * @param integer $g
   * @param integer $b
   * @return integer
   */
  private static function _convertToAnsi ($r, $g, $b) {
    $grads = array(0x00, 0x5f, 0x87, 0xaf, 0xd7, 0xff);
    $comps = array($r, $g, $b);

    for ($c = 0; $c < 3; $c++) {
      for ($i = 0; $i < 5; $i++) {
        if ($grads[$i] > $comps[$c]) continue;
        if ($grads[$i + 1] >= $comps[$c]) {
          $comps[$c] = $comps[$c] - $grads[$i] <
              $grads[$i + 1] - $comps[$c] ? $i : $i + 1;
          break;
        }
      }
    }

    return 16 + ($comps[0] * 36) + ($comps[1] * 6) + $comps[2];
  }

  /**
   * Convert an xterm-256 code to an rgb value
   * @param integer $ansi
   * @return integer
   */
  public static function toRgb ($ansi) {
    return self::_convertToRgb(intval($ansi));
  }

  /**
   * Convert an xterm-256 code to an rgb hex code
   * @param integer $ansi
   * @return string
   */
  public static function toRgbHex ($ansi) {
    return self::_convertToRgbHex(intval($ansi));
  }

  /**
   * Convert a rgb value or separated red, green, and blue components to xterm-256 code. 
   * @param mixed $r
   * @param mixed $g
   * @param mixed $b
   * @return integer
   */
  public static function toAnsi ($r, $g = null, $b = null) {
    if (!is_null($r) && is_null($g) && is_null($b)) { // rgb
      // check for valid hex string or try create valid hex
      if (!is_numeric($r) && is_string($r)) { // not a hex
        if ($r[0] === "#") $r = substr($r,1); // strips #
        switch  (strlen($r)) { // converts to hex string
          case 3:
            $r = $r[0] . $r[0] . $r[1] . $r[1] . $r[2] . $r[2];
          case 6:
            $r = '0x' . $r;
      	}
      }

      // convert to decimal within range
      $r = min(intval($r, 0), 0xffffff);

      // separate r, g, b components to convert
      list($r, $g, $b) = self::_explodeRgb($r);
      return self::_convertToAnsi($r, $g, $b);
    } else { // r, g, b
      return self::_convertToAnsi(intval($r, 0), intval($g, 0), intval($b, 0));
    }
  }
}

class RgbToAnsi extends AnsiToRgb {
  /**just an alias for AnsiToRgb */
}

?>

