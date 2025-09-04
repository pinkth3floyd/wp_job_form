<?php
/*
Plugin Name: Job Application Form
Description: A powerful job application form solution for WordPress.
Version: 1.2
Author: Prakash Niraula
Author URI: https://prakashniraula.info
License: GPLv2 or later
Text Domain: job_form_master
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Step 1: Create the custom database table on plugin activation.
 * This function is called via the register_activation_hook.
 */
function job_application_create_db_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'job_applications';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        first_name varchar(255) NOT NULL,
        last_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(255) DEFAULT NULL,
        address varchar(255) DEFAULT NULL,
        city varchar(255) DEFAULT NULL,
        state varchar(255) DEFAULT NULL,
        postcode varchar(255) DEFAULT NULL,
        date_of_birth date DEFAULT NULL,
        nationality varchar(255) DEFAULT NULL,
        current_company varchar(255) DEFAULT NULL,
        current_position varchar(255) DEFAULT NULL,
        years_of_experience int(11) DEFAULT 0,
        education_level varchar(255) DEFAULT NULL,
        expected_salary varchar(255) DEFAULT NULL,
        availability varchar(255) DEFAULT NULL,
        cv_file_url varchar(255) DEFAULT NULL,
        cover_letter_file_url varchar(255) DEFAULT NULL,
        cover_letter_text text DEFAULT NULL,
        additional_notes text DEFAULT NULL,
        submission_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'job_application_create_db_table');

/**
 * Step 2: Add a new menu item to the WordPress admin dashboard.
 * This function adds the menu page for viewing job applications.
 */
function job_application_admin_menu() {
    add_menu_page(
        'Job Applications',              // Page title
        'Job Applications',              // Menu title
        'manage_options',                // Capability
        'job-application-submissions',   // Menu slug
        'job_application_submissions_page', // Function to display the page content
        'dashicons-clipboard',           // Icon URL/Class
        6                                // Position in the menu
    );
}
add_action('admin_menu', 'job_application_admin_menu');

/**
 * Step 3: The function that displays the content of the admin page.
 * This fetches all job applications from the database and displays them in a table.
 * It also adds a delete button for each entry.
 */
function job_application_submissions_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'job_applications';
    $cache_key = 'job_applications_submissions';
    $submissions = wp_cache_get($cache_key);
    if (false === $submissions) {
        $submissions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} ORDER BY submission_date DESC" ) );
        wp_cache_set($cache_key, $submissions, '', 300); // Cache for 5 minutes
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Job Applications</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Location</th>
                    <th scope="col">Experience</th>
                    <th scope="col">Education</th>
                    <th scope="col">CV</th>
                    <th scope="col">Cover Letter</th>
                    <th scope="col">Date</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($submissions) : ?>
                    <?php foreach ($submissions as $submission) : ?>
                        <tr id="application-<?php echo esc_attr($submission->id); ?>">
                            <td><?php echo esc_html($submission->id); ?></td>
                            <td><?php echo esc_html($submission->first_name . ' ' . $submission->last_name); ?></td>
                            <td><?php echo esc_html($submission->email); ?></td>
                            <td><?php echo esc_html($submission->phone); ?></td>
                            <td><?php echo esc_html($submission->city . ', ' . $submission->state); ?></td>
                            <td><?php echo esc_html($submission->years_of_experience); ?></td>
                            <td><?php echo esc_html($submission->education_level); ?></td>
                            <td>
                                <?php if ($submission->cv_file_url) : ?>
                                    <a href="<?php echo esc_url($submission->cv_file_url); ?>" target="_blank">View CV</a>
                                <?php else : ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($submission->cover_letter_file_url) : ?>
                                    <a href="<?php echo esc_url($submission->cover_letter_file_url); ?>" target="_blank">View File</a>
                                <?php elseif ($submission->cover_letter_text) : ?>
                                    <span title="<?php echo esc_attr($submission->cover_letter_text); ?>">View Text</span>
                                <?php else : ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($submission->submission_date); ?></td>
                            <td>
                                <button class="delete-application-btn button button-small" data-id="<?php echo esc_attr($submission->id); ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="11">No applications found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-application-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const applicationId = this.getAttribute('data-id');
                    const confirmed = confirm('Are you sure you want to delete this application? This action cannot be undone.');

                    if (confirmed) {
                        const data = new FormData();
                        data.append('action', 'handle_job_application_deletion');
                        data.append('application_id', applicationId);
                        data.append('security', '<?php echo esc_attr( wp_create_nonce('delete_job_application_nonce') ); ?>');

                        fetch('<?php echo esc_url( admin_url('admin-ajax.php') ); ?>', {
                            method: 'POST',
                            body: data,
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                document.getElementById('application-' + applicationId).remove();
                                alert('Application deleted successfully!');
                            } else {
                                alert('Error deleting application: ' + (result.data || 'Unknown error.'));
                            }
                        })
                        .catch(error => {
                            alert('An unexpected error occurred.');
                            console.error('Error:', error);
                        });
                    }
                });
            });
        });
    </script>
    <?php
}

