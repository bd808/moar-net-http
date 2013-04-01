<?php

namespace Moar\Net\Http;

// ensure that curl error constants are available
Util::ensureCurlErrorConstants();


/**
 * HTTP request handler that attempts to make performing HTTP requests via
 * cURL easy for the developer.
 *
 * Static convenience methods are provided to performing typical requests such
 * as GET and POST. Several variations of POST are available for
 * "application/x-www-form-urlencoded", "multipart/form-data" and raw body
 * encodings.
 *
 * More complicated requests can be configured via direct use of the class.
 * Helper methods are provided for common operations such as authenticating
 * with a a username/password pair or an x509 certificate.
 *
 * Requests are sent by calling the submit() method directly or by
 * passing an array of prepared requests to the static parallelSubmit() method.
 * Either method of executing a request will result in the provided
 * Request being updated to contain response codes, headers and body data
 * returned by the server processing the URL.
 */
class Request {

  /**
   * Default user-agent string.
   * @var string
   */
  const DEFAULT_USERAGENT = 'Mozilla/5.0 (compatible; Moar-Net-Http-Request)';

  /**
   * Default maximum redirects.
   * @var int
   */
  const DEFAULT_MAXREDIRS = 10;

  /**
   * HTTP GET.
   * @var string
   */
  const METHOD_GET = 'GET';

  /**
   * HTTP POST.
   * @var string
   */
  const METHOD_POST = 'POST';

  /**
   * Default curlopts.
   * @var array
   */
  protected static $defaultCurlOpts = array(
      CURLOPT_SSL_VERIFYPEER    => false,
      CURLOPT_SSL_VERIFYHOST    => false,
      CURLINFO_SSL_VERIFYRESULT => false,
      CURLOPT_FOLLOWLOCATION    => true,
      CURLOPT_MAXREDIRS         => self::DEFAULT_MAXREDIRS,
      CURLOPT_HTTP_VERSION      => CURL_HTTP_VERSION_1_1,
      CURLOPT_ENCODING          => '',
      CURLOPT_CONNECTTIMEOUT    => 3, // 3 seconds
      CURLOPT_TIMEOUT           => 5, // 5 seconds
    );


  /**
   * URL to request.
   * @var string
   */
  protected $url;

  /**
   * HTTP request method.
   * @var string
   */
  protected $method;

  /**
   * HTTP request headers.
   * @var array
   */
  protected $headers = array();

  /**
   * POST body.
   * @var string
   */
  protected $postBody;

  /**
   * User-Agent header value.
   * @var string
   */
  protected $userAgent = self::DEFAULT_USERAGENT;

  /**
   * Curl options.
   * @var array
   */
  protected $curlOptions = array();

  /**
   * Default behavior for non-200 status responses.
   * @var bool
   */
  protected $defaultFailIfNot200 = true;

  /**
   * Response code.
   * @var int
   */
  protected $responseHttpCode;

  /**
   * Response headers.
   * @var array
   */
  protected $responseHeaders;

  /**
   * Response body.
   * @var string
   */
  protected $responseBody;

  /**
   * Curl response information.
   * @var array
   */
  protected $responseCurlInfo;

  /**
   * Curl error status.
   * @var int
   */
  protected $responseCurlErr;

  /**
   * Curl error message.
   * @var string
   */
  protected $responseCurlErrMessage;


  /**
   * Constructor.
   *
   * @param string $url URL to request
   * @param string $method HTTP request verb
   * @param mixed  $data Data to send with request. Either a URL-encoded
   *    string or an array of key=>value pairs.
   * @param array  $headers Custom headers to set on request
   * @param array  $opts Curl configuration options
   */
  public function __construct (
      $url = null, $method = self::METHOD_GET, $data = null, $headers = null,
      $opts = null) {
    $this->setUrl($url);
    $this->setMethod($method);
    $this->setHeaders($headers);
    $this->setPostBody($data);
    $this->setCurlOptions($opts);
  } //end __construct


