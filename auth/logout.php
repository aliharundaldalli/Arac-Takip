<?php
// auth/logout.php

// Oturumu başlat (yoksa bitiremeyiz)
session_start();

// Tüm oturum değişkenlerini boşalt
$_SESSION = [];

// Oturumu tamamen yok et
session_destroy();

// Kullanıcıyı giriş sayfasına yönlendir
header("Location: login.php");
exit;
?>