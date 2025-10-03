<?php
session_start();
require 'auth_check.php';
require '../db.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - VISION</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h2>Painel Admin</h2>
            <nav class="admin-nav">
                <a href="dashboard.php">Início</a>
                <a href="usuarios.php">Usuários</a>
                <a href="partidas_criar.php">Criar Partida</a>
                <a href="partidas_listar.php">Listar Partidas</a>
                <a href="../logout.php" class="logout">Sair</a>
            </nav>
        </div>
        <div class="admin-content">