set -x

IGNORE_PLATFORM_REQUIREMENTS=""

if [ "$TRAVIS_PHP_VERSION" = 'nightly' ] || [ "$TRAVIS_PHP_VERSION" = '7.4snapshot' ]; then
    IGNORE_PLATFORM_REQUIREMENTS="--ignore-platform-reqs"
fi

composer update --prefer-dist $IGNORE_PLATFORM_REQUIREMENTS

if [ "$DEPENDENCIES" = 'low' ]; then
    composer update --prefer-lowest --prefer-stable $IGNORE_PLATFORM_REQUIREMENTS
fi
