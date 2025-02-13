# DebugSP

[TOC]

## Introduction

The debugsp allows you to logon to any SP offered by your SimpleSAMLphp installation.
This is similar to the functionality on the admin/test page but using debugsp does not
require you to login as the admin user. This can be useful if an IdP you are talking to
wishes to verify that a login session can be created using your SP and their IdP.

## Preparations

You need to enable the module in `config.php`

```shell
'module.enable' => [
     'debugsp => true,
     …
],
```

## Using debugsp

Visit the link debugsp/test at your site. All the SP you have configured will be listed.
You can try to login as an SP. Once successful you will see a list of the attributes the
IdP supplied. You can then logout again and test another SP if desired.
