#!/usr/bin/env bash

set -e

VERSION=$1
REPOPATH=$2

if ! shift; then
    echo "$0: Missing required version parameter." >&2
    exit 1
fi

if [ -z "$VERSION" ]; then
    echo "$0: Empty version parameter." >&2
    exit 1
fi

if [ -z "$REPO" ]; then
    REPOPATH="https://github.com/simplesamlphp/simplesamlphp.git"
fi

TAG="v$VERSION"
TARGET="simplesamlphp-$VERSION"

cd /tmp

if [ -a "$TARGET" ]; then
    echo "$0: Destination already exists: $TARGET" >&2
    exit 1
fi

umask 0022

git clone $REPOPATH $TARGET
cd $TARGET
git checkout $TAG
cd ..

# Use composer only on newer versions that have a composer.json
if [ -f "$TARGET/composer.json" ]; then
    if [ ! -x "$TARGET/composer.phar" ]; then
        curl -sS https://getcomposer.org/installer | php -- --install-dir=$TARGET
    fi

    # Install dependencies (without vcs history or dev tools)
    php "$TARGET/composer.phar" install --no-dev --prefer-dist -o -d "$TARGET"
fi

mkdir -p "$TARGET/config" "$TARGET/metadata" "$TARGET/cert" "$TARGET/log"
cp -rv "$TARGET/config-templates/"* "$TARGET/config/"
cp -rv "$TARGET/metadata-templates/"* "$TARGET/metadata/"
rm -rf "$TARGET/.git"
rm "$TARGET/.coveralls.yml"
rm "$TARGET/.travis.yml"
rm "$TARGET/.gitignore"
rm "$TARGET/.editorconfig"
rm "$TARGET/composer.phar"
tar --owner 0 --group 0 -cvzf "$TARGET.tar.gz" "$TARGET"
rm -rf "$TARGET"

echo "Created: /tmp/$TARGET.tar.gz"

