# Реализация системы входа с логином и паролем

Я помогу вам реализовать систему аутентификации для вашей формы. Вот пошаговый план:

## Шаг 1: Модификация структуры базы данных

Добавим таблицу для хранения учетных данных пользователей:

```sql
CREATE TABLE users (
    id INT unsigned AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    login VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id)
);
```

## Шаг 2: Обновление index.php

```php
<?php
header('Content-Type: text/html; charset=UTF-8');

// Установка времени жизни сессии (1 час)
session_set_cookie_params(3600);
session_start();

// Очистка сообщений об ошибках после их отображения
if (!empty($_SESSION['formErrors'])) {
    unset($_SESSION['formErrors']);
}
if (!empty($_SESSION['fieldErrors'])) {
    unset($_SESSION['fieldErrors']);
}

// Получение старых значений из куки (если они есть)
$oldValues = [];
if (!empty($_COOKIE['form_data'])) {
    $oldValues = json_decode($_COOKIE['form_data'], true);
}

// Получение ошибок из сессии
$formErrors = $_SESSION['formErrors'] ?? [];
$fieldErrors = $_SESSION['fieldErrors'] ?? [];

// Если пользователь авторизован, загружаем его данные
if (!empty($_SESSION['login'])) {
    try {
        $user = 'u70422';
        $pass = '4545635';
        $dbname = 'u70422';
        $db = new PDO("mysql:host=localhost;dbname=$dbname", $user, $pass);
        
        // Получаем данные пользователя
        $stmt = $db->prepare("SELECT a.* FROM applications a JOIN users u ON a.id = u.application_id WHERE u.login = ?");
        $stmt->execute([$_SESSION['login']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            // Получаем языки программирования
            $stmt = $db->prepare("SELECT pl.name FROM application_languages al 
                                 JOIN programming_languages pl ON al.language_id = pl.id 
                                 WHERE al.application_id = ?");
            $stmt->execute([$userData['id']]);
            $languages = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Заполняем oldValues данными из БД
            $oldValues = [
                'fio' => $userData['fio'],
                'tel' => $userData['tel'],
                'email' => $userData['email'],
                'date' => $userData['birth_date'],
                'gender' => $userData['gender'],
                'bio' => $userData['bio'],
                'plang' => $languages
            ];
        }
    } catch (PDOException $e) {
        $formErrors[] = 'Ошибка при загрузке данных: ' . $e->getMessage();
    }
}

// Обработка GET-запроса (отображение формы)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_GET['save'])) {
        print('Спасибо, результаты сохранены!');
        
        // Если есть логин и пароль в куках, показываем их
        if (!empty($_COOKIE['login']) && !empty($_COOKIE['pass'])) {
            printf('<div class="alert alert-info">Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.</div>',
                htmlspecialchars($_COOKIE['login']),
                htmlspecialchars($_COOKIE['pass']));
        }
    }
    
    // Если пользователь авторизован, показываем кнопку выхода
    if (!empty($_SESSION['login'])) {
        echo '<div class="text-right"><a href="logout.php" class="btn btn-secondary">Выйти</a></div>';
    }
    
    include('form.php');
    exit();
}

// Обработка POST-запроса (отправка формы)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... существующий код валидации ...

    // Если ошибок нет, сохраняем данные в БД
    $user = 'u70422';
    $pass = '4545635';
    $dbname = 'u70422';
    
    try {
        $db = new PDO("mysql:host=localhost;dbname=$dbname", $user, $pass, [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $db->beginTransaction();
        
        if (!empty($_SESSION['login'])) {
            // Обновление существующей записи
            $stmt = $db->prepare("UPDATE applications SET fio=?, tel=?, email=?, birth_date=?, gender=?, bio=? WHERE id = 
                                (SELECT application_id FROM users WHERE login = ?)");
            $stmt->execute([
                $_POST['fio'],
                $_POST['tel'],
                $_POST['email'],
                $_POST['date'],
                $_POST['gender'],
                $_POST['bio'],
                $_SESSION['login']
            ]);
            
            // Удаляем старые языки
            $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = 
                                (SELECT application_id FROM users WHERE login = ?)");
            $stmt->execute([$_SESSION['login']]);
            
            $applicationId = $db->query("SELECT application_id FROM users WHERE login = '{$_SESSION['login']}'")->fetchColumn();
        } else {
            // Вставка новых данных
            $stmt = $db->prepare("INSERT INTO applications (fio, tel, email, birth_date, gender, bio) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['fio'],
                $_POST['tel'],
                $_POST['email'],
                $_POST['date'],
                $_POST['gender'],
                $_POST['bio']
            ]);
            
            $applicationId = $db->lastInsertId();
            
            // Генерация логина и пароля
            $login = uniqid('user_');
            $password = bin2hex(random_bytes(4)); // Генерируем случайный пароль
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Сохраняем учетные данные
            $stmt = $db->prepare("INSERT INTO users (application_id, login, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$applicationId, $login, $passwordHash]);
            
            // Сохраняем логин и пароль в куки на 5 минут
            setcookie('login', $login, time() + 300, '/');
            setcookie('pass', $password, time() + 300, '/');
        }
        
        // Вставка языков программирования
        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($_POST['plang'] as $language) {
            $langStmt = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $langStmt->execute([$language]);
            $langId = $langStmt->fetchColumn();
            
            if (!$langId) {
                $langStmt = $db->prepare("INSERT INTO programming_languages (name) VALUES (?)");
                $langStmt->execute([$language]);
                $langId = $db->lastInsertId();
            }
            
            $stmt->execute([$applicationId, $langId]);
        }
        
        $db->commit();
        
        // Сохраняем данные в куки на 1 год
        $formData = [
            'fio' => $_POST['fio'],
            'tel' => $_POST['tel'],
            'email' => $_POST['email'],
            'date' => $_POST['date'],
            'gender' => $_POST['gender'],
            'bio' => $_POST['bio'],
            'plang' => $_POST['plang']
        ];
        
        setcookie('form_data', json_encode($formData), time() + 3600 * 24 * 365, '/');
        
        // Перенаправляем с флагом успешного сохранения
        header('Location: ?save=1');
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['formErrors'] = ['Ошибка при сохранении данных: ' . $e->getMessage()];
        $_SESSION['oldValues'] = $_POST;
        
        header('Location: index.php');
        exit();
    }
}
?>
```

