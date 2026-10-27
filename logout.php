<?php
require_once __DIR__ . '/includes/init.php';

session_destroy();
session_start();
set_flash('Sesion cerrada correctamente.');
redirect('index.php');
