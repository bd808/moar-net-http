<?php
/**
 * @package Moar\Net\Http
 */

namespace Moar\Net\Http;

/**
 * @package Moar\Net\Http
 */
class UtilTest extends \PHPUnit_Framework_TestCase {

  public function test_params_are_added_before_fragment () {
    $url = Util::addQueryData('http://example.com:1234/path#fragment',
        array('a' => 'b'));
    $this->assertEquals('http://example.com:1234/path?a=b#fragment', $url);

    $url = Util::addQueryData('http://example.com:1234/path?key=value#fragment',
        array('a' => 'b'));
    $this->assertEquals('http://example.com:1234/path?key=value&a=b#fragment',
        $url);

    $url = Util::addQueryData('http://example.com:1234/path?k=long%20value#f',
        array('a' => 'b'));
    $this->assertEquals('http://example.com:1234/path?k=long%20value&a=b#f',
        $url);
  }

} //end UtilTest
