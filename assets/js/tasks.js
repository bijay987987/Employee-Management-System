// Tasks specific functionality
document.addEventListener('DOMContentLoaded', function() {
    // Task status update confirmation
    const statusForms = document.querySelectorAll('.status-form');
    statusForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const select = this.querySelector('select');
            const taskTitle = this.closest('.task-item').querySelector('.task-title').textContent;
            const newStatus = select.options[select.selectedIndex].text;
            
            if (!confirm(`Are you sure you want to change the status of "${taskTitle}" to "${newStatus}"?`)) {
                e.preventDefault();
                select.value = this.querySelector('input[name="current_status"]').value;
            }
        });
    });
    
    // Task priority highlighting
    const taskItems = document.querySelectorAll('.task-item');
    taskItems.forEach(item => {
        item.addEventListener('click', function() {
            this.classList.toggle('expanded');
        });
    });
    
    // Due date warnings
    const dueDates = document.querySelectorAll('.due-date');
    dueDates.forEach(dateElement => {
        const dueDate = new Date(dateElement.textContent);
        const today = new Date();
        const diffTime = dueDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays < 0) {
            dateElement.style.color = 'var(--danger-color)';
            dateElement.innerHTML += ' <span class="badge" style="background: var(--danger-color); color: white;">Overdue</span>';
        } else if (diffDays <= 2) {
            dateElement.style.color = 'var(--warning-color)';
            dateElement.innerHTML += ' <span class="badge" style="background: var(--warning-color); color: white;">Due Soon</span>';
        }
    });
});