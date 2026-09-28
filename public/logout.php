<?php

require __DIR__ . '/partials/bootstrap.php';

$_SESSION = [];
session_destroy();

header('Location: /');
exit;
