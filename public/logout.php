<?php
session_start();

// مسح كل الكوكيز
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time() - 3600, '/');
    }
}

// إعادة التوجيه إلى صفحة تسجيل الدخول
header("Location: login.php");
