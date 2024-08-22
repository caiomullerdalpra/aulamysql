<?php
session_start();
include './db_connection.php';
include './aula/queries.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register'])) {
        $name = $_POST['name'];
        $cpf = $_POST['cpf'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $user_id = registerUser($conn, $name, $cpf, $password);
        if ($user_id) {
            $_SESSION['user_id'] = $user_id;
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Erro ao registrar: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['login'])) {
        $cpf = $_POST['cpf'];
        $password = $_POST['password'];

        $result = getUserByCPF($conn, $cpf);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Senha incorreta.";
            }
        } else {
            $error_message = "CPF não encontrado.";
        }
    } elseif (isset($_POST['deposit'])) {
        $user_id = $_SESSION['user_id'];
        $amount = (float)$_POST['amount'];

        if ($amount > 0) {
            if (updateBalance($conn, $user_id, $amount, 'deposit')) {
                insertTransaction($conn, $user_id, 'deposit', $amount);
                $success_message = "Depósito de R$ " . number_format($amount, 2, ',', '.') . " realizado com sucesso!";
            } else {
                $error_message = "Erro ao realizar depósito: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Valor de depósito inválido!";
        }
    } elseif (isset($_POST['withdraw'])) {
        $user_id = $_SESSION['user_id'];
        $amount = (float)$_POST['amount'];

        $result = getUserBalance($conn, $user_id);
        $row = mysqli_fetch_assoc($result);

        if ($amount > 0 && $row['balance'] >= $amount) {
            if (updateBalance($conn, $user_id, $amount, 'withdraw')) {
                insertTransaction($conn, $user_id, 'withdraw', $amount);
                $success_message = "Saque de R$ " . number_format($amount, 2, ',', '.') . " realizado com sucesso!";
            } else {
                $error_message = "Erro ao realizar saque: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Saldo insuficiente ou valor inválido!";
        }
    } elseif (isset($_POST['pix'])) {
        $user_id = $_SESSION['user_id'];
        $pix_key = $_POST['pix_key'];
        $amount = (float)$_POST['amount'];

        $result = getUserBalance($conn, $user_id);
        $row = mysqli_fetch_assoc($result);

        if ($amount > 0 && $row['balance'] >= $amount) {
            $dest_result = getUserIdByCPF($conn, $pix_key);
            if (mysqli_num_rows($dest_result) > 0) {
                $dest_row = mysqli_fetch_assoc($dest_result);
                $dest_user_id = $dest_row['id'];

                if (updateBalance($conn, $user_id, $amount, 'withdraw') &&
                    updateBalance($conn, $dest_user_id, $amount, 'deposit')) {
                    insertTransaction($conn, $user_id, 'pix', $amount);
                    $success_message = "Transferência PIX de R$ " . number_format($amount, 2, ',', '.') . " realizada com sucesso!";
                } else {
                    $error_message = "Erro ao realizar transferência PIX: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Chave PIX (CPF) não encontrada!";
            }
        } else {
            $error_message = "Saldo insuficiente ou valor inválido!";
        }
    } elseif (isset($_POST['logout'])) {
        session_destroy();
        header("Location: dashboard.php");
        exit();
    }
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $result = getUserBalance($conn, $user_id);
    $row = mysqli_fetch_assoc($result);
    $balance = $row['balance'];
    ?>

    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8" />
        <link rel="stylesheet" type="text/css" href="./css/main.css" />
        <title>Nubank - Dashboard</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link rel="manifest" href="img/manifest.json" />
        <meta name="msapplication-TileImage" content="img/ms-icon-144x144.png" />
        <script src="https://kit.fontawesome.com/429b57eee3.js" crossorigin="anonymous"></script>
    </head>

    <body>
        <div class="header">
            <p class="title" style="color:white;">
                <i class="fas fa-wallet" style="color:white"></i> Nubank
            </p>
            <div class="align-left">
                <span id="nombre">Bem-vindo(a)</span>
                <i class="far fa-user-circle" style="color:white"></i>
            </div>
        </div>
        <div class="white-container">
            <div class="menu-container">
                <h1 class="tu-cuenta">Sua Conta</h1>
                <button class="links" onclick="extraerDinero()">Sacar Dinheiro</button>
                <button class="links" onclick="depositarDinheiro()">Depositar Dinheiro</button>
                <button class="links" onclick="transferirDinero()">Transferir Dinheiro</button>
            </div>
            <div class="pesos-container">
                <div class="cuenta-info">
                    <p>Saldo em sua conta</p>
                    <h3 id="saldo-cuenta">R$ <?php echo number_format($balance, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>

        <footer>
            <form method="POST">
                <button 
                class="logout"
                type="submit" name="logout">Sair</button>
                <style>
                .logout {
                    background-color: #82259f;
                    color: white;
                    padding: 10px;
                    border: none;
                    border-radius: 4px;
                    width: 100%;
                    cursor: pointer;
                    font-size: 16px;
                    margin-top: 10px;
                }
                </style>
            </form>
            <br>
            <span id="current-year"></span>
        </footer>

        <script src="./js/script.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>
    </body>

    </html>

<?php
} else {
    ?>

    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8" />
        <link rel="stylesheet" type="text/css" href="css/main.css" />
        <title>Nubank - Login/Registro</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-color: #f5f5f5;
                font-family: 'Source Sans Pro', sans-serif;
            }

            .container {
                background: #ffffff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.2);
                width: 300px;
                text-align: center;
            }

            .container h1 {
                color: #82259f;
                margin-bottom: 20px;
            }

            .container h2 {
                margin: 10px 0;
                color: #333;
            }

            .container form {
                margin-bottom: 15px;
            }

            .container input[type="text"],
            .container input[type="password"] {
                width: 100%;
                padding: 10px;
                margin: 5px 0;
                border: 1px solid #ccc;
                border-radius: 4px;
                box-sizing: border-box;
            }

            .container button {
                background-color: #82259f;
                color: white;
                padding: 10px;
                border: none;
                border-radius: 4px;
                width: 100%;
                cursor: pointer;
                font-size: 16px;
                margin-top: 10px;
            }

            .container button:hover {
                background-color: #6d1b80;
            }

            .error-message {
                color: red;
                margin-bottom: 10px;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <h1>Nubank</h1>

            <?php if (isset($error_message)) { echo "<div class='error-message'>$error_message</div>"; } ?>

            <h2>Login</h2>
            <form method="POST">
                <input type="text" id="cpf" name="cpf" placeholder="CPF" required />
                <input type="password" id="password" name="password" placeholder="Senha" required />
                <button type="submit" name="login">Entrar</button>
            </form>

            <h2>Registrar</h2>
            <form method="POST">
                <input type="text" id="name" name="name" placeholder="Nome" required />
                <input type="text" id="cpf" name="cpf" placeholder="CPF" required />
                <input type="password" id="password" name="password" placeholder="Senha" required />
                <button type="submit" name="register">Registrar</button>
            </form>
        </div>
    </body>

    </html>

<?php
}
mysqli_close($conn);
?>