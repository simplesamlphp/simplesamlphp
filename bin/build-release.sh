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

REPOPATH="http://simplesamlphp.googlecode.com/svn/tags/$TAG/"

svn export "$REPOPATH"

# Use composer only on newer versions that have a composer.json
if [ -f "$TAG/composer.json" ]; then
    if [ ! -x composer.phar ]; then
        curl -sS https://getcomposer.org/installer | php
    fi

    # Install dependencies (without vcs history or dev tools)
    php composer.phar install --no-dev --prefer-dist -o -d "$TAG"
fi

mkdir -p "$TAG/config" "$TAG/metadata"
cp -rv "$TAG/config-templates/"* "$TAG/config/"
cp -rv "$TAG/metadata-templates/"* "$TAG/metadata/"
tar --owner 0 --group 0 -cvzf "$TAG.tar.gz" "$TAG"
rm -rf "$TAG"

echo "Created: /tmp/$TAG.tar.gz"