/**
 * Handle the AJAX deletion from the admin page.
 */
function handle_job_application_deletion() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'job_applications';

    // Sanitize and verify nonce for security
    $security = isset($_POST['security']) ? sanitize_text_field(wp_unslash($_POST['security'])) : '';
    if (empty($security) || !wp_verify_nonce($security, 'delete_job_application_nonce')) {
        wp_send_json_error('Security check failed.');
    }

    $application_id = isset($_POST['application_id']) ? intval(wp_unslash($_POST['application_id'])) : 0;

    if ($application_id > 0) {
        $deleted = $wpdb->delete($table_name, array('id' => $application_id), array('%d'));

        if ($deleted) {
            wp_cache_delete('job_applications_submissions'); // Clear cache after deletion
            wp_send_json_success('Application deleted successfully.');
        } else {
            wp_send_json_error('Error deleting application. Application may not exist or database error.');
        }
    } else {
        wp_send_json_error('Invalid application ID.');
    }

    wp_die();
}
add_action('wp_ajax_handle_job_application_deletion', 'handle_job_application_deletion');

/**
 * Step 4: Handle the AJAX submission from the form.
 * This function processes all form data, including file uploads.
 */
function handle_job_application_submission() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'job_applications';

    // Verify nonce for security
    $nonce = isset($_POST['job_application_nonce']) ? sanitize_text_field(wp_unslash($_POST['job_application_nonce'])) : '';
    if (empty($nonce) || !wp_verify_nonce($nonce, 'job_application_form_action')) {
        wp_send_json_error('Security check failed.');
    }

    // Sanitize and validate data
    $first_name = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
    $last_name = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $address = isset($_POST['address']) ? sanitize_text_field(wp_unslash($_POST['address'])) : '';
    $city = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';
    $state = isset($_POST['state']) ? sanitize_text_field(wp_unslash($_POST['state'])) : '';
    $postcode = isset($_POST['postcode']) ? sanitize_text_field(wp_unslash($_POST['postcode'])) : '';
    $date_of_birth = isset($_POST['date_of_birth']) ? sanitize_text_field(wp_unslash($_POST['date_of_birth'])) : '';
    $nationality = isset($_POST['nationality']) ? sanitize_text_field(wp_unslash($_POST['nationality'])) : '';
    $current_company = isset($_POST['current_company']) ? sanitize_text_field(wp_unslash($_POST['current_company'])) : '';
    $current_position = isset($_POST['current_position']) ? sanitize_text_field(wp_unslash($_POST['current_position'])) : '';
    $years_of_experience = isset($_POST['years_of_experience']) ? intval(wp_unslash($_POST['years_of_experience'])) : 0;
    $education_level = isset($_POST['education_level']) ? sanitize_text_field(wp_unslash($_POST['education_level'])) : '';
    $expected_salary = isset($_POST['expected_salary']) ? sanitize_text_field(wp_unslash($_POST['expected_salary'])) : '';
    $availability = isset($_POST['availability']) ? sanitize_text_field(wp_unslash($_POST['availability'])) : '';
    $cover_letter_text = isset($_POST['cover_letter']) ? sanitize_textarea_field(wp_unslash($_POST['cover_letter'])) : '';
    $additional_notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($_FILES['cv_file'])) {
        wp_send_json_error('Please fill out all required fields.');
    }

    // Handle file uploads
    $cv_file_url = null;
    $cover_letter_file_url = null;

    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    $upload_overrides = array('test_form' => false);

    // Validate and sanitize CV file upload
    if (isset($_FILES['cv_file']) && !empty($_FILES['cv_file']['name'])) {
        $cv_file = array_map('sanitize_text_field', $_FILES['cv_file']);
        $cv_file_name = sanitize_file_name($cv_file['name']);
        $cv_file_type = wp_check_filetype_and_ext($cv_file['tmp_name'], $cv_file_name);
        $allowed_types = array('pdf', 'doc', 'docx');
        if (!in_array($cv_file_type['ext'], $allowed_types)) {
            wp_send_json_error('Invalid CV file type. Allowed types: PDF, DOC, DOCX.');
        }
        $cv_file['name'] = $cv_file_name;
        $cv_uploaded_file = wp_handle_upload($cv_file, $upload_overrides);
        if (isset($cv_uploaded_file['url'])) {
            $cv_file_url = $cv_uploaded_file['url'];
        } else {
            wp_send_json_error('CV file upload failed: ' . $cv_uploaded_file['error']);
        }
    } else {
        wp_send_json_error('CV file is required.');
    }

    // Validate and sanitize Cover Letter file upload if present
    if (isset($_FILES['cover_letter_file']) && !empty($_FILES['cover_letter_file']['name'])) {
        $cover_letter_file = array_map('sanitize_text_field', $_FILES['cover_letter_file']);
        $cover_letter_file_name = sanitize_file_name($cover_letter_file['name']);
        $cover_letter_file_type = wp_check_filetype_and_ext($cover_letter_file['tmp_name'], $cover_letter_file_name);
        $allowed_cover_types = array('pdf', 'doc', 'docx', 'txt');
        if (!in_array($cover_letter_file_type['ext'], $allowed_cover_types)) {
            wp_send_json_error('Invalid Cover Letter file type. Allowed types: PDF, DOC, DOCX, TXT.');
        }
        $cover_letter_file['name'] = $cover_letter_file_name;
        $cover_letter_uploaded_file = wp_handle_upload($cover_letter_file, $upload_overrides);
        if (isset($cover_letter_uploaded_file['url'])) {
            $cover_letter_file_url = $cover_letter_uploaded_file['url'];
        } else {
            wp_send_json_error('Cover Letter file upload failed: ' . $cover_letter_uploaded_file['error']);
        }
    }

    // Insert data into the database
    $inserted = $wpdb->insert(
        $table_name,
        array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'postcode' => $postcode,
            'date_of_birth' => $date_of_birth,
            'nationality' => $nationality,
            'current_company' => $current_company,
            'current_position' => $current_position,
            'years_of_experience' => $years_of_experience,
            'education_level' => $education_level,
            'expected_salary' => $expected_salary,
            'availability' => $availability,
            'cv_file_url' => $cv_file_url,
            'cover_letter_file_url' => $cover_letter_file_url,
            'cover_letter_text' => $cover_letter_text,
            'additional_notes' => $additional_notes,
        )
    );

    if ($wpdb->last_error) {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    } else {
        wp_cache_delete('job_applications_submissions'); // Clear cache after insertion
        wp_send_json_success('Your application has been submitted successfully!');
    }

    wp_die();
}
// Hook into WordPress AJAX system for both logged-in and logged-out users
add_action('wp_ajax_handle_job_application_submission', 'handle_job_application_submission');
add_action('wp_ajax_nopriv_handle_job_application_submission', 'handle_job_application_submission');

