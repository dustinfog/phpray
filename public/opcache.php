<?php

$status = opcache_get_status (true);

header("Content-Type: application/json");
echo json_encode($status, JSON_PRETTY_PRINT);