  /**
   * Set the url for this request.
   * @param string $url URL to request
   * @return Request Self, for message chaining
   */
  public function setUrl ($url) {
    $this->url = $url;
    return $this;
  }

  /**
   * Get the url for this request.
   * @return string URL of request
   */
  public function getUrl () {
    return $this->url;
  }


  /**
   * Set the method for this request.
   * @param string $method HTTP request verb
   * @return Request Self, for message chaining
   */
  public function setMethod ($method) {
    $this->method = $method;
    return $this;
  }

  /**
   * Get the method for this request.
   * @return string HTTP request verb
   */
  public function getMethod () {
    return $this->method;
  }


  /**
   * Set the headers for this request.
   * @param array $headers Custom headers to set on request
   * @return Request Self, for message chaining
   */
  public function setHeaders ($headers) {
    $this->headers = (array) $headers;
    return $this;
  }

  /**
   * Add a header for this request.
   * @param string $header Header to send with request
   * @return Request Self, for message chaining
   */
  public function addHeader ($header) {
    $this->headers[] = $header;
    return $this;
  }

  /**
   * Get the custom headers for this request.
   * @return array Collection of custom headers
   */
  public function getHeaders () {
    return $this->headers;
  }


  /**
   * Set the postBody for this request.
   * @param mixed $postBody Either literal payload for request or array of
   *    key=>value pairs to encode as "multipart/form-data" on submission.
   * @return Request Self, for message chaining
   */
  public function setPostBody ($postBody) {
    $this->postBody = $postBody;
    return $this;
  }

  /**
   * Get the postBody for this request.
   * @return string HTTP request verb
   */
  public function getPostBody () {
    return $this->postBody;
  }

  /**
   * Append a query string to the current URL.
   *
   * @param string|array $parms Parameters to add as query string to url
   * @return Request Self, for message chaining
   */
  public function addQueryData ($parms) {
    $this->setUrl(Util::addQueryData($this->getUrl(), $parms));
    return $this;
  }


  /**
   * Set cURL options for this request.
   * @param array $opts Curl options
   * @return Request Self, for message chaining
   */
  public function setCurlOptions ($opts) {
    $this->curlOptions = (array) $opts;
    return $this;
  }

  /**
   * Add a cURL option for this request.
   * @param mixed $key Curl option identifier
   * @param mixed $value Curl option value
   * @return Request Self, for message chaining
   */
  public function addCurlOption ($key, $value) {
    $this->curlOptions[$key] = $value;
    return $this;
  }

  /**
   * Get the cURL options for this request.
   * @return array Curl configuration options
   */
  public function getCurlOptions () {
    return $this->curlOptions;
  }


  /**
   * Set the value of the User-Agent header for this request.
   * @param string $userAgent The User-Agent
   * @return Request Self, for message chaining
   */
  public function setUserAgent ($userAgent) {
    $this->userAgent = $userAgent;
    return $this;
  }

  /**
   * Get the value of the User-Agent header for this request.
   * @return string The User-Agent
   */
  public function getUserAgent () {
    return $this->userAgent;
  }

  /**
   * Set the referring URL that this request is realted to.
   * @param string $ref URL to report to server in "Referer" header
   * @return Request Self, for message chaining
   */
  public function setReferrer ($ref) {
    $this->addCurlOption(CURLOPT_REFERER, $ref);
    return $this;
  }

  /**
   * Synonym for setReferrer().
   * @param string $ref URL to report to server in "Referer" header
   * @return Request Self, for message chaining
   * @see setReferrer()
   */
  public function setReferer ($ref) {
    return $this->setReferrer($ref);
  }

  /**
   * Set HTTP connect timeout (in milliseconds).
   * @param int $ms Connect timeout in millseconds
   * @return Request Self, for message chaining
   */
  public function setConnectTimeout ($ms) {
    if (defined('CURLOPT_CONNECTTIMEOUT_MS')) {
      $this->addCurlOption(CURLOPT_CONNECTTIMEOUT_MS, $ms);

    } else {
      // older versions of php/libcurl don't have the sub-second timeout
      // functionality. Convert to nearest whole seconds not less than 1.
      $this->addCurlOption(CURLOPT_CONNECTTIMEOUT, max(1, round($ms / 1000)));
    }
    return $this;
  } //end setConnectTimeout

