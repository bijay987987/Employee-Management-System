// Leave Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Calculate total days when dates change
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (startDateInput && endDateInput) {
        function calculateDays() {
            if (startDateInput.value && endDateInput.value) {
                const start = new Date(startDateInput.value);
                const end = new Date(endDateInput.value);
                
                if (end < start) {
                    alert('End date cannot be before start date');
                    endDateInput.value = '';
                    return;
                }
                
                // Calculate working days (excluding weekends)
                let count = 0;
                const current = new Date(start);
                
                while (current <= end) {
                    const dayOfWeek = current.getDay();
                    if (dayOfWeek !== 0 && dayOfWeek !== 6) { // Skip Sunday (0) and Saturday (6)
                        count++;
                    }
                    current.setDate(current.getDate() + 1);
                }
                
                // Show calculated days
                const daysElement = document.getElementById('calculated_days');
                if (!daysElement) {
                    const formGroup = endDateInput.parentElement;
                    const daysDiv = document.createElement('div');
                    daysDiv.id = 'calculated_days';
                    daysDiv.className = 'alert alert-info mt-2';
                    daysDiv.innerHTML = `<strong>Total Working Days: ${count}</strong>`;
                    formGroup.appendChild(daysDiv);
                } else {
                    daysElement.innerHTML = `<strong>Total Working Days: ${count}</strong>`;
                }
            }
        }
        
        startDateInput.addEventListener('change', calculateDays);
        endDateInput.addEventListener('change', calculateDays);
    }
    
    // Check leave balance
    const leaveTypeSelect = document.getElementById('leave_type');
    if (leaveTypeSelect) {
        leaveTypeSelect.addEventListener('change', function() {
            const leaveTypeId = this.value;
            if (leaveTypeId) {
                fetch(`check_balance.php?leave_type_id=${leaveTypeId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.balance !== undefined) {
                            const balanceElement = document.getElementById('balance_info');
                            if (!balanceElement) {
                                const formGroup = this.parentElement;
                                const div = document.createElement('div');
                                div.id = 'balance_info';
                                div.className = 'alert alert-info mt-2';
                                formGroup.appendChild(div);
                            }
                            document.getElementById('balance_info').innerHTML = 
                                `<strong>Available Balance: ${data.balance} days</strong>`;
                        }
                    });
            }
        });
    }
});

// Function to export leave data
function exportLeaveData(format = 'csv') {
    const table = document.querySelector('table');
    let data = [];
    
    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.innerText);
    });
    
    // Get rows
    table.querySelectorAll('tbody tr').forEach(row => {
        const rowData = [];
        row.querySelectorAll('td').forEach(cell => {
            rowData.push(cell.innerText);
        });
        data.push(rowData);
    });
    
    if (format === 'csv') {
        exportToCSV(headers, data, 'leave_data.csv');
    } else if (format === 'excel') {
        exportToExcel(headers, data, 'leave_data.xlsx');
    }
}

function exportToCSV(headers, data, filename) {
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += headers.join(",") + "\n";
    data.forEach(row => {
        csvContent += row.join(",") + "\n";
    });
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}