<?php
/**
 * @package Moar\Net\Http
 */

namespace Moar\Net\Http;

/**
 * HTTP utilities.
 *
 * @package Moar\Net\Http
 */
class Util {

  /**
   * Make a URL-encoded string from a key=>value array
   * @param array $parms Parameter array
   * @return string URL-encoded message body
   */
  public static function urlEncode ($parms) {
    $payload = array();

    foreach ($parms as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $item) {
          $payload[] = urlencode($key) . '=' . urlencode($item);
        }
      } else {
        $payload[] = urlencode($key) . '=' . urlencode($value);
      }
    }

    return implode('&', $payload);
  } //end urlEncode


  /**
   * Merge two arrays of curl options together into a new array.
   *
   * Values in the additional array will override values in the base array
   * except in the case of CURLOPT_HTTPHEADER where values from the additional
   * array will be merged with values from the base array if any exist.
   *
   * The additional array can be keyed with either ints which will be assumed
   * to be CURLOPT_* values or strings such as 'CURLOPT_USERPWD' which will be
   * turned into ints via constant(). This makes setting up options in a DI or
   * other non-php scripted senario easier to implement.
   *
   * @param array $base Base options
   * @param array $additional Additional options
   * @return array New array of base options with additional options overlayed
   * @throws \InvalidArgumentException If invalid string key is used
   */
  public static function mergeCurlOptions ($base, $additional) {
    if (null === $additional || empty($additional)) {
      return $base;
    }

    foreach ($additional as $key => $val) {
      if (!is_int($key)) {
        // treat non-numeric keys as CURLOPT_* constants to be resolved
        try {
          $curlkey = constant($key);
        } catch (Exception $ignored) {
          // no-op
        }
        if (null === $curlkey) {
          throw new \InvalidArgumentException("Invalid curl option [{$key}].");
        }
        $key = $curlkey;
      }
      if (CURLOPT_HTTPHEADER == $key &&
          array_key_exists(CURLOPT_HTTPHEADER, $base)) {
        // additional headers are cumlative
        if (is_array($val)) {
          // concatinate header collection
          foreach ($val as $header) {
            $base[CURLOPT_HTTPHEADER][] = $header;
          }
        } else {
          // add single header
          $base[CURLOPT_HTTPHEADER][] = $val;
        }

      } else {
        $base[$key] = $val;
      }
    } //end foreach

    return $base;
  } //end mergeCurlOptions


  /**
   * Ensure that constants are available for useful cURL error codes.
   *
   * @return void
   * @see http://curl.haxx.se/libcurl/c/libcurl-errors.html
   */
  public static function ensureCurlErrorConstants () {
    $defs = array(
      'CURLE_COULDNT_CONNECT' => 7,
      'CURLE_COULDNT_RESOLVE_HOST' => 6,
      'CURLE_HTTP_RETURNED_ERROR' => 22,
      'CURLE_OPERATION_TIMEDOUT' => 28,
      'CURLE_PEER_FAILED_VERIFICATION' => 51,
      'CURLE_SSL_CACERT' => 60,
      'CURLE_SSL_CACERT_BADFILE' => 77,
      'CURLE_SSL_CERTPROBLEM' => 58,
      'CURLE_SSL_CIPHER' => 59,
      'CURLE_SSL_CONNECT_ERROR' => 35,
      'CURLE_SSL_CRL_BADFILE' => 82,
      'CURLE_SSL_ENGINE_INITFAILED' => 66,
      'CURLE_SSL_ENGINE_NOTFOUND' => 53,
      'CURLE_SSL_ENGINE_SETFAILED' => 54,
      'CURLE_SSL_ISSUER_ERROR' => 83,
      'CURLE_SSL_SHUTDOWN_FAILED' => 80,
      'CURLE_UNSUPPORTED_PROTOCOL' => 1,
      'CURLE_URL_MALFORMAT' => 3,
      'CURLE_USE_SSL_FAILED' => 64,
    );

    foreach ($defs as $constName => $errCode) {
      if (!defined($constName)) {
        define($constName, $errCode);
      }
    }
  } //end ensureCurlConstants

  /**
   * Append a query string to the given URL.
   *
   * @param string $url URL to append to
   * @param string|array $parms Parameters to add as query string to url
   * @return string Composed URL
   */
  public static function addQueryData ($url, $parms) {
    if (is_array($parms)) {
      // construct GET data
      $payload = self::urlEncode($parms);

    } else if (null !== $parms) {
      $payload = (string) $parms;
    }
    if (!empty($payload)) {
      $parts = parse_url($url);
      if (isset($parts['query'])) {
        $parts['query'] .= "&{$payload}";
      } else {
        $parts['query'] = $payload;
      }
      $url = self::assembleUrl($parts);
    }
    return $url;
  } //end addQueryData

  /**
   * Assemble a URL from a array of components as would be returned by
   * parse_url().
   *
   * @param array $parts URL parts
   * @return string URL
   */
  protected static function assembleUrl ($parts) {
    $url = '';
    if (isset($parts['scheme'])) {
      $url .= "{$parts['scheme']}:";
    }
    $url .= '//';
    if (isset($parts['user'])) {
      $url .= $parts['user'];
      if (isset($parts['password'])) {
        $url .= ":{$parts['password']}";
      }
      $url .= '@';
    }
    if (isset($parts['host'])) {
      $url .= $parts['host'];
    }
    if (isset($parts['path'])) {
      $url .= $parts['path'];
    }
    if (isset($parts['query'])) {
      $url .= "?{$parts['query']}";
    }
    if (isset($parts['fragment'])) {
      $url .= "#{$parts['fragment']}";
    }
    return $url;
  } //end assembleUrl

  const COOKIE_NAME = 'cookie-name';
  const COOKIE_VALUE = 'cookie-value';

  /**
   * Parse a "Set-Cookie" header to get the component cookie data.
   *
   * @param string $hdr Header data
   * @return array Collection of cookies specified by the header
   */
  public static function parseCookieHeader ($hdr) {
    $cookies = array();
    $i = 0;
    $from = 0;
    $len = mb_strlen($hdr, 'latin1');
    $quoted = false;

    while ($i < $len) {
      if ('"' === $hdr[$i] && '\\' !== $elm[$i - 1]) {
        $quoted = !$quoted;
      }
      $elm = null;
      if (!$quoted && ',' === $hdr[$i]) {
        $chunk = mb_substr($hdr, $from, $i - $from, 'latin1');
        $elm = self::parseCookieElement($chunk);
        $from = $i + 1;

      } else if ($i === $len - 1) {
        $chunk = mb_substr($hdr, $from, $len - $from, 'latin1');
        $elm = self::parseCookieElement($chunk);
      }

      if (null !== $elm && isset($elm[self::COOKIE_NAME])) {
        $cookies[] = $elm;
      }
      $i += 1;
    } //end while
    return $cookies;
  } //end parseCookieHeader

  /**
   * Parse a single cookie setting.
   * @param string $elm Cookie element
   * @return array Associative array of cookie data
   */
  protected static function parseCookieElement ($elm) {
    $cookie = array();
    // eat whitespace outside of quotes
    // each part ends with a semi-colon outside of quotes
    $i = 0;
    $from = 0;
    $len = mb_strlen($elm, 'latin1');
    $quoted = false;
    while ($i < $len) {
      if ('"' === $elm[$i] && '\\' !== $elm[$i - 1]) {
        $quoted = !$quoted;
      }
      $chunk = null;
      if (!$quoted && ';' === $elm[$i]) {
        $chunk = mb_substr($elm, $from, $i - $from, 'latin1');
        $from = $i + 1;
      } else if ($i === $len - 1) {
        $chunk = mb_substr($elm, $from, $len - $from, 'latin1');
      }
      if (null !== $chunk) {
        $parts = explode('=', $chunk, 2);
        if (count($parts) == 1) {
          // we found a flag of some sort
          $name = trim($parts[0]);
          $cookie[$name] = true;

        } else {
          $name = trim($parts[0]);
          $val = trim($parts[1]);
          // remove quotes
          $val = trim($val, '"');
          if (empty($cookie)) {
            // first key=value pair is the cookie name and value
            $cookie[self::COOKIE_NAME] = $name;
            $cookie[self::COOKIE_VALUE] = $val;

          } else {
            $cookie[$name] = $val;
          }
        } //end if/else
      } //end if
      $i += 1;
    } //end while

    return $cookie;
  } //end parseCookieElement

  /**
   * Construction disallowed.
   */
  private function __construct () {
    // no-op
  }

} //end Util