## Шаг 3: Создание login.php

```php
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
        $user = 'u70422';
        $pass_db = '4545635';
        $dbname = 'u70422';
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
```

## Шаг 4: Создание logout.php

```php
<?php
session_start();

// Удаляем все данные сессии
$_SESSION = array();

// Если требуется уничтожить сессию, также удаляем сессионные cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожаем сессию
session_destroy();

// Перенаправляем на главную страницу
header('Location: index.php');
exit();
?>
```

## Шаг 5: Модификация form.php

Добавим в начало формы проверку авторизации:

```php
<?php
// Если пользователь авторизован, показываем сообщение
if (!empty($_SESSION['login'])) {
    echo '<div class="alert alert-info">Вы вошли как ' . htmlspecialchars($_SESSION['login']) . 
         '. Теперь вы можете редактировать свои данные.</div>';
}
?>
```

## Шаг 6: Обновление базы данных

Не забудьте выполнить SQL-запрос для создания таблицы users:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    login VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id)
);
```

## Как это работает:

1. **Первичная отправка формы**:
   - При первом сохранении формы генерируются случайные логин и пароль
   - Пароль хешируется и сохраняется в базе данных
   - Логин и пароль показываются пользователю (хранятся в куках 5 минут)

2. **Вход в систему**:
   - Пользователь вводит полученные логин и пароль на странице login.php
   - Система проверяет соответствие хеша пароля
   - При успешной аутентификации создается сессия

3. **Редактирование данных**:
   - Авторизованный пользователь видит свои текущие данные в форме
   - При сохранении обновляются существующие записи в базе данных

4. **Выход из системы**:
   - При нажатии на кнопку "Выйть" сессия уничтожается

## Безопасность:

1. Пароли хранятся в виде хешей (функция password_hash)
2. Используются подготовленные выражения для защиты от SQL-инъекций
3. Сессионные идентификаторы защищены
4. Пароль генерируется случайным образом
5. Чувствительные данные экранируются при выводе

Эта реализация соответствует требованиям задания и обеспечивает безопасную аутентификацию пользователей с возможностью редактирования ранее сохраненных данных.# web55
