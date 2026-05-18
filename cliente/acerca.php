<?php
/**
 * ACERCA DE - Restaurante Inteligente v5
 * Pagina informativa sobre el sistema
 */
require_once '../includes/config.php';

$pageTitle = 'Acerca de';
require_once 'header_cliente.php';
?>

<style>
.about-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 3rem 2rem;
}
.about-hero {
    text-align: center;
    padding: 3rem 2rem;
    background: linear-gradient(135deg, var(--color-dark), #16213e);
    color: white;
    border-radius: var(--radius-lg);
    margin-bottom: 3rem;
}
.about-hero i {
    font-size: 4rem;
    color: var(--color-primary);
    margin-bottom: 1rem;
}
.about-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}
.about-hero p {
    opacity: 0.8;
    font-size: 1.1rem;
}
.about-section {
    margin-bottom: 2.5rem;
}
.about-section h2 {
    color: var(--color-secondary);
    font-size: 1.4rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.about-section h2 i {
    color: var(--color-primary);
}
.about-section p {
    color: var(--color-gray);
    line-height: 1.8;
    margin-bottom: 1rem;
}
.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}
.feature-item {
    background: var(--color-white);
    padding: 1.5rem;
    border-radius: var(--radius-md);
    text-align: center;
    border: 1px solid var(--color-border);
    transition: var(--transition);
}
.feature-item:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}
.feature-item i {
    font-size: 2rem;
    color: var(--color-primary);
    margin-bottom: 0.8rem;
}
.feature-item h3 {
    font-size: 1rem;
    color: var(--color-secondary);
    margin-bottom: 0.5rem;
}
.feature-item p {
    font-size: 0.85rem;
    margin: 0;
}
.tech-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 1rem;
}
.tech-badge {
    background: var(--color-gray-light);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--color-secondary);
}
.version-box {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    color: white;
    padding: 2rem;
    border-radius: var(--radius-md);
    text-align: center;
    margin-top: 2rem;
}
.version-box h3 {
    margin-bottom: 0.5rem;
}
.version-box .version-number {
    font-size: 2.5rem;
    font-weight: 700;
}

