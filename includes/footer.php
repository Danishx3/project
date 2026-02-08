    </main>
    
    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-briefcase me-2"></i><?php echo APP_NAME; ?></h5>
                    <p class="text-muted">Find your perfect part-time job opportunity. Connect with top employers and build your career.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo APP_URL; ?>" class="text-muted">Home</a></li>
                        <li><a href="<?php echo APP_URL; ?>/user/browse_jobs.php" class="text-muted">Browse Jobs</a></li>
                        <li><a href="<?php echo APP_URL; ?>/auth/register.php" class="text-muted">Register</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h6>Contact</h6>
                    <p class="text-muted">
                        <i class="fas fa-envelope me-2"></i> support@jobfinder.com<br>
                        <i class="fas fa-phone me-2"></i> +91 1234567890
                    </p>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="text-center text-muted">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved. | Version <?php echo APP_VERSION; ?></p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
    
    <?php if (isset($extra_js)): ?>
        <?php echo $extra_js; ?>
    <?php endif; ?>
    
    <script>
        // Load notifications
        <?php if (is_authenticated()): ?>
        function loadNotifications() {
            fetch('<?php echo APP_URL; ?>/api/notifications.php')
                .then(response => response.json())
                .then(data => {
                    const notifList = document.getElementById('notification-list');
                    if (data.success && data.notifications.length > 0) {
                        notifList.innerHTML = data.notifications.map(notif => `
                            <li>
                                <a class="dropdown-item notification-item ${notif.is_read ? '' : 'unread'}" 
                                   href="#" onclick="markAsRead(${notif.id}, '${notif.link || '#'}')">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-${getNotifIcon(notif.type)} me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <strong>${notif.title}</strong>
                                            <p class="mb-0 small text-muted">${notif.message}</p>
                                            <small class="text-muted">${notif.time_ago}</small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        `).join('');
                    } else {
                        notifList.innerHTML = '<li class="text-center py-3"><small class="text-muted">No notifications</small></li>';
                    }
                });
        }
        
        function getNotifIcon(type) {
            const icons = {
                'info': 'info-circle',
                'success': 'check-circle',
                'warning': 'exclamation-triangle',
                'error': 'times-circle'
            };
            return icons[type] || 'bell';
        }
        
        function markAsRead(id, link) {
            fetch(`<?php echo APP_URL; ?>/api/notifications.php?id=${id}`, {
                method: 'PUT'
            }).then(() => {
                if (link && link !== '#') {
                    window.location.href = link;
                }
                loadNotifications();
            });
        }
        
        // Load notifications on page load
        document.addEventListener('DOMContentLoaded', loadNotifications);
        
        // Refresh notifications every 30 seconds
        setInterval(loadNotifications, 30000);
        <?php endif; ?>
    </script>
</body>
</html>
