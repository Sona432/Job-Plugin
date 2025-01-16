// Toggle details in the submissions table
function toggleDetails(row) {
    const nextRow = row.nextElementSibling;
    if (nextRow && nextRow.classList.contains('details-row')) {
        nextRow.classList.toggle('hidden');
    }
}

// Form validation for the application form
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.form-container form');
    if (form) {
        form.addEventListener('submit', function(event) {
            let valid = true;

            // Check required fields
            const requiredFields = form.querySelectorAll('input[required], textarea[required]');
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('error'); // Add error class for styling
                } else {
                    field.classList.remove('error'); // Remove error class if valid
                }
            });

            // If the form is not valid, prevent submission
            if (!valid) {
                event.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }
});