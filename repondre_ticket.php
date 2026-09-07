<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header("Location: home.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'], $_POST['reponse'])) {
    $ticket_id = $_POST['ticket_id'];
    $reponse = trim($_POST['reponse']);
    
    if (!empty($reponse)) {
        $update = $pdo->prepare("UPDATE support_tickets SET reponse = ?, statut = 'Résolu' WHERE id = ?");
        $update->execute([$reponse, $ticket_id]);
    }
}

header("Location: support.php");
exit();
?>