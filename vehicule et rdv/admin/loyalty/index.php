<?php

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '../../index.php?action=adminLoyalty';
if ($query !== '') {
    $target .= '&' . $query;
}

header('Location: ' . $target);
exit;
