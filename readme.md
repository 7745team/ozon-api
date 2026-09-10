# OzonApi

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]

This is where your description should go. Take a look at [contributing.md](contributing.md) to see a to do list.

## Installation

Via Composer

``` bash
$ composer require 7745team/ozon-api
$ php artisan migrate
$ php artisan vendor:publish 
```

## Usage

Commands list

``` bash
$ php artisan ozon:tables-refresh
$ php artisan ozon:sync-categories
$ php artisan ozon:sync-attributes
$ php artisan ozon:sync-options
$ php artisan ozon:sync-options {category_id}
$ php artisan ozon:sync-options {category_id} {attribute_id} {type_id}
```

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email zhen.kib@gmail.com instead of using the issue tracker.

## Credits

- [Author Name][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/7745team/ozon-api.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/7745team/ozon-api.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/7745team/ozon-api/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/7745team/ozon-api
[link-downloads]: https://packagist.org/packages/7745team/ozon-api
[link-travis]: https://travis-ci.org/7745team/ozon-api
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/7745team
[link-contributors]: ../../contributors
