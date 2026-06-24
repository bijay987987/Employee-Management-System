// Attendance functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh attendance status every minute
    setInterval(updateAttendanceStatus, 60000);
    
    // Check-in/out confirmation
    const attendanceForms = document.querySelectorAll('form[method="POST"]');
    attendanceForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            const action = button.name;
            const time = new Date().toLocaleTimeString();
            
            if (action === 'check_in') {
                if (!confirm(`Check in at ${time}?`)) {
                    e.preventDefault();
                }
            } else if (action === 'check_out') {
                if (!confirm(`Check out at ${time}?`)) {
                    e.preventDefault();
                }
            }
        });
    });
    
    // Calculate and display working hours
    function calculateWorkingHours() {
        const checkInTime = document.querySelector('.check-in-time');
        const checkOutTime = document.querySelector('.check-out-time');
        
        if (checkInTime && checkOutTime && checkInTime.textContent !== 'Not checked in' && checkOutTime.textContent !== 'Not checked out') {
            // Calculate hours logic here
        }
    }
    
    function updateAttendanceStatus() {
        // This would typically make an AJAX call to update the status
        console.log('Updating attendance status...');
    }
    
    // Initialize
    calculateWorkingHours();
});