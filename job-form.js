document.getElementById('jobApplicationForm').addEventListener('submit', async function(event) {
    event.preventDefault(); // Prevent default form submission

    const form = event.target;
    const statusMessage = document.getElementById('statusMessage');
    const buttonText = document.getElementById('buttonText');
    const loadingSpinner = document.getElementById('loadingSpinner');

    // Simple client-side validation
    if (!form.checkValidity()) {
        statusMessage.textContent = 'Please fill out all required fields.';
        statusMessage.className = 'block text-sm font-medium text-red-500 text-center';
        return;
    }

    // Show loading state
    buttonText.textContent = 'Submitting...';
    loadingSpinner.classList.remove('hidden');
    form.querySelector('button[type="submit"]').disabled = true;

    const formData = new FormData(form);
    formData.append('action', 'handle_job_application_submission');

    try {
        const response = await fetch(jobFormAjax.ajax_url, {
            method: 'POST',
            body: formData,
        });

        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                statusMessage.textContent = result.data;
                statusMessage.className = 'block text-sm font-medium text-green-500 text-center';
                form.reset(); // Reset form fields on success
                document.getElementById('cv_file_name').textContent = '';
                document.getElementById('cover_letter_file_name').textContent = '';
            } else {
                statusMessage.textContent = result.data;
                statusMessage.className = 'block text-sm font-medium text-red-500 text-center';
            }
        } else {
            statusMessage.textContent = 'An unexpected error occurred. Please try again later.';
            statusMessage.className = 'block text-sm font-medium text-red-500 text-center';
        }
    } catch (error) {
        statusMessage.textContent = 'An unexpected error occurred. Please try again later.';
        statusMessage.className = 'block text-sm font-medium text-red-500 text-center';
    } finally {
        buttonText.textContent = 'Submit Application';
        loadingSpinner.classList.add('hidden');
        form.querySelector('button[type="submit"]').disabled = false;
    }
});

// Add event listener to the Cancel button
document.getElementById('cancelButton').addEventListener('click', function() {
    document.getElementById('jobApplicationForm').reset();
    const statusMessage = document.getElementById('statusMessage');
    statusMessage.textContent = '';
    statusMessage.classList.add('hidden');
    document.getElementById('cv_file_name').textContent = '';
    document.getElementById('cover_letter_file_name').textContent = '';
});

// Add event listeners for file inputs to display the selected file name
document.getElementById('cv_file').addEventListener('change', function(event) {
    const fileName = event.target.files.length > 0 ? event.target.files[0].name : '';
    document.getElementById('cv_file_name').textContent = fileName;
});

document.getElementById('cover_letter_file').addEventListener('change', function(event) {
    const fileName = event.target.files.length > 0 ? event.target.files[0].name : '';
    document.getElementById('cover_letter_file_name').textContent = fileName;
});
