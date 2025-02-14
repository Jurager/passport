# Jurager/Passport
[![Latest Stable Version](https://poser.pugx.org/jurager/passport/v/stable)](https://packagist.org/packages/jurager/passport)
[![Total Downloads](https://poser.pugx.org/jurager/passport/downloads)](https://packagist.org/packages/jurager/passport)
[![PHP Version Require](https://poser.pugx.org/jurager/passport/require/php)](https://packagist.org/packages/jurager/passport)
[![License](https://poser.pugx.org/jurager/passport/license)](https://packagist.org/packages/jurager/passport)

This Laravel package simplifies the implementation of single sign-on authentication. It features a centralized user repository and enables the creation of user models in brokers without disrupting app logic. Additionally, it provides methods for incorporating authentication history pages and terminating sessions for either all users or specific ones with ease

> [!NOTE]
> The documentation for this package is currently being written. For now, please refer to this readme for information on the functionality and usage of the package.

- [Requirements](#requirements)
- [Installation](#installation)
- [License](#license)

Requirements
-------------------------------------------
`PHP >= 8.1` and `Laravel 9.x or higher`

Installation
-------------------------------------------

```sh
composer require jurager/passport
```

Run the migrations

```sh
php artisan migrate
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