/**
 * Step 5: Create a shortcode to display the form HTML.
 * This allows the user to place the form anywhere on the site using [job_application_form].
 */
function job_application_form_shortcode() {
    // Enqueue scripts and styles
    wp_enqueue_script('job-form-script', plugin_dir_url(__FILE__) . 'job-form.js', array('jquery'), '1.0', true);
    wp_localize_script('job-form-script', 'jobFormAjax', array('ajax_url' => admin_url('admin-ajax.php')));
    // Remove external Tailwind CSS CDN and add local fallback or custom styles
    // For now, remove the external enqueue to comply with plugin guidelines
    // wp_enqueue_style('job-form-styles', 'https://cdn.tailwindcss.com');

    ob_start();
    ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #ffffff;
            width: 24px;
            height: 24px;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <!-- Increased max-w-4xl to max-w-6xl for wider form as requested -->
    <div class="rounded-xl border bg-card text-card-foreground shadow p-6 max-w-6xl mx-auto">
        <div class="flex flex-col space-y-1.5 pb-6">
            <!-- Increased font size for the main heading -->
            <h3 class="font-semibold tracking-tight text-2xl text-gray-900 text-center">Job Application Form</h3>
            <p class="text-sm text-muted-foreground text-center">Please fill out all required fields to submit your application for the Room Attendant position.</p>
        </div>
        <div class="p-6 pt-0">
            <form id="jobApplicationForm" class="space-y-6" enctype="multipart/form-data">
                <input type="hidden" name="job_application_nonce" value="<?php echo esc_attr( wp_create_nonce('job_application_form_action') ); ?>" />
                <div class="space-y-4">
                    <!-- Increased font size for section heading to 18px -->
                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Personal Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="first_name">First Name *</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="first_name" required="" placeholder="Enter your first name" name="first_name">
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="last_name">Last Name *</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="last_name" required="" placeholder="Enter your last name" name="last_name">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="email">Email Address *</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="email" required="" placeholder="Enter your email address" type="email" name="email">
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="phone">Phone Number</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="phone" placeholder="Enter your phone number" name="phone">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="address">Address</label>
                        <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="address" placeholder="Enter your address" name="address">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="city">City</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="city" placeholder="Enter your city" name="city">
                        </div>
                        <div>
                            <!-- Changed State field from select to input -->
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="state">State</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="state" placeholder="Enter your state" name="state">
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="postcode">Postcode</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="postcode" placeholder="Enter your postcode" name="postcode">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="date_of_birth">Date of Birth</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="date_of_birth" type="date" name="date_of_birth">
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="nationality">Nationality</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="nationality" placeholder="Enter your nationality" value="Australian" name="nationality">
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <!-- Increased font size for section heading to 18px -->
                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Professional Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="current_company">Current Company</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="current_company" placeholder="Enter your current company" name="current_company">
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="current_position">Current Position</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="current_position" placeholder="Enter your current position" name="current_position">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="years_of_experience">Years of Experience</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="years_of_experience" placeholder="Enter years of experience" type="number" value="0" name="years_of_experience">
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="education_level">Education Level</label>
                            <div class="relative">
                                <select class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50 appearance-none" id="education_level" name="education_level">
                                    <option value="High School">High School</option>
                                    <option value="Certificate">Certificate</option>
                                    <option value="Diploma">Diploma</option>
                                    <option value="Bachelor">Bachelor</option>
                                    <option value="Master">Master</option>
                                    <option value="PhD">PhD</option>
                                    <option value="Other">Other</option>
                                </select>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon" class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 pointer-events-none">
                                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="expected_salary">Expected Salary</label>
                            <input class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="expected_salary" placeholder="Enter your expected salary" name="expected_salary">
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="availability">Availability</label>
                            <div class="relative">
                                <select class="flex h-9 w-full rounded-md border border-gray-300 bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50 appearance-none" id="availability" name="availability">
                                    <option value="Immediate">Immediate</option>
                                    <option value="2 weeks">2 weeks</option>
                                    <option value="1 month">1 month</option>
                                    <option value="3 months">3 months</option>
                                    <option value="Other">Other</option>
                                </select>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon" class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 pointer-events-none">
                                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <!-- Increased font size for section heading to 18px -->
                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Documents</h3>
                    <div>
                        <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="cv_file">CV/Resume *</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon" class="mx-auto h-12 w-12 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"></path>
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="cv_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Upload a file</span>
                                        <input id="cv_file" class="sr-only" accept=".pdf,.doc,.docx" required="" type="file" name="cv_file">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PDF, DOC, DOCX up to 10MB</p>
                                <!-- Added span to display selected file name -->
                                <span id="cv_file_name" class="mt-2 text-sm text-gray-500"></span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="cover_letter_file">Cover Letter (Optional)</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon" class="mx-auto h-12 w-12 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"></path>
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="cover_letter_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Upload a file</span>
                                        <input id="cover_letter_file" class="sr-only" accept=".pdf,.doc,.docx,.txt" type="file" name="cover_letter_file">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PDF, DOC, DOCX, TXT up to 10MB</p>
                                <!-- Added span to display selected file name -->
                                <span id="cover_letter_file_name" class="mt-2 text-sm text-gray-500"></span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="cover_letter">Cover Letter Text (Optional)</label>
                        <textarea class="flex min-h-[60px] w-full rounded-md border border-gray-300 bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="cover_letter" name="cover_letter" placeholder="Write your cover letter here..." rows="6"></textarea>
                    </div>
                </div>
                <div class="space-y-4">
                    <!-- Increased font size for section heading to 18px -->
                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Additional Information</h3>
                    <div>
                        <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" for="notes">Additional Notes</label>
                        <textarea class="flex min-h-[60px] w-full rounded-md border border-gray-300 bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50" id="notes" name="notes" placeholder="Any additional information you'd like to share..." rows="4"></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-4 pt-6 border-t">
                    <button id="cancelButton" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-gray-100 h-9 px-4 py-2" type="button">Cancel</button>
                    <button id="submitButton" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 bg-indigo-600 text-white shadow hover:bg-indigo-700 h-9 px-4 py-2 min-w-[120px]" type="submit">
                        <span id="buttonText">Submit Application</span>
                        <div id="loadingSpinner" class="hidden loading-spinner ml-2"></div>
                    </button>
                </div>
            </form>
            <div id="statusMessage" class="mt-4 hidden"></div>
        </div>
    </div>
    <script>
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
                const response = await fetch('<?php echo esc_url( admin_url('admin-ajax.php') ); ?>', {
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
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('job_application_form', 'job_application_form_shortcode');
?>
