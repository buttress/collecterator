# Collecterator: Generator based collections

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-actions]][link-actions]
[![Total Downloads][ico-downloads]][link-downloads]

This library is a fully featured `\Generator` based Collection implementation. 
The goal is to provide a memory efficient fast collection implementation that makes it possible to use familiar
collection methods to work with infinite or very large streams.

Our tests were largely copied from [`tightenco/collect`](https://packagist.org/packages/tightenco/collect) with many 
modifications added to support the deferred processing you get with `Generators`.

For basic usage, see [the `AllMethods.php` example](examples/AllMethods.php)

## Install

Via Composer

``` bash
$ composer require buttress/collecterator
```

## Usage

``` php
$collection = GeneratorCollection::make([1,2,3]);
$collection->filter(function(int $value) {
    return $value % 2;
});

$array = $collection->all();
```

## Change log

Please see [CHANGELOG](https://github.com/buttress/collecterator/releases) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email korvinszanto@gmail.com instead of using the issue tracker.

## Credits

- [Korvin Szanto][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/buttress/collecterator.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-actions]: https://img.shields.io/github/actions/workflow/status/buttress/collecterator/test.yml?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/buttress/collecterator.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/buttress/collecterator
[link-actions]: https://github.com/buttress/collecterator/actions
[link-downloads]: https://packagist.org/packages/buttress/collecterator
[link-author]: https://github.com/korvinszanto
[link-contributors]: ../../contributors
