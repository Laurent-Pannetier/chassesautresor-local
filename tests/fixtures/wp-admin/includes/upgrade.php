<?php
function dbDelta(string $sql): void
{
    $GLOBALS['dbDeltaSql'] = $sql;
}
