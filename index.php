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
