        </div>
    </main>

    <script>
    // Toggle sidebar en movil
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });

    // Auto-cerrar alertas
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(-20px)';
            setTimeout(function() {
                alert.remove();
            }, 300);
        });
    }, 5000);
    </script>
</body>
</html>
