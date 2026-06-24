// Dashboard specific functionality
document.addEventListener('DOMContentLoaded', function() {
    // Update current time
    function updateTime() {
        const now = new Date();
        document.getElementById('currentTime').textContent = now.toLocaleString();
    }
    
    // Update time every second
    setInterval(updateTime, 1000);
    updateTime();
    
    // Chart initialization (placeholder for future chart integration)
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'scale(1.02)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
});