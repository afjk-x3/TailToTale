<?php
session_start();
session_unset();
session_destroy();
header('Location: /TAILTOTALE/frontend/pages/index.php');
exit;
