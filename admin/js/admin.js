// Admin JS - Login y utilidades generales

// Login
if (document.getElementById('login-form')) {
    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const username = e.target.username.value;
        const password = e.target.password.value;
        const errorDiv = document.getElementById('error-message');

        errorDiv.textContent = '';

        try {
            const response = await fetch('../api/auth/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = 'dashboard.php';
            } else {
                errorDiv.textContent = data.error || 'Error al iniciar sesión';
            }
        } catch (error) {
            console.error('Error:', error);
            errorDiv.textContent = 'Error de conexión';
        }
    });
}

// Validar sesión en todas las páginas admin (excepto login)
async function validateSession() {
    if (window.location.pathname.includes('index.php')) {
        return;
    }

    try {
        const response = await fetch('../api/auth/validate-session.php');
        const data = await response.json();

        if (!data.valid) {
            window.location.href = 'index.php';
        }
    } catch (error) {
        console.error('Error validando sesión:', error);
    }
}

// Ejecutar validación al cargar
if (!window.location.pathname.includes('index.php')) {
    validateSession();
}

// Utilidades
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-CL', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
        color: white;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Animaciones CSS inline
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Change Password
if (document.getElementById('change-password-form')) {
    document.getElementById('change-password-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const currentPassword = e.target['current-password'].value;
        const newPassword = e.target['new-password'].value;
        const confirmPassword = e.target['confirm-password'].value;
        const messageDiv = document.getElementById('password-message');

        messageDiv.textContent = '';
        messageDiv.className = 'message';

        if (newPassword !== confirmPassword) {
            messageDiv.textContent = 'Las nuevas contraseñas no coinciden.';
            messageDiv.classList.add('error');
            return;
        }

        try {
            const response = await fetch('../api/auth/update-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ currentPassword, newPassword, confirmPassword })
            });

            const data = await response.json();

            if (data.success) {
                messageDiv.textContent = data.message + ' Serás redirigido.';
                messageDiv.classList.add('success');
                e.target.reset();
                setTimeout(() => {
                    window.location.href = 'logout.php';
                }, 2000);
            } else {
                messageDiv.textContent = data.message || 'Error al cambiar la contraseña.';
                messageDiv.classList.add('error');
            }
        } catch (error) {
            console.error('Error:', error);
            messageDiv.textContent = 'Error de conexión.';
            messageDiv.classList.add('error');
        }
    });
}

// Change Email
if (document.getElementById('change-email-form')) {
    document.getElementById('change-email-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = e.target.email.value;
        const password = e.target.password.value;
        const messageDiv = document.getElementById('email-message');

        messageDiv.textContent = '';
        messageDiv.className = 'message';

        try {
            const response = await fetch('../api/auth/update-email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (data.success) {
                messageDiv.textContent = data.message;
                messageDiv.classList.add('success');
                e.target.password.value = '';
                 // also update the email in the sidebar
                const emailInSidebar = document.querySelector('.user-info div');
                if(emailInSidebar) emailInSidebar.textContent = email;

            } else {
                messageDiv.textContent = data.message || 'Error al cambiar el correo.';
                messageDiv.classList.add('error');
            }
        } catch (error) {
            console.error('Error:', error);
            messageDiv.textContent = 'Error de conexión.';
            messageDiv.classList.add('error');
        }
    });
}
