<?php
session_start();
require_once(__DIR__ . '/../secure_config.php');
require_once('../includes/Database.php');
require_once('../vendor/fpdf/fpdf.php');
require_once('../includes/send_email.php');
require_once('../includes/ZiinaPayment.php');
require_once('../includes/whatsapp_notify.php');
require_once('../includes/shipping.php');

define('PAYMENT_METHOD_COD', 'COD');
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_PAID', 'paid');
