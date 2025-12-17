<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaunAR - Admin Login</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <img src="../assets/images/logo_faunar.png" alt="FaunAR" onerror="this.style.display='none'">
        <h1>Panel de Administración</h1>
        <form id="login-form">
            <input type="text" name="username" placeholder="Usuario" required autofocus>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Iniciar Sesión</button>
            <div id="error-message"></div>
        </form>
        <div style="text-align: center; margin-top: 20px; color: #7f8c8d; font-size: 12px;">
            <p>Usuario por defecto: <strong>admin</strong></p>
            <p>Contraseña: <strong>password</strong></p>
        </div>
    </div>
    <script src="js/admin.js"></script>
</body>
</html>
