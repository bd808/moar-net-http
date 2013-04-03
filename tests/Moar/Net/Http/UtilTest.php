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
    $url = Util::addQueryData('http://example.com/path#fragment',
        array('a' => 'b'));
    $this->assertEquals('http://example.com/path?a=b#fragment', $url);

    $url = Util::addQueryData('http://example.com/path?key=value#fragment',
        array('a' => 'b'));
    $this->assertEquals('http://example.com/path?key=value&a=b#fragment', $url);

    $url = Util::addQueryData('http://example.com/path?k=long%20value#f',
        array('a' => 'b'));
    $this->assertEquals('http://example.com/path?k=long%20value&a=b#f', $url);
  }

} //end UtilTest
