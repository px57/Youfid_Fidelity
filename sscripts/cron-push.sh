#!/bin/bash

PHP_SCRIPT=/apps/apache/backoffice-youfid/lib/cron_push.php
LOCK_FILE=/var/lock/youfid-cron

if mkdir $LOCK_FILE; then
    echo "Sending pushs to push server ...";
    php $PHP_SCRIPT;
    rmdir $LOCK_FILE
fi

