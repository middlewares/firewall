# middlewares/firewall

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
![Testing][ico-ga]
[![Total Downloads][ico-downloads]][link-downloads]

Middleware to provide IP filtering using [M6Web/Firewall](https://github.com/M6Web/Firewall).

## Requirements

* PHP >= 7.2
* A [PSR-7 http library](https://github.com/middlewares/awesome-psr15-middlewares#psr-7-implementations)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

## Installation

This package is installable and autoloadable via Composer as [middlewares/firewall](https://packagist.org/packages/middlewares/firewall).

```sh
composer require middlewares/firewall
```

## Example

```php
Dispatcher::run([
    (new Middlewares\Firewall(['123.0.0.*']))
        ->blacklist([
            '123.0.0.1',
            '123.0.0.2',
        ])
]);
```

## Usage

The constructor accepts an array with the whitelist ips. [See the ip formats allowed](https://github.com/M6Web/Firewall#entries-formats).

```php
$firewall = new Middlewares\Firewall([
    '127.0.0.1',
    '198.168.0.*',
]);
```

Optionally, you can provide a `Psr\Http\Message\ResponseFactoryInterface` as the second argument to create the error response (`403`). If it's not defined, [Middleware\Utils\Factory](https://github.com/middlewares/utils#factory) will be used to detect it automatically.

```php
$responseFactory = new MyOwnResponseFactory();

$firewall = new Middlewares\Firewall($whitelist, $responseFactory);
```

### blacklist

The blacklist ips. The ip format is the same than whitelist.

```php
$whitelist = [
    '127.0.0.1',
    '198.168.0.*',
];
$blacklist = [
    '192.168.0.50',
];

$firewall = (new Middlewares\Firewall($whitelist))->blacklist($blacklist);
```

### ipAttribute

By default uses the `REMOTE_ADDR` server parameter to get the client ip. Use this option if you want to use a request attribute. Useful to combine with any ip detection middleware, for example [client-ip](https://github.com/middlewares/client-ip):

```php
Dispatcher::run([
    //detect the client ip and save it in client-ip attribute
    new Middlewares\ClientIP(),

    //use that attribute
    (new Middlewares\Firewall(['123.0.0.*']))
        ->ipAttribute('client-ip')
]);
```

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/firewall.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-ga]: https://github.com/middlewares/firewall/workflows/testing/badge.svg
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/firewall.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/firewall
[link-downloads]: https://packagist.org/packages/middlewares/firewall
