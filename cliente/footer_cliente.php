    </main>

    <footer class="client-footer">
        <p><i class="fas fa-utensils"></i> <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></p>
        <p>Sistema de Gestion para Restaurantes</p>
        <p style="margin-top: 1rem;">
            <a href="acerca.php" style="color: var(--color-primary); margin: 0 10px;"><i class="fas fa-info-circle"></i> Acerca de</a>
            <span style="color: rgba(255,255,255,0.3);">|</span>
            <a href="../login.php" style="color: rgba(255,255,255,0.6); margin: 0 10px;">Panel de Administracion</a>
        </p>

    </footer>

    <script>
    // Auto-cerrar alertas
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(-20px)';
            setTimeout(function() { alert.remove(); }, 300);
        });
    }, 5000);
    </script>
</body>
</html>