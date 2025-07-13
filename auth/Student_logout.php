<?php
include_once '../core/header.php';
    $_SESSION['login'] = FALSE;
    session_destroy();
    header('Location: ../index.php');
    exit;
?>
