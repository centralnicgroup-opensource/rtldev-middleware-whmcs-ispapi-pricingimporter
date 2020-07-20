#!/bin/bash
PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
phpcs --ignore=registrars/ispapi/lib --standard=PHPCompatibility -q -n --colors --runtime-set testVersion "$PHP_VERSION" ispapidpi.php tldlib_array.php || exit 1;