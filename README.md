
Installation
=

Before creating your first Symfony application you must:

* Install PHP 8.1 or higher and these PHP extensions (which are installed and enabled by default in most PHP 8 installations): Ctype, iconv, PCRE, Session, SimpleXML, and Tokenizer;


* Install Composer, which is used to install PHP packages.

Optionally, you can also install Symfony CLI. This creates a binary called symfony that provides all the tools you need to develop and run your Symfony application locally.

The symfony binary also provides a tool to check if your computer meets all requirements. Open your console terminal and run this command:
```
symfony check:requirements
```

To install dependencies:
```
cd user-api/
composer install

symfony console doctrine:migrations:migrate
```

In order to start the DB server in docker run:
```
docker-compose up -d
```

Testing
=

To run unit tests execute
```
php bin/phpunit --testsuite unit
```

And for the functional tests
```
symfony console --env=test doctrine:database:drop --force
symfony console --env=test doctrine:database:create
symfony console --env=test doctrine:schema:create
symfony console --env=test doctrine:fixtures:load
php bin/phpunit --testsuite functional
```