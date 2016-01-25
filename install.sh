#! /bin/bash
. config.sh
if [ "$DBNAME" = "" ]; then
    echo "ERR: DBNAME non défini dans inc/config.sh";
    exit;
fi
if [ "$DBPASS" = "" ]; then
    echo "ERR: DBPASS non défini dans inc/config.sh";
    exit;
fi
if [ "$DBUSER" = "" ]; then
    echo "ERR: DBUSER non défini dans inc/config.sh";
    exit;
fi

FILES="pgproc/sql/*.sql pgproc/plpgsql/*.sql pgproc/tests/tests.sql"
(echo 'BEGIN TRANSACTION; ' && cat $FILES && echo 'COMMIT; ' ) |  PGPASSWORD=$DBPASS PGOPTIONS="--client-min-messages=warning" psql -q -h localhost -U $DBUSER $DBNAME
