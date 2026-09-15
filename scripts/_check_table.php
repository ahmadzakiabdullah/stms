<?php
$cols = DB::select('SHOW COLUMNS FROM participants');
foreach ($cols as $c) {
    echo $c->Field . ' (' . $c->Type . ')' . PHP_EOL;
}