  /**
   * Set HTTP timeout (in milliseconds).
   * @param int $ms Timeout in millseconds
   * @return Request Self, for message chaining
   */
  public function setTimeout ($ms) {
    if (defined('CURLOPT_TIMEOUT_MS')) {
      $this->addCurlOption(CURLOPT_TIMEOUT_MS, $ms);

    } else {
      // older versions of php/libcurl don't have the sub-second timeout
      // functionality. Convert to nearest whole seconds not less than 1.
      $this->addCurlOption(CURLOPT_TIMEOUT, max(1, round($ms / 1000)));
    }
    return $this;
  } //end setTimeout


  /**
   * Set credentials for authenticating to remote host.
   *
   * @param string $user Username
   * @param string $password Password
   * @param int    $type Auth type to attempt. See CURLOPT_HTTPAUTH section of
   *    curl_setopt page at php.net for options.
   * @return Request Self, for message chaining
   */
  public function setCredentials ($user, $password, $type = CURLAUTH_ANYSAFE) {
    $this->addCurlOption(CURLOPT_HTTPAUTH, $type);
    $this->addCurlOption(CURLOPT_USERPWD, "{$user}:{$password}");
    return $this;
  } //end setCredentials


  /**
   * Set x509 credentials for authenticating to remote host.
   *
   * @param string $cert Path to x509 certificate
   * @param string $key Patch to x509 private key
   * @param string $keypass Passphrase to decrypt private key
   * @param string $type Certificate encoding (PEM|DER|ENG)
   * @return Request Self, for message chaining
   */
  public function setX509Credentials ($cert, $key, $keypass, $type = 'PEM') {
    $this->addCurlOption(CURLOPT_SSLCERTTYPE, $type);
    $this->addCurlOption(CURLOPT_SSLCERT, $cert);
    $this->addCurlOption(CURLOPT_SSLKEY, $key);
    $this->addCurlOption(CURLOPT_SSLKEYPASSWD, $keypass);
    return $this;
  }


  /**
   * Read and store cookies in the provided file.
   *
   * @param string $file Path to cookie storage file
   * @return Request Self, for message chaining
   */
  public function setCookieJar ($file) {
    if (null !== $file) {
      $this->addCurlOption(CURLOPT_COOKIEFILE, $file); //read from file
      $this->addCurlOption(CURLOPT_COOKIEJAR, $file); //write to file
    }
    return $this;
  }

  /**
   * Set the default behavior when a request returns a non-200 status code.
   *
   * @param bool $flag True to throw an exception, false otherwise
   * @return Request Self, for message chaining
   * @see getResponseHttpCode()
   */
  public function failIfNot200 ($flag) {
    $this->defaultFailIfNot200 = (bool) $flag;
    return $this;
  }

  /**
   * Check to see if this request has been submitted yet.
   *
   * @return bool True if request has been submitted, false otherwise.
   */
  public function wasSubmitted () {
    return null !== $this->responseCurlErr;
  }

  /**
   * Throw a \RuntimeException if this request has not been submitted
   * yet.
   *
   * @return void
   * @throws \RuntimeException If request has not been submitted.
   */
  protected function throwIfNotSubmitted () {
    if (!$this->wasSubmitted()) {
      throw new \RuntimeException('Request not submitted.');
    }
  } //end throwIfNotSubmitted


  /**
   * Get the HTTP response code sent by the server.
   * @return int HTTP response code
   * @throws \RuntimeException If request has not been submitted.
   */
  public function getResponseHttpCode () {
    $this->throwIfNotSubmitted();
    return $this->responseHttpCode;
  }