/* Logos Universidad */
.university-section {
    background: var(--color-white);
    border-radius: var(--radius-md);
    padding: 2rem;
    border: 1px solid var(--color-border);
    margin-bottom: 2.5rem;
    text-align: center;
}
.university-section h2 {
    color: var(--color-secondary);
    font-size: 1.3rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
.university-section h2 i {
    color: var(--color-primary);
}
.university-logos {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 3rem;
    flex-wrap: wrap;
    margin-top: 1.5rem;
}
.university-logo {
    max-width: 200px;
    height: auto;
    transition: var(--transition);
    filter: grayscale(20%);
}
.university-logo:hover {
    transform: scale(1.05);
    filter: grayscale(0%);
}
.university-logo img {
    max-width: 100%;
    height: auto;
}
.university-name {
    margin-top: 0.8rem;
    font-size: 0.85rem;
    color: var(--color-gray);
    font-weight: 600;
}
</style>

<div class="about-container">
    <div class="about-hero animate-fadeInUp">
        <i class="fas fa-utensils"></i>
        <h1><?php echo APP_NAME; ?></h1>
        <p>Sistema de Gestion para Restaurantes Modernos</p>
    </div>

    <div class="about-section animate-fadeInUp">
        <h2><i class="fas fa-info-circle"></i> Sobre el Sistema</h2>
        <p>
            <?php echo APP_NAME; ?> es una plataforma completa disenada para optimizar la operacion 
            de restaurantes de cualquier tamano. Desde la gestion de pedidos y mesas hasta el control 
            de inventario y reportes de ventas, nuestro sistema centraliza todas las operaciones en 
            una interfaz intuitiva y moderna.
        </p>
        <p>
            La diseñadora de este sistema es Raquel Castro Villanueva con la ayuda de KIMI IA. Esta 
            pagina WEB se realizó durante la clase de Programacion WEB en el periodo 2026-1 en la FIC.
        </p>
    </div>

    <!-- Seccion Universidad -->
    <div class="university-section animate-fadeInUp">
        <h2><i class="fas fa-university"></i> Institucion Academica</h2>
        <p style="color: var(--color-gray); margin-bottom: 1rem;">
            Este proyecto fue desarrollado en el marco academico de la Universidad Autonoma de Tamaulipas, 
            como parte de la formacion en la Facultad de Ingenieria y Ciencias.
        </p>
        <div class="university-logos">
            <div style="text-align: center;">
                <div class="university-logo">
                    <img src="../uploads/logo_uat.jpeg" alt="Universidad Autonoma de Tamaulipas" onerror="this.style.display='none'">
                </div>
                <div class="university-name">Universidad Autonoma de Tamaulipas</div>
            </div>
            <div style="text-align: center;">
                <div class="university-logo">
                    <img src="../uploads/logo_fic.jpeg" alt="Facultad de Ingenieria y Ciencias" onerror="this.style.display='none'">
                </div>
                <div class="university-name">Facultad de Ingenieria y Ciencias</div>
            </div>
        </div>
    </div>

    <div class="about-section animate-fadeInUp">
        <h2><i class="fas fa-star"></i> Caracteristicas Principales</h2>
        <div class="feature-grid">
            <div class="feature-item">
                <i class="fas fa-clipboard-list"></i>
                <h3>Gestion de Pedidos</h3>
                <p>Control completo del ciclo de vida de cada pedido</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-chair"></i>
                <h3>Control de Mesas</h3>
                <p>Visualizacion en tiempo real del estado de mesas</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-hamburger"></i>
                <h3>Menu Digital</h3>
                <p>Catalogo de productos con categorias e imagenes</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-chart-bar"></i>
                <h3>Reportes</h3>
                <p>Estadisticas y analisis de ventas detallados</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-calendar-check"></i>
                <h3>Reservaciones</h3>
                <p>Sistema de reservas de mesas integrado</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-user-shield"></i>
                <h3>Roles y Permisos</h3>
                <p>Administracion flexible de accesos por usuario</p>
            </div>
        </div>
    </div>

    <div class="about-section animate-fadeInUp">
        <h2><i class="fas fa-code"></i> Tecnologias Utilizadas</h2>
        <p>El sistema esta construido con tecnologias web modernas y robustas:</p>
        <div class="tech-list">
            <span class="tech-badge"><i class="fab fa-php"></i> PHP 7.4+</span>
            <span class="tech-badge"><i class="fas fa-database"></i> MySQL / MariaDB</span>
            <span class="tech-badge"><i class="fab fa-html5"></i> HTML5</span>
            <span class="tech-badge"><i class="fab fa-css3-alt"></i> CSS3</span>
            <span class="tech-badge"><i class="fab fa-js"></i> JavaScript</span>
            <span class="tech-badge"><i class="fas fa-shield-alt"></i> bcrypt</span>
            <span class="tech-badge"><i class="fas fa-mobile-alt"></i> Responsive</span>
        </div>
    </div>

    <div class="about-section animate-fadeInUp">
        <h2><i class="fas fa-users"></i> Equipo y Desarrollo</h2>
        <p>
            Este proyecto fue desarrollado como una solucion integral para la gestion de restaurantes, 
            enfocandose en la usabilidad, rendimiento y escalabilidad. El codigo es modular y facilmente 
            extensible para adaptarse a las necesidades especificas de cada negocio.
        </p>
    </div>

    <div class="version-box animate-fadeInUp">
        <h3><i class="fas fa-code-branch"></i> Version Actual</h3>
        <div class="version-number"><?php echo APP_VERSION; ?></div>
        <p>Sistema de Roles y Permisos incluido</p>
    </div>

    <div style="text-align: center; margin-top: 3rem; color: var(--color-gray);">
        <p><i class="fas fa-heart" style="color: var(--color-danger);"></i> Hecho con dedicacion para la industria gastronomica</p>
        <p style="margin-top: 0.5rem; font-size: 0.9rem;">
            &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos los derechos reservados.
        </p>
    </div>
</div>

<?php require_once 'footer_cliente.php'; ?>