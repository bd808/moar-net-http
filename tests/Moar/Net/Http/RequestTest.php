<?php
/**
 * @package Moar\Net\Http
 */

namespace Moar\Net\Http;

/**
 * @package Moar\Net\Http
 */
class RequestTest extends \PHPUnit_Framework_TestCase {

  public function test_basic_get () {
    $r = new Request('https://httpbin.org/');
    $r->submit();

    $code = $r->getResponseHttpCode();
    $body = $r->getResponseBody();
    $head = $r->getResponseHeaders();

    $this->assertEquals(200, $code);
    $this->assertNotNull($body);
    $this->assertNotEquals('', $body);
  } //end test_basic_get

  public function test_parallel () {
    $reqs = array(
        new Request('https://httpbin.org/'),
        new Request('http://httpbin.org/ip'),
        new Request('http://httpbin.org/user-agent'),
        new Request('http://httpbin.org/redirect/3'),
        new Request('http://httpbin.org/post', 'POST',
            array('foo' => 'bar')),
      );
    Request::parallelSubmit($reqs);

    foreach ($reqs as $req) {
      $req->validateResponse(false);
      $body = $req->getResponseBody();
      $this->assertNotNull($body, "{$req->getUrl()} body is not null");
      $this->assertNotEquals('', $body, "{$req->getUrl()} body is not empty");
    }
  } //end test_parallel

  /**
   * @expectedException Moar\Net\Http\Exception
   * @expectedExceptionMessage 404
   */
  public function test_get_404 () {
    $r = new Request('http://httpbin.org/status/404');
    $r->submit();
  } //end test_get_404

  public function test_postcontent () {
    $url = 'https://httpbin.org/post';
    $content = '<?xml version="1.0" encoding=\'UTF-8\'?><payload><email>test@example.com</email></payload>';
    $contentType = 'text/xml';

    $r = Request::postContent($url, $content, $contentType)
        ->getResponseBody();
    $this->assertNotNull($r);
    $this->assertNotEquals('', $r);
  } //end test_postcontent

} //end RequestTest
