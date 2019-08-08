#!/usr/bin/env bash

PHP='/usr/bin/env php'
RETURN=0

# check PHP files
for FILE in `find attributemap bin config-templates lib metadata-templates modules templates tests www -name "*.php"`; do
    $PHP -l $FILE > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "Syntax check failed for ${FILE}"
        RETURN=`expr ${RETURN} + 1`
    fi
done

# check JSON files
for FILE in `find dictionaries modules -name "*.json"`; do
    $PHP -r "exit((json_decode(file_get_contents('$FILE')) === null) ? 1 : 0);"
    if [ $? -ne 0 ]; then
        echo "Syntax check failed for ${FILE}"
        RETURN=`expr ${RETURN} + 1`
    fi
done

# check YAML files
for i in `find . -path ./vendor -prune -o -path ./node_modules -prune -name '*.yml' -o -name '*.yaml' -print`
do
    if [ -f "$i" ]; then
        FILE="${i%/*}/${i##*/}"
        $PHP -r "require(dirname(dirname(__FILE__)).'/vendor/autoload.php'); use Symfony\Component\Yaml\Yaml; use Symfony\Component\Yaml\Exception\ParseException; try { Yaml::parseFile('$FILE'); } catch(ParseException \$e) { exit(1); }"
        if [ $? -ne 0 ]; then
            echo "Syntax check failed for ${FILE}"
            RETURN=$((RETURN + 1))
        fi
    fi
done

exit $RETURN
