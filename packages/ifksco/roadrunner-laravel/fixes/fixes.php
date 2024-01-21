<?php

if (!function_exists('\\Symfony\\Component\\HttpFoundation\\File\\is_uploaded_file')) {
    require __DIR__ . '/fix-symfony-file-validation.php';
}

if (!function_exists('\\Symfony\\Component\\HttpFoundation\\File\\move_uploaded_file')) {
    require __DIR__ . '/fix-symfony-file-moving.php';
}
