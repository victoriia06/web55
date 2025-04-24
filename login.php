<?php
header('Content-Type: text/html; charset=UTF-8');

session_start();

// Если пользователь уже авторизован, перенаправляем на главную
if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$login = $_POST['login'] ?? '';
$pass = $_POST['pass'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $user = 'uXXXXX';
        $pass_db = 'YYYYYYY';
        $dbname = 'uXXXXX';
        $db = new PDO("mysql:host=localhost;dbname=$dbname", $user, $pass_db);
        
        // Ищем пользователя
        $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($pass, $user['password_hash'])) {
            // Авторизация успешна
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            
            header('Location: index.php');
            exit();
        } else {
            $error = 'Неверный логин или пароль';
        }
    } catch (PDOException $e) {
        $error = 'Ошибка при авторизации: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .login-container h2 {
            margin-top: 0;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .error {
            color: #dc3545;
            margin-bottom: 15px;
            text-align: center;
        }
        
        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Вход</h2>
        
        <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($login); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="pass">Пароль:</label>
                <input type="password" id="pass" name="pass" required>
            </div>
            
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html>
