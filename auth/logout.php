<?php
include_once '../core/header.php';
    $_SESSION['logged'] = false;
    session_destroy();
    header('Location: ../index.php');
    exit;
?>
