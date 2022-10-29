#!/usr/bin/env bash

set -e

VERSION=$1
REPOPATH=$2

EXTRAMODULES="adfs authorize authx509 consent consentadmin
discopower ldap memcookie metarefresh negotiate oauth
radius statistics sqlauth"

if ! shift; then
    echo "$0: Missing required version parameter." >&2
    exit 1
fi

if [ -z "$VERSION" ]; then
    echo "$0: Empty version parameter." >&2
    exit 1
fi

if [ -z "$REPOPATH" ]; then
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

git clone --depth 1 --branch $TAG $REPOPATH $TARGET

if [ ! -x "$TARGET/composer.phar" ]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=$TARGET
fi

# Set the version in composer.json
php "$TARGET/composer.phar" config version "v$VERSION" -d "$TARGET"

# Install dependencies (without vcs history or dev tools)
php "$TARGET/composer.phar" install --no-dev --prefer-dist -o -d "$TARGET"

cd $TARGET
npm install
npm audit fix
npx browserslist@latest --update-db
npm run build

mkdir -p config metadata cert log data
cp -rv config-templates/* config/
cp -rv metadata-templates/* metadata/
rm -rf .git
rm -rf node_modules
rm www/assets/js/stylesheet.js
rm .editorconfig
rm .gitattributes
rm -r .github/
rm phpunit.xml
rm {cache,config,metadata,locales}/.gitkeep
rm bin/build-release.sh

cd ..

cp -a "$TARGET" "$TARGET-full"

MODS=""
for i in $EXTRAMODULES; do MODS="$MODS simplesamlphp/simplesamlphp-module-$i"; done

php "$TARGET/composer.phar" require --update-no-dev --prefer-dist --ignore-platform-reqs -n -o -d "$TARGET-full" $MODS

export GZIP=-9
tar --owner 0 --group 0 -cvzf "$TARGET.tar.gz" "$TARGET"
tar --owner 0 --group 0 -cvzf "$TARGET-full.tar.gz" "$TARGET-full"

rm -rf "$TARGET $TARGET-full"

echo "Created: /tmp/$TARGET.tar.gz /tmp/$TARGET-full.tar.gz"
