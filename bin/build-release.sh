#!/usr/bin/env bash

set -e

VERSION=$1
REPOPATH=$2

if ! shift; then
    echo "$0: Missing required version parameter." >&2
    exit 1
elif [ -z "$VERSION" ]; then
    echo "$0: Empty version parameter." >&2
    exit 1
fi

if [ -z "$REPOPATH" ]; then
    REPOPATH="https://github.com/simplesamlphp/simplesamlphp.git"
fi

TAG="v$VERSION"
TARGET="simplesamlphp-$VERSION"
COMPOSER="/tmp/composer.phar"

cd /tmp

if [ -a "$TARGET" ]; then
    echo "$0: Destination already exists: $TARGET" >&2
    exit 1
fi

umask 0022

git clone --depth 1 --branch $TAG $REPOPATH $TARGET
cd $TARGET

if [ ! -x "$COMPOSER" ]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/tmp
fi

# Set the version in composer.json
php "$COMPOSER" config version "v$VERSION"

# Install dependencies (without vcs history or dev tools)
php "$COMPOSER" install --no-dev --prefer-dist -o

npm install
npm audit fix
npx browserslist@latest --update-db
npm run build

php "$COMPOSER" archive -f tar.gz --dir /tmp --file "$TARGET"
rm "$COMPOSER"
echo `shasum -a 256 /tmp/$TARGET.tar.gz`
