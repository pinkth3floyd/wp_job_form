=== Job Application Form ===

Contributors: prakashniraula
Tags: job application, form, recruitment, cv, resume, ajax
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful and user-friendly job application form plugin for WordPress that allows you to collect job applications with file uploads and manage them from the admin dashboard.

== Description ==

The Job Application Form plugin provides a comprehensive solution for collecting job applications on your WordPress website. It features a modern, responsive form with the following capabilities:

* **Personal Information Collection**: First name, last name, email, phone, address, city, state, postcode, date of birth, nationality
* **Professional Information**: Current company, position, years of experience, education level, expected salary, availability
* **Document Uploads**: CV/Resume (required) and Cover Letter (optional) with file validation
* **Additional Notes**: Text area for any additional information
* **AJAX Submission**: Seamless form submission without page refresh
* **Admin Management**: View all applications in a clean admin interface with delete functionality
* **Database Storage**: Secure storage of all application data
* **Responsive Design**: Mobile-friendly form using Tailwind CSS
* **Security**: Nonce verification and data sanitization

The plugin creates a custom database table to store all application data and provides an admin menu for easy management of submissions.

== Installation ==

1. Upload the `job_application_forms.php` file to the `/wp-content/plugins/job-application-form/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the shortcode `[job_application_form]` to display the form on any page or post.
4. Access the admin menu under "Job Applications" to view and manage submissions.

== Usage ==

1. **Display the Form**: Add the shortcode `[job_application_form]` to any page or post where you want the job application form to appear.

2. **Customize the Form**: The form is styled with Tailwind CSS and includes modern UI elements. You can customize the appearance by modifying the CSS classes in the shortcode function.

3. **View Applications**: Go to the WordPress admin dashboard and click on "Job Applications" in the menu to view all submitted applications.

4. **Manage Applications**: From the admin page, you can view application details and delete unwanted submissions.

== Features ==

* AJAX-powered form submission
* File upload handling for CV and cover letters
* Responsive design that works on all devices
* Secure data handling with WordPress security functions
* Clean admin interface for managing applications
* Custom database table for efficient data storage
* Nonce verification for security
* Data sanitization and validation
* Modern UI with Tailwind CSS styling

== Frequently Asked Questions ==

= How do I display the job application form? =

Simply add the shortcode `[job_application_form]` to any page or post.

= Where can I view the submitted applications? =

Go to the WordPress admin dashboard and click on "Job Applications" in the left menu.

= What file types are accepted for uploads? =

The plugin accepts PDF, DOC, and DOCX files for both CV and cover letter uploads.

= Is the form mobile-friendly? =

Yes, the form is fully responsive and works perfectly on mobile devices.

= Can I customize the form appearance? =

Yes, you can modify the CSS classes and styling in the plugin file to match your theme.

== Screenshots ==

1. The job application form as displayed on the frontend
2. Admin dashboard showing list of submitted applications
3. Individual application details view

== Changelog ==

= 1.2 =
* Improved form validation and error handling
* Enhanced file upload security
* Better responsive design
* Added loading spinner for form submission

= 1.1 =
* Initial release with basic functionality
* AJAX form submission
* Admin management interface
* File upload support

= 1.0 =
* First version

== Upgrade Notice ==

= 1.2 =
This version includes security improvements and better user experience. Upgrade recommended.

== Support ==

For support, please visit the plugin's support forum or contact the author at https://prakashniraula.info

== License ==

This plugin is licensed under the GPLv2 or later. See the included LICENSE file for details.

== Credits ==

* Tailwind CSS for styling framework
* WordPress Codex for development guidelines
* Font Awesome for icons (via CDN)
