<?php
/**
 * @package Moar\Net\Http
 */

namespace Moar\Net\Http;

/**
 * Base exception for HTTP request errors.
 *
 * @package Moar\Net\Http
 */
class Exception extends \RuntimeException {

  /**
   * Request that triggered this exception.
   * @var Request
   */
  protected $request;

  /**
   * Constructor.
   * @param string $msg Error message
   * @param int $code Error code
   * @param Request $req Request that triggered this exception
   */
  public function __construct ($msg, $code, $req = null) {
    parent::__construct($msg, $code);
    $this->request = $req;
  }

  /**
   * Get the request that triggered this exception.
   * @return Request Request
   */
  public function getRequest () {
    return $this->request;
  }

  /**
   * Set the request that triggered this exception.
   * @param Request $req Request
   * @return Exception Self, for message chaining
   */
  public function setRequest ($req) {
    $this->request = $req;
    return $this;
  }

} //end Exception
