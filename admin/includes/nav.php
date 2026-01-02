        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo_faunar.png" alt="FaunAR" onerror="this.style.display='none'">
                <h2>Admin Panel</h2>
            </div>

            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                        📊 Dashboard
                    </a>
                </li>
                <li>
                    <a href="animals.php" class="<?= $currentPage === 'animals' ? 'active' : '' ?>">
                        🦁 Animales/Figuras
                    </a>
                </li>
                <li>
                    <a href="variants.php" class="<?= $currentPage === 'variants' ? 'active' : '' ?>">
                        🎨 Variantes
                    </a>
                </li>
                <li>
                    <a href="translations.php" class="<?= $currentPage === 'translations' ? 'active' : '' ?>">
                        🌐 Traducciones
                    </a>
                </li>
                <li>
                    <a href="locations.php" class="<?= $currentPage === 'locations' ? 'active' : '' ?>">
                        🗺️ Lugares/Sectores
                    </a>
                </li>
                <li>
                    <a href="instances.php" class="<?= $currentPage === 'instances' ? 'active' : '' ?>">
                        📦 Instancias
                    </a>
                </li>
                <li>
                    <a href="qr-generator.php" class="<?= $currentPage === 'qr-generator' ? 'active' : '' ?>">
                        📱 Códigos QR
                    </a>
                </li>

                <li>
                    <a href="roadmap.php" class="<?= $currentPage === 'roadmap' ? 'active' : '' ?>">
                        🗺️ Hoja de Ruta
                    </a>
                </li>
                <li>
                    <a href="backup.php" class="<?= $currentPage === 'backup' ? 'active' : '' ?>">
                        📦 Copia de Seguridad
                    </a>
                </li>
            </ul>

            <div style="padding: 0 20px 15px;">
                <a href="../" target="_blank" class="btn btn-secondary" style="width: 100%; text-align: center;">
                    🌐 Ir al Portal
                </a>
            </div>

            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?= htmlspecialchars($_SESSION['admin_user']['username']) ?></strong>
                    <div style="font-size: 12px; color: rgba(255,255,255,0.6);">
                        <?= htmlspecialchars($_SESSION['admin_user']['email']) ?>
                    </div>
                </div>
                <div class="sidebar-footer-actions">
                    <a href="settings.php" class="btn btn-icon-only <?= $currentPage === 'settings' ? 'active' : '' ?>" title="Configuración">🔧</a>
                    <a href="account.php" class="btn btn-icon-only <?= $currentPage === 'account' ? 'active' : '' ?>" title="Mi Cuenta">⚙️</a>
                    <a href="logout.php" class="btn btn-logout">🚪 Cerrar Sesión</a>
                </div>
            </div>
        </nav>

        <main class="admin-main">