  /**
   * Set the HTTP response code sent by the server.
   * @param int $code HTTP response code
   * @return Request Self, for message chaining
   */
  protected function setResponseHttpCode ($code) {
    $this->responseHttpCode = $code;
    return $this;
  }


  /**
   * Get the HTTP response headers sent by the server.
   *
   * Keys in the header array are the header names. The value is either the
   * raw header contents or an array of raw header contents if that particular
   * header appeared more than once in the response.
   *
   * @return array HTTP response headers
   * @throws \RuntimeException If request has not been submitted.
   */
  public function getResponseHeaders () {
    $this->throwIfNotSubmitted();
    return $this->responseHeaders;
  }

  /**
   * Get a particular HTTP response header sent by the server.
   *
   * @param string $name Header name
   * @return mixed Header value or null if not found. Value may be an array if
   *    the named header occured more than once in the server's response.
   * @throws \RuntimeException If request has not been submitted.
   */
  public function getResponseHeader ($name) {
    $this->throwIfNotSubmitted();
    if (isset($this->responseHeaders[$name])) {
      return $this->responseHeaders[$name];
    } else {
      return null;
    }
  } //end getResponseHeader

  /**
   * Set the HTTP response headers sent by the server.
   * @param array $headers HTTP response headers
   * @return Request Self, for message chaining
   */
  protected function setResponseHeaders ($headers) {
    $this->responseHeaders = $headers;
    return $this;
  }


  /**
   * Get the HTTP response body sent by the server.
   * @return string HTTP response body
   * @throws \RuntimeException If request has not been submitted.
   */
  public function getResponseBody () {
    $this->throwIfNotSubmitted();
    return $this->responseBody;
  }

  /**
   * Set the HTTP response body sent by the server.
   * @param array $body HTTP response body
   * @return Request Self, for message chaining
   */
  protected function setResponseBody ($body) {
    $this->responseBody = $body;
    return $this;
  }


  /**
   * Get the cURL response information.
   * @return string HTTP response body
   * @throws \RuntimeException If request has not been submitted.
   */
  public function getResponseCurlInfo () {
    $this->throwIfNotSubmitted();
    return $this->responseCurlInfo;
  }

  /**
   * Set the cURL response information.
   * @param array $info Curl info
   * @return Request Self, for message chaining
   */
  protected function setResponseCurlInfo ($info) {
    $this->responseCurlInfo = $info;
    return $this;
  }


  /**
   * Submit this request.
   *
   * This request will be updated with the results of the request which can
   * then be retrieved using getResponseHttpCode() and releated methods.
   *
   * @param bool $failIfNot200 Throw an exception if a non-200 status was sent?
   * @return Request Request with response data populated
   * @throws Exception On cURL failure
   */
  public function submit ($failIfNot200 = null) {
    if ($this->wasSubmitted()) {
      throw new Exception("Request be reused!");
    }
    $ch = $this->createCurlRequest();
    $raw = curl_exec($ch);
    $this->processCurlResponse($ch, curl_errno($ch), curl_error($ch), $raw);
    $this->validateResponse($failIfNot200);
    return $this;
  }


