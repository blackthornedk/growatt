#!/bin/sh

HOME=/var/lib/growatt
RSYNC=/usr/bin/rsync
PHP=/usr/bin/php
DATADIR=$HOME/data/
SRCDIR=$HOME/data/
SRCHOST=solar.btworld.dk
IMPORT_SCRIPT=$HOME/import-data.php
# sync data dirs
#echo $RSYNC -avze ssh $SRCHOST:$SRCDIR $DATADIR
$RSYNC -aze ssh $SRCHOST:$SRCDIR $DATADIR

#echo $PHP $IMPORT_SCRIPT
$PHP $IMPORT_SCRIPT 
