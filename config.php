<?php

$host = 'localhost';
$port = '3306';
$dbname = 'mydb';
$usuario = 'root';
$senha = 'mysql';

try{
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $usuario, $senha);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
    die("Erro na conexão: ". $e->getMessage());
}

try {
    $colunasProduto = [];
    foreach ($conn->query("SHOW COLUMNS FROM Produto") as $coluna) {
        $colunasProduto[strtoupper($coluna['Field'])] = true;
    }

    $migraProduto = [
        'PESO' => "ALTER TABLE Produto ADD COLUMN PESO DECIMAL(10,2) NOT NULL DEFAULT 0",
        'PORTE_PRODUTO' => "ALTER TABLE Produto ADD COLUMN PORTE_PRODUTO TINYINT NULL",
        'TIPO_ESPECIE' => "ALTER TABLE Produto ADD COLUMN TIPO_ESPECIE TINYINT NULL",
        'FABRICANTE' => "ALTER TABLE Produto ADD COLUMN FABRICANTE VARCHAR(100) NULL",
        'TAMANHO' => "ALTER TABLE Produto ADD COLUMN TAMANHO VARCHAR(100) NULL",
        'MATERIAL_FABRICACAO' => "ALTER TABLE Produto ADD COLUMN MATERIAL_FABRICACAO VARCHAR(150) NULL",
    ];

    foreach ($migraProduto as $campo => $sql) {
        if (!isset($colunasProduto[$campo])) {
            $conn->exec($sql);
        }
    }
} catch (PDOException $e) {
    // ignorado para manter compatibilidade com bancos já existentes
}

function verificarLogin(){
    session_start();
    if(!isset($_SESSION['usuario_id'])){
        header('Location: login.php');
        exit;
    }
}