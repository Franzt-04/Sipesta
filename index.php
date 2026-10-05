<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;

}

switch ($_SESSION['role']) {

    case 'admin':
        header("Location: admin/index.php");
        break;

    case 'penjual':
        header("Location: penjual/index.php");
        break;

    case 'pembeli':
        header("Location: pembeli/index.php");
        break;

    default:
        session_destroy();
        header("Location: login.php");
        break;
}

exit;