<?php
require '../../components/db.php';

logoutCurrentUser();
header('Location: ' . appUrl('/modules/auth/login.php'));
exit;
