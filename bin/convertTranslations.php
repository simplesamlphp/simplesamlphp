#!/usr/bin/php -q
<?php

/**
 * Converts PO files with old-style translation tags into regular gettext
 * po files as used by current SimpleSAMLphp.
 */

declare(strict_types=1);

function mergeWithSource(array $sourcePairs, array $destPairs): array
{
    $mergedPairs = [];

    foreach ($sourcePairs as $msgId => $msgStr) {
        if (array_key_exists($msgId, $destPairs)) {
            // the index is becoming the msgId but comes as the string "msgstr" from the file, so convert it
            $mergedPairs[preg_replace("/^msgstr/", "msgid", $msgStr)] = $destPairs[$msgId];
        } else {
            $mergedPairs[preg_replace("/^msgstr/", "msgid", $msgStr)] = "msgstr \"\"\n";
        }
    }
    return $mergedPairs;
}

function codifyWithSource(array $sourcePairs, array $destPairs): array
{
    $mergedPairs = [];

    foreach ($sourcePairs as $msgId => $msgStr) {
        $modifiedKey = preg_replace("/^msgstr/", "msgid", $msgStr);
        if (isset($destPairs[$modifiedKey])) {
            $mergedPairs[$msgId] = $destPairs[$modifiedKey];
        } else {
            $mergedPairs[$msgId] = "msgstr \"\"\n";
        }
    }
    return $mergedPairs;
}

function dissectFile(array $fileInputRaw): array
{
    $pairs = [];
    // create an array with MSGID => MSGSTR
    foreach ($fileInputRaw as $rowIndex => $oneLine) {
        if (preg_match("/^msgid/", $oneLine)) {
            $msgId = $oneLine;
            $nextLineCountMsgId = $rowIndex + 1;
            while (substr($fileInputRaw[$nextLineCountMsgId], 0, 1) === '"') {
                $msgId .= $fileInputRaw[$nextLineCountMsgId];
                $nextLineCountMsgId = $nextLineCountMsgId + 1;
            }
            // we now have the full msgid in $msgid. Now find the full subsequent msgstr
            // msgstr immediately follows the last msgid line; and can continue on
            // multiple lines itself
            $msgStr = $fileInputRaw[$nextLineCountMsgId];
            $nextLineCountMsgStr = $nextLineCountMsgId + 1;
            while (
                isset($fileInputRaw[$nextLineCountMsgStr]) &&
                substr($fileInputRaw[$nextLineCountMsgStr], 0, 1) === '"'
            ) {
                $msgStr .= $fileInputRaw[$nextLineCountMsgStr];
                $nextLineCountMsgStr = $nextLineCountMsgStr + 1;
            }
            $pairs[$msgId] = $msgStr;
        }
    }
    return $pairs;
}

if (!isset($argv[3])) {
    fwrite(STDERR, "
This script needs three arguments:

1) MERGE, CODIFY or SOURCEONLY
   - MERGE creates a .po file with source-lang as msgId and dest-lang as msgStr
   - CODIFY creates a .po file with the common codes as msgId and dest-lang as msgStr
   - SOURCEONLY creates a .po file with source-lang as msgId and an empty msgStr

2) filename of the .po with codes as msgStr and the source language (typically English) as msgStr

3) in case of
   - MERGE, SOURCEONLY: filename of the .po with codes as msgStr and the destination language as msgStr
   - CODIFY: filename of the .po file with source language as msgId and destination language as msgStr
     (i.e. the input to CODIFY is the result of a previous MERGE)

");
    exit(1);
}

switch ($argv[1]) {
    case "MERGE":
        fwrite(STDERR, "Will merge two language into one .po file based on identical msgIds.\n");
        break;
    case "CODIFY":
        fwrite(STDERR, "Will create .po file with codes as msgIds and dest language translations as msgStr.\n");
        break;
    case "SOURCEONLY":
        fwrite(STDERR, "Will create .po file with source lang msgid and empty msgstr.\n");
        break;
    default:
        fwrite(STDERR, "The first parameter is either MERGE or CODIFY.\n");
        exit(1);
}

$sourceLangRaw = file($argv[2]);
$destLangRaw = file($argv[3]);

if ($sourceLangRaw === false || $destLangRaw === false) {
    fwrite(STDERR, "At least one input file was not readable!\n");
    exit(1);
}

$sourcePairs = dissectFile($sourceLangRaw);
$destPairs = dissectFile($destLangRaw);

switch ($argv[1]) {
    case "SOURCEONLY":
        fwrite(
            STDERR,
            "Merging (for nullify) " . count($sourcePairs) .
            " entries from source language (destination language has " .
            count($destPairs) . " already.\n",
        );
        $outputPairs = mergeWithSource($sourcePairs, $destPairs);
        foreach ($outputPairs as $key => $value) {
            $outputPairs[$key] = "msgstr \"\"\n";
        }
        break;
    case "MERGE":
        fwrite(
            STDERR,
            "Merging " . count($sourcePairs) .
            " entries from source language (destination language has " .
            count($destPairs) . " already.\n",
        );
        $outputPairs = mergeWithSource($sourcePairs, $destPairs);
        break;
    case "CODIFY":
        fwrite(
            STDERR,
            "Codifying " . count($sourcePairs) .
            " entries from destination language (pool has " .
            count($destPairs) . " candidates).\n",
        );
        $outputPairs = codifyWithSource($sourcePairs, $destPairs);
        break;
}

foreach ($outputPairs as $msgId => $msgStr) {
    echo $msgId;
    echo $msgStr;
    echo "\n";
}