  /**
   * Prepare a cURL handle for this request.
   * @return resource Curl handle ready to be submitted
   */
  protected function createCurlRequest () {
    // merge any custom cURL options into the default set
    $curlOpts = Util::mergeCurlOptions(
        self::$defaultCurlOpts, $this->getCurlOptions());

    // set basic options that we always want to use
    $curlOpts[CURLOPT_RETURNTRANSFER] = true;
    $curlOpts[CURLOPT_HEADER] = true;
    $curlOpts[CURLOPT_FAILONERROR] = false;
    if (defined('CURLINFO_HEADER_OUT')) {
      $curlOpts[CURLINFO_HEADER_OUT] = true;
    }

    // timeouts less than 1 sec fail unless we disable signals
    // see http://www.php.net/manual/en/function.curl-setopt.php#104597
    if (defined('CURLOPT_TIMEOUT_MS') ||
        defined('CURLOPT_CONNECTTIMEOUT_MS')) {
      if ((isset($curlOpts[CURLOPT_TIMEOUT_MS]) &&
          $curlOpts[CURLOPT_TIMEOUT_MS] < 1000) ||
          (isset($curlOpts[CURLOPT_CONNECTTIMEOUT_MS]) &&
          $curlOpts[CURLOPT_CONNECTTIMEOUT_MS] < 1000)) {
        $curlOpts[CURLOPT_NOSIGNAL] = true;
      }
    }

    // prepare the request
    $curlOpts[CURLOPT_URL] = $this->url;
    if (self::METHOD_POST == $this->getMethod()) {
      // using CURLOPT_CUSTOMREQUEST changes the behavior for POST
      // this change forces strict RFC2616:10.3.3 compliance which doesn't
      // work too well with many internet sites.
      $curlOpts[CURLOPT_POST] = true;
    } else {
      $curlOpts[CURLOPT_CUSTOMREQUEST] = $this->getMethod();
    }
    $curlOpts[CURLOPT_USERAGENT] = $this->getUserAgent();

    if ($this->getPostBody()) {
      // add post payload
      $pBody = $this->getPostBody();
      if (!is_array($pBody)) {
        // caller supplied a URI-encoded payload, so make sure we set the
        // content-length header
        $len = mb_strlen($pBody, 'latin1');
        $this->addHeader("Content-Length: {$len}");
      }
      $curlOpts[CURLOPT_POSTFIELDS] = $pBody;
    }

    if ($this->getHeaders()) {
      // add custom headers
      $curlOpts[CURLOPT_HTTPHEADER] = $this->getHeaders();
    }

    // remember the options we used. Might be handy for debugging.
    $this->setCurlOptions($curlOpts);

    // create curl resource
    $ch = curl_init();

    // apply options
    curl_setopt_array($ch, $curlOpts);

    return $ch;
  } //end createCurlRequest


  /**
   * Process an submitted cURL response.
   * @param resource $ch Curl handle
   * @param int      $errCode Curl error code
   * @param string   $errMsg Curl error message
   * @param string   $rawResp Raw response
   * @return Request Request with response data populated
   */
  protected function processCurlResponse ($ch, $errCode, $errMsg, $rawResp) {
    // check error codes
    $this->responseCurlErr = $errCode;
    $this->responseCurlErrMessage = $errMsg;

    if (CURLE_OK == $errCode) {
      $info = curl_getinfo($ch);
      $hdrSize = $info['header_size'];
      $respCode = (int) $info['http_code'];

      // parse the raw response
      $rawHeaders = mb_substr($rawResp, 0, $hdrSize, 'latin1');
      $respBody =  mb_substr(
          $rawResp, $hdrSize, mb_strlen($rawResp, 'latin1'), 'latin1');

      // parse response headers
      if ($info['redirect_count'] > 0) {
        // discard redirect headers
        // TODO: there may be useful info in here that we want to preserve
        $headerChunks = explode("\r\n\r\n", $rawHeaders);
        $rawHeaders = $headerChunks[$info['redirect_count']];
      }
      $respHeaderParts = explode("\r\n", $rawHeaders);
      $respHeaders = array();
      foreach ($respHeaderParts as $header) {
        if ($header) {
          $parts = explode(': ', $header, 2);
          $name = $parts[0];
          $value = '';
          if (count($parts) == 2) {
            $value = $parts[1];
          }
          if (isset($respHeaders[$name])) {
            if (!is_array($respHeaders[$name])) {
              // convert single value to collection
              $respHeaders[$name] = array($respHeaders[$name]);
            }
            $respHeaders[$name][] = $value;
          } else {
            $respHeaders[$name] = $value;
          }
        }
      } //end foreach $header

      // fill in request with response
      $this->setResponseCurlInfo($info);
      $this->setResponseHttpCode($respCode);
      $this->setResponseHeaders($respHeaders);
      $this->setResponseBody($respBody);
    } //end if CURLE_OK

    curl_close($ch);
    return $this;
  } //end processCurlResponse


