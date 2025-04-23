<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма регистрации</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f7f7f7;
            overflow: auto;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            overflow: auto;
            max-height: 90vh;
        }

        .form-container h2 {
            margin: 0 0 15px;
        }

        .form-row {
            margin-bottom: 20px;
        }

        .form-container input,
        .form-container textarea,
        .form-container select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-container button {
            width: 100%;
            padding: 10px;
            background: #007BFF;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
        }

        .form-container button:hover {
            background: #0056b3;
        }

        .gender-container {
            display: flex;
            margin-top: 5px;
        }

        .form-check {
            margin-right: 20px;
            display: flex;
            align-items: center;
        }

        .form-group {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        .form-group label {
            margin-left: 5px;
            margin-bottom: 0;
        }

        .form-check-input {
            margin-right: 10px;
        }

        .error {
            color: red;
            font-size: 0.8em;
            margin-top: 5px;
        }
        
        .error-field {
            border-color: red !important;
        }
        
        .form-messages {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #f5c6cb;
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 4px;
        }
        
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .text-right {
            text-align: right;
            margin-bottom: 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 0;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 4px;
            text-decoration: none;
        }
        
        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Регистрация</h2>
        
        <?php if (!empty($_SESSION['login'])): ?>
            <div class="alert alert-info">Вы вошли как <?php echo htmlspecialchars($_SESSION['login']); ?>. Теперь вы можете редактировать свои данные.</div>
            <div class="text-right"><a href="logout.php" class="btn btn-secondary">Выйти</a></div>
        <?php endif; ?>
        
        <?php if (!empty($formErrors)): ?>
        <div class="form-messages">
            <p>Пожалуйста, исправьте следующие ошибки:</p>
            <ul>
                <?php foreach ($formErrors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form id="registrationForm" action="index.php" method="POST">
            <div class="form-row">
                <label for="fio">ФИО:</label>
                <input type="text" name="fio" class="form-control <?php echo !empty($fieldErrors['fio']) ? 'error-field' : ''; ?>" 
                       id="fio" placeholder="Иванов Иван Иванович" required
                       value="<?php echo !empty($oldValues['fio']) ? htmlspecialchars($oldValues['fio']) : ''; ?>">
                <?php if (!empty($fieldErrors['fio'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['fio']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <label for="tel">Номер телефона:</label>
                <input type="tel" name="tel" class="form-control <?php echo !empty($fieldErrors['tel']) ? 'error-field' : ''; ?>" 
                       id="tel" placeholder="Введите ваш номер" required
                       value="<?php echo !empty($oldValues['tel']) ? htmlspecialchars($oldValues['tel']) : ''; ?>">
                <?php if (!empty($fieldErrors['tel'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['tel']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <label for="email">Email:</label>
                <input type="email" name="email" class="form-control <?php echo !empty($fieldErrors['email']) ? 'error-field' : ''; ?>" 
                       id="email" placeholder="Введите вашу почту" required
                       value="<?php echo !empty($oldValues['email']) ? htmlspecialchars($oldValues['email']) : ''; ?>">
                <?php if (!empty($fieldErrors['email'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['email']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <label for="date">Дата рождения:</label>
                <input type="date" name="date" class="form-control <?php echo !empty($fieldErrors['date']) ? 'error-field' : ''; ?>" 
                       id="date" required
                       value="<?php echo !empty($oldValues['date']) ? htmlspecialchars($oldValues['date']) : ''; ?>">
                <?php if (!empty($fieldErrors['date'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['date']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <label>Пол:</label>
                <div class="gender-container">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="radio-male" value="male" required
                            <?php echo (!empty($oldValues['gender']) && $oldValues['gender'] == 'male') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="radio-male">Мужской</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="radio-female" value="female" required
                            <?php echo (!empty($oldValues['gender']) && $oldValues['gender'] == 'female') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="radio-female">Женский</label>
                    </div>
                </div>
                <?php if (!empty($fieldErrors['gender'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['gender']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <label for="plang">Любимый язык программирования:</label>
                <select class="form-control <?php echo !empty($fieldErrors['plang']) ? 'error-field' : ''; ?>" 
                        name="plang[]" id="plang" multiple required>
                    <?php
                    $languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Haskell', 'Clojure', 'Prolog', 'Scala'];
                    foreach ($languages as $lang) {
                        $selected = (!empty($oldValues['plang']) && in_array($lang, $oldValues['plang'])) ? 'selected' : '';
                        echo "<option value=\"$lang\" $selected>$lang</option>";
                    }
                    ?>
                </select>
                <?php if (!empty($fieldErrors['plang'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['plang']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <label for="bio">Биография:</label>
                <textarea class="form-control <?php echo !empty($fieldErrors['bio']) ? 'error-field' : ''; ?>" 
                          name="bio" id="bio" rows="3" placeholder="Расскажите о себе" required><?php 
                    echo !empty($oldValues['bio']) ? htmlspecialchars($oldValues['bio']) : ''; 
                ?></textarea>
                <?php if (!empty($fieldErrors['bio'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['bio']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row form-group">
                <input type="checkbox" class="form-check-input" name="check" id="check" required
                    <?php echo !empty($oldValues) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="check">С контрактом ознакомлен(а)</label>
                <?php if (!empty($fieldErrors['check'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['check']); ?></div>
                <?php endif; ?>
            </div>
            
            <button type="submit">Сохранить</button>
        </form>
    </div>
</body>
</html>
