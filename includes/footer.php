<?php if (!isset($conn)) include 'config.php'; ?>
<footer class="footer">
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> Employee Management System. All rights reserved.</p>
        <div class="footer-links">
            <a href="#" >About</a> |
            <a href="#" >Contact</a> |
            <a href="#" >Privacy Policy</a>
        </div>
    </div>
</footer>

<style>
/* Sticky footer wrapper */
body, html {
    min-height: 100%;
    display: flex;
    flex-direction: column;
}

.main-content {
    flex: 1 0 auto; /* Makes main content expand to fill available space */
}

.footer {
    background-color: var(--white);
    color: var(--secondary-color);
    text-align: center;
    padding: 15px 20px;
    box-shadow: 0 -2px 6px rgba(0,0,0,0.1);
    font-size: 14px;
    flex-shrink: 0; /* Prevent footer from shrinking */
}

.footer a {
    color: var(--primary-color);
    text-decoration: none;
    margin: 0 5px;
    transition: color 0.3s ease;
}

.footer a:hover {
    color: var(--primary-dark);
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .footer-content {
        font-size: 13px;
    }

    .footer-links {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
}
</style>
