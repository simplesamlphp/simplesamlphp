#!/usr/bin/env bash

set -e

TAG=$1
if ! shift; then
    echo "$0: Missing required tag parameter." >&2
    exit 1
fi

if [ -z "$TAG" ]; then
    echo "$0: Empty tag parameter." >&2
    exit 1
fi

cd /tmp

if [ -a "$TAG" ]; then
    echo "$0: Destination already exists: $TAG" >&2
    exit 1
fi

umask 0022

REPOPATH="https://github.com/simplesamlphp/simplesamlphp.git"

git clone $REPOPATH $TAG
cd $TAG
git checkout $TAG
cd ..

# Use composer only on newer versions that have a composer.json
if [ -f "$TAG/composer.json" ]; then
    if [ ! -x "TAG/composer.phar" ]; then
        curl -sS https://getcomposer.org/installer | php -- --install-dir=$TAG
    fi

    # Install dependencies (without vcs history or dev tools)
    php "$TAG/composer.phar" install --no-dev --prefer-dist -o -d "$TAG"
fi

mkdir -p "$TAG/config" "$TAG/metadata"
cp -rv "$TAG/config-templates/"* "$TAG/config/"
cp -rv "$TAG/metadata-templates/"* "$TAG/metadata/"
rm -rf "$TAG/.git"
rm "$TAG/composer.phar"
tar --owner 0 --group 0 -cvzf "$TAG.tar.gz" "$TAG"
rm -rf "$TAG"

echo "Created: /tmp/$TAG.tar.gz"
