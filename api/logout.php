<?php
// api/logout.php
session_start();
session_destroy();
header('Location: /web_app/user/login.php');
exit;