<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="page-header">
    <h1>Mi Cuenta</h1>
</div>

<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="page-header">
    <h1>Mi Cuenta</h1>
</div>

<div class="form-section">
    <h2>Cambiar Correo Electrónico</h2>
    <form id="change-email-form">
        <div class="form-group">
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_SESSION['admin_user']['email']) ?>" required>
        </div>
        <div class="form-group">
            <label for="email-confirm-password">Contraseña Actual para Confirmar</label>
            <input type="password" id="email-confirm-password" name="password" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar Correo</button>
        </div>
    </form>
    <div id="email-message" class="message"></div>
</div>

<div class="form-section">
    <h2>Cambiar Contraseña</h2>
    <form id="change-password-form">
        <div class="form-group">
            <label for="current-password">Contraseña Actual</label>
            <input type="password" id="current-password" name="current-password" required>
        </div>
        <div class="form-group">
            <label for="new-password">Nueva Contraseña</label>
            <input type="password" id="new-password" name="new-password" required>
        </div>
        <div class="form-group">
            <label for="confirm-password">Confirmar Nueva Contraseña</label>
            <input type="password" id="confirm-password" name="confirm-password" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
        </div>
    </form>
    <div id="password-message" class="message"></div>
</div>

<?php include 'includes/footer.php'; ?>
