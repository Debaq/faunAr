<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="page-header">
    <h1>Copia de Seguridad</h1>
</div>

<div class="form-section">
    <h2>Descargar Copia de Seguridad de Modelos</h2>
    <p style="margin-bottom: 20px; color: #7f8c8d;">
        Haz clic en el botón de abajo para generar y descargar un archivo ZIP con todo el contenido de la carpeta <code>/models</code>.
        <br>
        Este proceso puede tardar varios minutos dependiendo del tamaño de la carpeta. Por favor, no cierres esta ventana hasta que la descarga comience.
    </p>
    
    <div class="form-actions">
        <a href="../api/backup/create.php" id="download-backup-btn" class="btn btn-primary">📦 Generar y Descargar Copia</a>
    </div>

    <div id="loading-message" style="display: none; margin-top: 20px;">
        <p>
            <strong>Generando archivo...</strong> Por favor espera.
        </p>
    </div>
</div>

<script>
    document.getElementById('download-backup-btn').addEventListener('click', () => {
        const btn = document.getElementById('download-backup-btn');
        const loadingMessage = document.getElementById('loading-message');

        btn.classList.add('btn-secondary');
        btn.classList.remove('btn-primary');
        btn.innerHTML = '⏳ Generando...';
        loadingMessage.style.display = 'block';

        // Opcional: Revertir el estado del botón después de un tiempo, 
        // ya que no podemos saber con certeza cuándo finaliza la descarga.
        setTimeout(() => {
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-primary');
            btn.innerHTML = '📦 Generar y Descargar Copia';
            loadingMessage.style.display = 'none';
        }, 60000); // Revertir después de 1 minuto
    });
</script>

<?php include 'includes/footer.php'; ?>
