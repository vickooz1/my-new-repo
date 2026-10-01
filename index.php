<?php
if (isset($_GET['login'])) {
	require __DIR__ . '/login.php';
} else {
	require __DIR__ . '/dashboard.php';
}
