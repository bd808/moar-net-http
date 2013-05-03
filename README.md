Moar-Net-Http
=============

cURL wrapper and utilities for making HTTP requests.

Part of the [Moar PHP Library][].

[![Build Status][ci-status]][ci-home]
[![Test Coverage][coverage-status]][coverage-home]


Installation
------------
Moar-Net-Http is available on Packagist ([moar/net-http][]) and is installable
via [Composer][].

    {
      "require": {
        "moar/net-http": "dev-master"
      }
    }


If you do not use Composer, you can get the source from GitHub and use any
PSR-0 compatible autoloader.

    $ git clone https://github.com/bd808/moar-net-http.git


Run the tests
-------------
Tests are automatically performed by [Travis CI][]:
[![Build Status][ci-status]][ci-home]
[![Test Coverage][coverage-status]][coverage-home]


    curl -sS https://getcomposer.org/installer | php
    php composer.phar install --dev
    ./vendor/bin/archer


<!-- references -->
[Moar PHP Library]: https://github.com/bd808/moar
[ci-status]: https://travis-ci.org/bd808/moar-net-http.png
[ci-home]: https://travis-ci.org/bd808/moar-net-http
[coverage-status]: https://raw.github.com/bd808/moar-net-http/gh-pages/artifacts/images/travis/variable-width/coverage.png
[coverage-home]: http://bd808.com/moar-net-http/artifacts/tests/coverage
[moar/net-http]: https://packagist.org/packages/moar/net-http
[Composer]: http://getcomposer.org
[Travis CI]: https://travis-ci.org
