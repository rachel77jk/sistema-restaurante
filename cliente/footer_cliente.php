</main>

    <footer class="client-footer">
        <div style="max-width: 800px; margin: 0 auto;">
            <!-- Logo y descripcion -->
            <p style="font-size: 1.2rem; margin-bottom: 0.5rem;">
                <i class="fas fa-utensils" style="color: var(--color-primary);"></i> 
                <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
            </p>
            <p style="opacity: 0.7; margin-bottom: 1.5rem;">Consulta nuestro menu digital</p>
            
            <!-- Redes Sociales -->
            <div style="margin: 1.5rem 0;">
                <p style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 0.8rem;">
                    <i class="fas fa-share-alt"></i> Siguenos
                </p>
                <div style="display: flex; justify-content: center; gap: 1.5rem;">
                    <a href="https://facebook.com" target="_blank" style="color: rgba(255,255,255,0.8); font-size: 1.5rem; transition: var(--transition);" 
                       onmouseover="this.style.color='#1877f2'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="https://instagram.com" target="_blank" style="color: rgba(255,255,255,0.8); font-size: 1.5rem; transition: var(--transition);" 
                       onmouseover="this.style.color='#e4405f'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://wa.me/528341234567" target="_blank" style="color: rgba(255,255,255,0.8); font-size: 1.5rem; transition: var(--transition);" 
                       onmouseover="this.style.color='#25d366'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="https://tiktok.com" target="_blank" style="color: rgba(255,255,255,0.8); font-size: 1.5rem; transition: var(--transition);" 
                       onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
                        <i class="fab fa-tiktok"></i>
                    </a>
                </div>
            </div>
            
            <!-- Divider -->
            <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 1.5rem 0;"></div>
            
            <!-- Contacto -->
            <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 2rem; margin-bottom: 1.5rem; font-size: 0.9rem;">
                <span><i class="fas fa-map-marker-alt" style="color: var(--color-primary);"></i> Ciudad Victoria, Tamaulipas</span>
                <span><i class="fas fa-phone" style="color: var(--color-primary);"></i> (834) 123 4567</span>
                <span><i class="fas fa-envelope" style="color: var(--color-primary);"></i> contacto@restaurante.com</span>
            </div>
            
            <!-- Links -->
            <p style="margin-top: 1rem;">
                <a href="acerca.php" style="color: var(--color-primary); margin: 0 10px;"><i class="fas fa-info-circle"></i> Acerca de</a>
                <span style="color: rgba(255,255,255,0.3);">|</span>
                <a href="../login.php" style="color: rgba(255,255,255,0.6); margin: 0 10px;">Panel de Administracion</a>
            </p>
            
            <!-- Copyright -->
            <p style="margin-top: 1.5rem; font-size: 0.8rem; opacity: 0.5;">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos los derechos reservados.
            </p>
        </div>
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