  /**
   * Check the cURL response code and throw an exception if it is an error.
   * @param bool $failIfNot200 Throw an exception if a non-200 status was sent?
   * @return void
   * @throws Exception On cURL failure
   */
  public function validateResponse ($failIfNot200 = null) {
    if (null === $failIfNot200) {
      $failIfNot200 = $this->defaultFailIfNot200;
    }

    if (CURLE_OK != $this->responseCurlErr) {
      $exClazz = 'Exception';

      switch ($this->responseCurlErr) {
        case CURLE_UNSUPPORTED_PROTOCOL:
        case CURLE_URL_MALFORMAT:
          $exClazz = 'BadUrlException';
          break;

        case CURLE_COULDNT_RESOLVE_HOST:
          $exClazz = 'DnsFailureException';
          break;

        case CURLE_COULDNT_CONNECT:
          $exClazz = 'ConnectFailedException';
          break;

        case CURLE_HTTP_RETURNED_ERROR:
          $exClazz = 'StatusCodeException';
          break;

        case CURLE_OPERATION_TIMEDOUT:
          $exClazz = 'TimeoutException';
          break;

        case CURLE_PEER_FAILED_VERIFICATION:
        case CURLE_SSL_CACERT:
        case CURLE_SSL_CACERT_BADFILE:
        case CURLE_SSL_CERTPROBLEM:
        case CURLE_SSL_CERTPROBLEM:
        case CURLE_SSL_CIPHER:
        case CURLE_SSL_CONNECT_ERROR:
        case CURLE_SSL_CRL_BADFILE:
        case CURLE_SSL_ENGINE_INITFAILED:
        case CURLE_SSL_ENGINE_NOTFOUND:
        case CURLE_SSL_ENGINE_SETFAILED:
        case CURLE_SSL_ISSUER_ERROR:
        case CURLE_SSL_SHUTDOWN_FAILED:
        case CURLE_USE_SSL_FAILED:
          $exClazz = 'SslException';
          break;

      } //end switch
      throw new $exClazz(
          $this->responseCurlErrMessage, $this->responseCurlErr, $this);
    } //end if !ok

    if ($failIfNot200) {
      $code = $this->getResponseHttpCode();
      if ($code < 200 || $code > 299) {
        throw new StatusCodeException(
            "HTTP Error: ({$code}) from {$this->url}",
            CURLE_HTTP_RETURNED_ERROR, $this);
      }
    }
  } //end validateResponse


  /**
   * Submit a group of requests in parallel.
   *
   * Uses the curl_multi_exec engine to fire off several requests in parallel
   * and waits for all responses to finish before returing the collective
   * results.
   *
   * @param array $requests List of requests to submit
   * @return array List of submitted requests
   * @throws Exception If a fatal multi error occurs.
   */
  public static function parallelSubmit ($requests) {
    $handles = array();
    $mh = curl_multi_init();

    // make a curl handle for each request
    foreach ($requests as $req) {
      $ch = $req->createCurlRequest();
      $handles[(string) $ch] = $req;
      curl_multi_add_handle($mh, $ch);
    }

    $reqsRunning = 0;

    // fire off initial requests
    do {
      $status = curl_multi_exec($mh, $reqsRunning);
    } while (CURLM_CALL_MULTI_PERFORM == $status);

    // process the requests
    while ($reqsRunning && CURLM_OK == $status) {
      // wait to be woken up by network activity
      $selectReady = curl_multi_select($mh);

      if ($selectReady > 0) {
        // one or more requests are finished
        while ($info = curl_multi_info_read($mh)) {
          self::handleMultiResponse($info, $mh, $handles);
        }
      } //end if selectReady

      if (-1 != $selectReady) {
        // continue processing
        do {
          $status = curl_multi_exec($mh, $reqsRunning);
        } while (CURLM_CALL_MULTI_PERFORM == $status);
      }
    } //end while requests to process

    // check for critical failure
    if (CURLM_OK != $status) {
      throw new Exception(
          "Fatal error [{$status}] processing multiple requests", $status);
    }

    // any remaining results should be ready now
    while ($handles && ($info = curl_multi_info_read($mh))) {
      self::handleMultiResponse($info, $mh, $handles);
    }

    curl_multi_close($mh);
    return $requests;
  } //end parallelSubmit

