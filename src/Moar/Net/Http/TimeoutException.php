<?php
/**
 * @package Moar\Net\Http
 */

namespace Moar\Net\Http;

/**
 * Signals that a timeout event halted the request.
 *
 * This could be a timeout of the DNS lookup, the initial socket connect, the
 * SSL handshake or the total runtime of the request. Check the exception
 * message for more details on the origin of the failure.
 *
 * @package Moar\Net\Http
 */
class TimeoutException extends Exception {
} //end TimeoutException
