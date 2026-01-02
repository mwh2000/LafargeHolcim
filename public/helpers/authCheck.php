<?php
session_start();

if (empty($_SESSION['token'])) {
    // المستخدم غير مسجل الدخول → نعيد توجيهه
    header("Location: " . rtrim(BASE_URL, '/') . "/public/login.php");
    exit();
}