  /**
   * Handle a curl_multi_info_read() response message.
   *
   * @param array $info Status message from Curl
   * @param resource $mh Curl multi handle
   * @param array &$handles List of Curl handles still outstanding
   * @return void
   * @see self::parallelSubmit()
   */
  protected static function handleMultiResponse ($info, $mh, &$handles) {
    $ch = $info['handle'];
    $req = $handles[(string) $ch];

    $rawResp = null;
    if (CURLE_OK == $info['result']) {
      // read the response from the handle
      $rawResp = curl_multi_getcontent($ch);
    }
    $req->processCurlResponse(
        $ch, $info['result'], curl_error($ch), $rawResp);

    // remove this handle from the queue
    curl_multi_remove_handle($mh, $ch);
    unset($handles[(string) $ch]);
  } //end handleMultiResponse


  /**
   * Convenience method to perform a GET request.
   *
   * Provided parameters will be "application/x-www-form-urlencoded" encoded
   * and appended to the provided URL.
   *
   * @param string $url URL to get (eg https://www.keynetics.com/page.php)
   * @param array $parms Array of key => value pairs to send
   * @param array $options Array of extra options to pass to cURL
   * @return Request Submitted request
   * @throws Exception On failure
   */
  public static function get ($url, $parms = null, $options = null) {
    $url = Util::addQueryData($url, $parms);
    $r = new Request($url, self::METHOD_GET, null, null, $options);
    return $r->submit();
  } //end get


  /**
   * Convenience method to POST data to a URL and retrieve the results.
   *
   * Provided parms will be encoded as "application/x-www-form-urlencoded"
   * data before being sent.
   *
   * @param string $url Full URL to post to (eg
   *    https://www.keynetics.com/page.php)
   * @param array $parms Array of key => value pairs to post
   * @param array $options Array of extra options to pass to cURL
   * @return Request Submitted request
   * @throws Exception On failure
   */
  public static function post ($url, $parms, $options = null) {
    $encParms = Util::urlEncode($parms);
    $r = new Request(
        $url, self::METHOD_POST, $encParms, null, $options);
    return $r->submit();
  } //end post


  /**
   * Convenience method to POST content in the form of a raw string to a URL.
   *
   * This is useful for manually constructed SOAP requests and other document
   * body type operations. Default content type is text/xml.
   *
   * @param string $url Full URL to post to (eg
   *    https://www.keynetics.com/page.php)
   * @param string $content Raw HTTP request body to be posted
   * @param string $contentType Value of the HTTP Content-Type header
   * @param array $options Array of extra options to pass to cURL
   * @return Request Submitted request
   * @throws Exception On failure
   */
  public static function postContent (
      $url, $content, $contentType = 'text/xml', $options = null) {
    $headers = array("Content-type: {$contentType}");
    $r = new Request(
        $url, self::METHOD_POST, $content, $headers, $options);
    return $r->submit();
  } //end postContent


  /**
   * Convenience method to POST data to a URL and retrieve the results.
   *
   * Provided parms will be encoded as "multipart/form-data" data before being
   * sent.
   *
   * @param string $url Full URL to post to (eg
   *    https://www.keynetics.com/page.php)
   * @param array $parms Array of key => value pairs to post
   * @param array $options Array of extra options to pass to cURL
   * @return Request Submitted request
   * @throws Exception On failure
   */
  public static function postMultipart ($url, $parms, $options = null) {
    $r = new Request(
        $url, self::METHOD_POST, $parms, null, $options);
    return $r->submit();
  } //end postMultipart

} //end Request
