<?php
/**
 * Public entry point. It sits outside the password-protected file list on
 * purpose, so it reveals nothing at all -- it only bounces to the dashboard,
 * which Basic auth then guards.
 */
require __DIR__ . '/lib.php';

header('Location: ' . dtb_url('admin.php'), true, 302);
exit;
