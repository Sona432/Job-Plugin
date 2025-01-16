<?php
/**
* Plugin Name: Job Board
* Description: Job board plugin
* Author: Sona
* Version: 1.0
*/
 
if (!defined('ABSPATH')) {
    exit;
}
 
// Enqueue Styles
function jb_enqueue_styles() {
    if (is_singular('job')) { // Ensures this CSS loads only for job posts
        wp_enqueue_style('job-board-styles', plugins_url('style.css', __FILE__));
    }
}
 
// Register custom post type and taxonomies
function jb_register_job_post_type()
{
    // Register Custom Post Type: Job
    register_post_type('job', array(
        'labels' => array(
            'name' => __('Jobs', 'job-board'),
            'singular_name' => __('Job', 'job-board'),
            'add_new' => __('Add New Job', 'job-board'),
            'add_new_item' => __('Add New Job', 'job-board'),
            'edit_item' => __('Edit Job', 'job-board'),
            'new_item' => __('New Job', 'job-board'),
            'view_item' => __('View Job', 'job-board'),
            'search_items' => __('Search Jobs', 'job-board'),
            'not_found' => __('No jobs found', 'job-board'),
            'not_found_in_trash' => __('No jobs found in trash', 'job-board'),
        ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-briefcase',
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'rewrite' => array('slug' => 'jobs'),
    ));
 
    // Register Taxonomy: Categories
    register_taxonomy('job_category', 'job', array(
        'labels' => array(
            'name' => __('Categories', 'job-board'),
            'singular_name' => __('Category', 'job-board'),
        ),
        'hierarchical' => true,
        'public' => true,
        'rewrite' => array('slug' => 'job-category'),
    ));
 
    // Register Taxonomy: Locations
    register_taxonomy('job_location', 'job', array(
        'labels' => array(
            'name' => __('Locations', 'job-board'),
            'singular_name' => __('Location', 'job-board'),
        ),
        'hierarchical' => true,
        'public' => true,
        'rewrite' => array('slug' => 'job-location'),
    ));
}
add_action('init', 'jb_register_job_post_type');
 
// Add default taxonomy terms
function jb_add_default_terms()
{
    $categories = ['Consultant', 'Sales', 'Developer', 'Marketing', 'HR'];
    $locations = ['Delhi', 'Mumbai', 'Bangalore', 'Chennai', 'Hyderabad'];
 
    foreach ($categories as $category) {
        if (!term_exists($category, 'job_category')) {
            wp_insert_term($category, 'job_category');
        }
    }
 
    foreach ($locations as $location) {
        if (!term_exists($location, 'job_location')) {
            wp_insert_term($location, 'job_location');
        }
    }
}
add_action('init', 'jb_add_default_terms');
 
// Add Meta Boxes
function jb_add_job_meta_boxes() {
    add_meta_box(
        'job_details_meta',
        __('Job Details', 'job-board'),
        'jb_render_job_meta_boxes',
        'job',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'jb_add_job_meta_boxes');
 
// Render Meta Boxes
function jb_render_job_meta_boxes($post) {
    // Get existing values
    $company_name = get_post_meta($post->ID, '_company_name', true);
    $job_type = get_post_meta($post->ID, '_job_type', true);
    $salary = get_post_meta($post->ID, '_salary', true);
    $expiry_date = get_post_meta($post->ID, '_expiry_date', true);
 
    wp_nonce_field('save_job_meta_data', 'job_meta_nonce');
 
    echo '<label for="company_name">' . __('Company Name:', 'job-board') . '</label>';
    echo '<input type="text" id="company_name" name="company_name" value="' . esc_attr($company_name) . '" class="widefat" />';
 
    echo '<label for="job_type">' . __('Job Type:', 'job-board') . '</label>';
    echo '<input type="text" id="job_type" name="job_type" value="' . esc_attr($job_type) . '" class="widefat" />';
 
    echo '<label for="salary">' . __('Salary:', 'job-board') . '</label>';
    echo '<input type="text" id="salary" name="salary" value="' . esc_attr($salary) . '" class="widefat" />';
 
    echo '<label for="expiry_date">' . __('Expiry Date:', 'job-board') . '</label>';
    echo '<input type="date" id="expiry_date" name="expiry_date" value="' . esc_attr($expiry_date) . '" class="widefat" />';
}
 
// Save Job Meta Data
function jb_save_job_meta_data($post_id) {
    // Avoid saving on autosave or in non-legitimate requests
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
 
    if (!isset($_POST['job_meta_nonce']) || !wp_verify_nonce($_POST['job_meta_nonce'], 'save_job_meta_data')) {
        return;
    }
 
    // Ensure user has permission to edit post
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
 
    // Save or update post meta
    if (isset($_POST['company_name'])) {
        update_post_meta($post_id, '_company_name', sanitize_text_field($_POST['company_name']));
    }
    if (isset($_POST['job_type'])) {
        update_post_meta($post_id, '_job_type', sanitize_text_field($_POST['job_type']));
    }
    if (isset($_POST['salary'])) {
        update_post_meta($post_id, '_salary', sanitize_text_field($_POST['salary']));
    }
    if (isset($_POST['expiry_date'])) {
        $expiry_date = sanitize_text_field($_POST['expiry_date']);
        update_post_meta($post_id, '_expiry_date', $expiry_date);
    }
}
add_action('save_post', 'jb_save_job_meta_data');
 
// Create custom database table on plugin activation
function jb_create_custom_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'job_posts';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT(20) UNSIGNED NOT NULL,
        company_name VARCHAR(255) NOT NULL,
        job_type VARCHAR(100) NOT NULL,
        salary VARCHAR(100) NOT NULL,
        expiry_date DATE NOT NULL,
        PRIMARY KEY (id),
        KEY post_id (post_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'jb_create_custom_table');
 
// Hook into 'save_post' to insert/update job post data in custom table
function jb_save_job_to_custom_table($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
 
    if (get_post_type($post_id) !== 'job') {
        return;
    }
 
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
 
    global $wpdb;
    $table_name = $wpdb->prefix . 'job_posts';
 
    // Fetch data from post meta
    $company_name = get_post_meta($post_id, '_company_name', true);
    $job_type = get_post_meta($post_id, '_job_type', true);
    $salary = get_post_meta($post_id, '_salary', true);
    $expiry_date = get_post_meta($post_id, '_expiry_date', true);
 
 
 
    // Insert or update the data in the custom table
    $wpdb->replace($table_name, array(
        'post_id' => $post_id,
 
        'company_name' => $company_name,
        'job_type' => $job_type,
        'salary' => $salary,
        'expiry_date' => $expiry_date,
    ), array('%d', '%s', '%s', '%s', '%s'));
}
add_action('save_post', 'jb_save_job_to_custom_table');
 
 
// Add Meta Boxes for Expiry Date
function jb_add_expiry_date_meta_box() {
    add_meta_box(
        'job_expiry_date_meta',
        __('Job Expiry Date', 'job-board'),
        'jb_render_expiry_date_meta_box',
        'job',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'jb_add_expiry_date_meta_box');
 
// Render Expiry Date Meta Box
function jb_render_expiry_date_meta_box($post) {
    $expiry_date = get_post_meta($post->ID, '_expiry_date', true);
 
    wp_nonce_field('save_job_expiry_date', 'expiry_date_nonce');
 
    echo '<label for="expiry_date">' . __('Expiry Date:', 'job-board') . '</label>';
    echo '<input type="date" id="expiry_date" name="expiry_date" value="' . esc_attr($expiry_date) . '" class="widefat" />';
}
 
// Save Expiry Date Meta Data
function jb_save_expiry_date_meta_data($post_id) {
    if (!isset($_POST['expiry_date_nonce']) || !wp_verify_nonce($_POST['expiry_date_nonce'], 'save_job_expiry_date')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
 
    // Make sure the expiry date is being properly sanitized and stored
    $expiry_date = sanitize_text_field($_POST['expiry_date']);
    
    // Ensure the date format is correct (Y-m-d)
    $expiry_date = date('Y-m-d', strtotime($expiry_date));
 
    update_post_meta($post_id, '_expiry_date', $expiry_date);
}
add_action('save_post', 'jb_save_expiry_date_meta_data');
 
// Display Meta Fields in the Single Job Post Page
function jb_display_job_details($content) {
    if (is_singular('job')) {
        $company_name = get_post_meta(get_the_ID(), '_company_name', true);
        $job_type = get_post_meta(get_the_ID(), '_job_type', true);
        $salary = get_post_meta(get_the_ID(), '_salary', true);
        $expiry_date = get_post_meta(get_the_ID(), '_expiry_date', true);
 
        // Check if the job has expired
        $is_expired = false;
        if ($expiry_date && strtotime($expiry_date) < strtotime('today')) {
            $is_expired = true;
        }
 
        // Get the featured image
        $job_thumbnail = get_the_post_thumbnail(get_the_ID(), 'medium', ['style' => 'max-width: 100%; height: auto;']);
 
        // Begin Flexbox Container
        $custom_content = '<div style="display: flex; align-items: flex-start;">';
 
        // Left Section: Job Details
        $custom_content .= '<div style="flex: 2; margin-right: 20px;">';
        if ($company_name) {
            $custom_content .= '<p><strong>' . __('Company Name:', 'job-board') . '</strong> ' . esc_html($company_name) . '</p>';
        }
        if ($job_type) {
            $custom_content .= '<p><strong>' . __('Job Type:', 'job-board') . '</strong> ' . esc_html($job_type) . '</p>';
        }
        if ($salary) {
            $custom_content .= '<p><strong>' . __('Salary:', 'job-board') . '</strong> ' . esc_html($salary) . '</p>';
        }
 
        // Display Expired status
        if ($is_expired) {
            $custom_content .= '<p><strong>' . __('Status:', 'job-board') . '</strong> ' . __('Expired', 'job-board') . '</p>';
        } else {
            // Apply Now Button for the single job post page
            $job_id = get_the_ID(); // Get the current job post ID
            $apply_url = add_query_arg('job_id', $job_id, home_url('/apply-now')); // Dynamically build the URL for Apply Now
            $custom_content .= '<p><a href="' . esc_url($apply_url) . '" class="apply-now-button">' . __('Apply Now', 'job-board') . '</a></p>';
        }
        
        $custom_content .= '</div>'; // Close Left Section
 
        // Right Section: Featured Image
        $custom_content .= '<div style="flex: 1; text-align: center;">';
        if ($job_thumbnail) {
            $custom_content .= $job_thumbnail;
        } else {
            $custom_content .= '<p>' . __('No image available', 'job-board') . '</p>';
        }
        $custom_content .= '</div>'; // Close Right Section
 
        $custom_content .= '</div>'; // Close Flexbox Container
 
        return $custom_content . $content;
    }
 
    return $content;
}
add_filter('the_content', 'jb_display_job_details');
 
// Modify the job query to exclude expired jobs
function jb_display_jobs_shortcode($atts) {
    $args = array(
        'post_type' => 'job',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_expiry_date', // The meta key for expiry date
                'value' => date('Y-m-d'), // The current date
                'compare' => '>=', // Only show jobs with expiry date >= today
                'type' => 'DATE',
            ),
        ),

             'orderby' => 'date', // Sort by date
        'order' => 'DESC', // Newest first
    );

    // Add filtering by category and location
    if (isset($_GET['category'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'job_category',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['category']),
        );
    }

    if (isset($_GET['location'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'job_location',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['location']),
        );
    }


 
    $query = new WP_Query($args);
    $output = '';
 
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
 
            // Meta Fields
            $company_name = get_post_meta(get_the_ID(), '_company_name', true);
            $job_type = get_post_meta(get_the_ID(), '_job_type', true);
            $salary = get_post_meta(get_the_ID(), '_salary', true);
 
            // Taxonomies
            $categories = get_the_terms(get_the_ID(), 'job_category');
            $locations = get_the_terms(get_the_ID(), 'job_location');
 
            $output .= '<div class="job-post">';
 
            // Job Details and Apply Now Button (similar to the single post page)
            $output .= '<h2>' . get_the_title() . '</h2>';
 
            if ($company_name) {
                $output .= '<p><strong>' . __('Company Name:', 'job-board') . '</strong> ' . esc_html($company_name) . '</p>';
            }
            if ($job_type) {
                $output .= '<p><strong>' . __('Job Type:', 'job-board') . '</strong> ' . esc_html($job_type) . '</p>';
            }
            if ($salary) {
                $output .= '<p><strong>' . __('Salary:', 'job-board') . '</strong> ' . esc_html($salary) . '</p>';
            }
 
            // Display Categories and Locations
            if (!empty($categories) && !is_wp_error($categories)) {
                $category_names = wp_list_pluck($categories, 'name');
                $output .= '<p><strong>' . __('Categories:', 'job-board') . '</strong> ' . implode(', ', $category_names) . '</p>';
            }
            if (!empty($locations) && !is_wp_error($locations)) {
                $location_names = wp_list_pluck($locations, 'name');
                $output .= '<p><strong>' . __('Locations:', 'job-board') . '</strong> ' . implode(', ', $location_names) . '</p>';
            }
 
            // Add View More Link and Apply Now Button
            $output .= '<p><a href="' . get_permalink() . '" class="view-more-link">' . __('View More', 'job-board') . '</a></p>';
            $output .= '<p><a href="' . add_query_arg('job_id', get_the_ID(), home_url('/apply-now')) . '" class="apply-now-button">' . __('Apply Now', 'job-board') . '</a></p>';
 
            $output .= '</div>'; // Close job post div
        }
    } else {
        $output .= '<p>' . __('No job posts found.', 'job-board') . '</p>';
    }
 
    wp_reset_postdata();
 
    return $output;
}
add_shortcode('display_jobs', 'jb_display_jobs_shortcode');
 
 
function create_form_submissions_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'forms';
    $charset_collate = $wpdb->get_charset_collate();
 
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(15) NOT NULL,
        graduation_year int(4) NOT NULL,
        degree varchar(100) NOT NULL,
        college_name varchar(255) NOT NULL,
        resume_url varchar(255) NOT NULL,
        photo_url varchar(255) NOT NULL,
        job_id int(11) NOT NULL,  -- Added to store the job post ID
        submission_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
 
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_form_submissions_table');
 
register_activation_hook(__FILE__, 'create_form_submissions_table');
function display_form_shortcode() {
    // Get the job_id from the URL
    $job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
 
    if ($job_id == 0) {
        return '<p class="error-message">Job ID is missing or invalid.</p>';
    }
 
    $success_message = isset($_GET['success']) && $_GET['success'] === 'true' ? '<p class="success-message">Form submitted successfully!</p>' : '';
    $error_message = isset($_GET['error']) && $_GET['error'] === 'true' ? '<p class="error-message">There was an error submitting the form. Please try again.</p>' : '';
 
    ob_start(); ?>
    <div class="form-container">
        <p>Apply Now</p>
        <?php echo $success_message; ?>
        <?php echo $error_message; ?>
        <div class="form-content">
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="process_form_data">
                
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
                
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                
                <label for="phone">Phone Number:</label>
                <input type="text" id="phone" name="phone" required>
                
                <label for="graduation_year">Graduation Year:</label>
                <input type="number" id="graduation_year" name="graduation_year" required>
                
                <label for="degree">Degree:</label>
                <input type="text" id="degree" name="degree" required>
                
                <label for="college_name">College Name:</label>
                <input type="text" id="college_name" name="college_name" required>
                 
                <label for="resume">Resume (PDF, DOC, DOCX):</label>
                <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
 
                <label for="photo">Photo (JPEG, PNG):</label>
                <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png" required>
 
                <input type="hidden" name="job_id" value="<?php echo esc_attr($job_id); ?>"> <!-- Pass the job_id -->
 
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>
    <?php return ob_get_clean();
}
add_shortcode('display_form', 'display_form_shortcode');
 
function process_form_data() {
    global $wpdb;
 
    // Retrieve form data
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $graduation_year = isset($_POST['graduation_year']) ? intval($_POST['graduation_year']) : '';
    $degree = isset($_POST['degree']) ? sanitize_text_field($_POST['degree']) : '';
    $college_name = isset($_POST['college_name']) ? sanitize_text_field($_POST['college_name']) : '';
    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;  // Capture job_id
 
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
 
    // Initialize file URLs
    $resume_url = '';
    $photo_url = '';
 
    // Process resume file upload
    if (isset($_FILES['resume'])) {
        $uploadedfile = $_FILES['resume'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
 
        if ($movefile && !isset($movefile['error'])) {
            $resume_url = $movefile['url'];
        } else {
            wp_redirect(add_query_arg('error', 'true', $_SERVER['HTTP_REFERER']));
            exit;
        }
    }
 
    // Process photo file upload
    if (isset($_FILES['photo'])) {
        $uploadedfile = $_FILES['photo'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
 
        if ($movefile && !isset($movefile['error'])) {
            $photo_url = $movefile['url'];
        } else {
            wp_redirect(add_query_arg('error', 'true', $_SERVER['HTTP_REFERER']));
            exit;
        }
    }
 
    // Insert data into the database
    $submission_date = current_time('mysql');
    $table_name = $wpdb->prefix . 'forms';
    $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'graduation_year' => $graduation_year,
            'degree' => $degree,
            'college_name' => $college_name,
            'resume_url' => $resume_url,
            'photo_url' => $photo_url,
            'job_id' => $job_id,  // Store the job ID
            'submission_date' => $submission_date,
        ),
        array(
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        )
    );
 
    // Send admin email notification
    $admin_email = get_option('admin_email');
    $subject_admin = 'New Form Submission';
    $body_admin = "A new form submission has been received from $name.\n\nName: $name\nEmail: $email\nPhone: $phone\nGraduation Year: $graduation_year\nDegree: $degree\nCollege Name: $college_name\nJob ID: $job_id\nResume: $resume_url\nPhoto: $photo_url\n";
    wp_mail($admin_email, $subject_admin, $body_admin);
 
    // Send thank you email to the applicant
    $subject_client = 'Thank you for your submission';
    $body_client = "Dear $name,\n\nThank you for your submission for the job position (ID: $job_id). We will get back to you as soon as possible.\n\nBest regards,\n[Your Website]";
    wp_mail($email, $subject_client, $body_client);
 
    // Redirect to success page
    wp_redirect(add_query_arg('success', 'true', $_SERVER['HTTP_REFERER']));
    exit;
}
 
add_action('admin_post_process_form_data', 'process_form_data');
add_action('admin_post_nopriv_process_form_data', 'process_form_data');
 
function display_form_data_shortcode() {
    global $wpdb;
 
    $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';
    $table_name = $wpdb->prefix . 'forms';
 
    if (!empty($search_query)) {
        $submissions = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE name LIKE %s OR email LIKE %s OR phone LIKE %s OR graduation_year LIKE %s OR degree LIKE %s OR college_name LIKE %s OR job LIKE %s", "%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%")
        );
    } else {
        $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submission_date DESC");
    }
 
    ob_start(); ?>
    <div class="form-data-container">
        <h1>Form Submissions</h1>
        <form method="post" action="" class="search-form">
            <input type="text" name="search_query" placeholder="Search..." value="<?php echo esc_attr($search_query); ?>">
            <input type="submit" value="Search">
        </form>
        <?php
        if (!empty($submissions)) {
            echo '<div class="submitted-details">';
            echo '<ul>';
            foreach ($submissions as $submission) {
                echo '<li>';
                echo '<strong>Name:</strong> ' . esc_html($submission->name) . '<br>';
                echo '<strong>Email:</strong> ' . esc_html($submission->email) . '<br>';
                echo '<strong>Phone:</strong> ' . esc_html($submission->phone) . '<br>';
                echo '<strong>Graduation Year:</strong> ' . esc_html($submission->graduation_year) . '<br>';
                echo '<strong>Degree:</strong> ' . esc_html($submission->degree) . '<br>';
                echo '<strong>College Name:</strong> ' . esc_html($submission->college_name) . '<br>';
                //echo '<strong>Job:</strong> ' . esc_html($submission->job) . '<br>';
                echo '<a href="' . esc_url($submission->resume_url) . '" target="_blank">View Resume</a><br>';
                echo '<strong>Photo:</strong> <a href="' . esc_url($submission->photo_url) . '" target="_blank">View Photo</a><br>';
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        } else {
            echo '<p>No submissions found.</p>';
        }
        ?>
    </div>
    <style>
        .form-data-container {
            margin: 20px;
        }
        .search-form input[type="text"] {
            padding: 10px;
            margin-right: 10px;
        }
        .search-form input[type="submit"] {
            padding: 10px;
        }
        .submitted-details ul {
            list-style: none;
            padding: 0;
        }
        .submitted-details li {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('display_form_data', 'display_form_data_shortcode');
 
add_action('phpmailer_init', 'configure_smtp');
 
function configure_smtp($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.gmail.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = 587;
    $phpmailer->Username   = 'sonamenothil05@gmail.com';
    $phpmailer->Password   = 'ffwn svhy pynz ovpk';
    $phpmailer->SMTPSecure = 'tls';
    $phpmailer->From       = 'sonamenothil05@gmail.com';
    $phpmailer->FromName   = 'Sona M S';
}
 
function add_dashboard_menu_items() {
    add_menu_page(
        'Contact Form',
        'Contact Form',
        'manage_options',
        'contact_form_page',
        'display_contact_form_page',
        'dashicons-email',
        25
    );
}
add_action('admin_menu', 'add_dashboard_menu_items');
 
function display_contact_form_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'forms';
    $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submission_date DESC"); ?>
    <div class="form-data-container">
        <h1> Form Submissions</h1>
        <?php if (!empty($submissions)) : ?>
            <table class="submissions-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Submission Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission) : ?>
                        <tr onclick="toggleDetails(this)">
                            <td><?php echo esc_html($submission->name); ?></td>
                            <td><?php echo esc_html($submission->submission_date); ?></td>
                        </tr>
                        <tr class="hidden details-row">
                            <td colspan="2">
                                <strong>Email:</strong> <?php echo esc_html($submission->email); ?><br>
                                <strong>Phone:</strong> <?php echo esc_html($submission->phone); ?><br>
                                <strong>Graduation Year:</strong> <?php echo esc_html($submission->graduation_year); ?><br>
                                <strong>Degree:</strong> <?php echo esc_html($submission->degree); ?><br>
                                <strong>College Name:</strong> <?php echo esc_html($submission->college_name); ?><br>
                                <strong>Resume:</strong> <a href="<?php echo esc_url($submission->resume_url); ?>" target="_blank">View Resume</a><br>
                                <strong>Photo:</strong> <a href="<?php echo esc_url($submission->photo_url); ?>" target="_blank">View Photo</a><br>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No form submissions found.</p>
        <?php endif; ?>
    </div>
    <style>
        .form-data-container {
            margin: 20px;
            font-family: 'Times New Roman', Times, serif;
        }
        .submissions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 15px;
        }
        .submissions-table th, .submissions-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .submissions-table th {
            background-color: lightgray;
            font-weight: bold;
        }
        .submissions-table tr:hover {
            background-color: lightsteelblue;
            cursor: pointer;
        }
        .hidden {
            display: none;
        }
      .details-row td {
    background-color: #f9f9f9;
    border-bottom: 1px solid #ddd;
    padding: 15px;
    font-size: 14px;
    line-height: 1.4;
}
 
.details-row td strong {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
 
.details-row td a {
    color: #007bff;
    text-decoration: none;
}
 
.details-row td a:hover {
    text-decoration: underline;
}
 
    </style>
    <script>
        function toggleDetails(row) {
            const nextRow = row.nextElementSibling;
            if (nextRow && nextRow.classList.contains('details-row')) {
                nextRow.classList.toggle('hidden');
            }
        }
    </script>
    <?php
}
 
register_activation_hook(__FILE__, 'create_custom_user_table');
 
function create_custom_user_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'jobplugin_users';
    $charset_collate = $wpdb->get_charset_collate();
 
    $sql = "CREATE TABLE $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";
 
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
 
function registration_form_shortcode() {
    ob_start();
    ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <input type="hidden" name="action" value="jobplugin_register_user">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <input type="submit" value="Register">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('display_registration_form', 'registration_form_shortcode');
 
function jobplugin_register_user() {
    global $wpdb;
 
    if (isset($_POST['username'], $_POST['email'], $_POST['password'])) {
        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
 
        $confirm_password = $_POST['confirm_password'];
 
        // Check if passwords match
        if ($password !== $confirm_password) {
            wp_redirect(add_query_arg('registration_error', 'Passwords do not match.', $_SERVER['HTTP_REFERER']));
            exit;
        }
 
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
 
        // Insert user into the custom table
        $table_name = $wpdb->prefix . 'jobplugin_users';
        $wpdb->insert(
            $table_name,
            [
                'username' => $username,
                'email' => $email,
                'password' => $hashed_password,
            ],
            ['%s', '%s', '%s']
        );
 
        wp_redirect(add_query_arg('registration_success', 'true', $_SERVER['HTTP_REFERER']));
        exit;
    }
 
    wp_redirect(add_query_arg('registration_error', 'Please complete all fields.', $_SERVER['HTTP_REFERER']));
    exit;
}
add_action('admin_post_nopriv_jobplugin_register_user', 'jobplugin_register_user');
add_action('admin_post_jobplugin_register_user', 'jobplugin_register_user');
function login_form_shortcode() {
    ob_start();
    ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <input type="hidden" name="action" value="jobplugin_login_user">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <input type="submit" value="Login">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('display_login_form', 'login_form_shortcode');
 
function jobplugin_login_user() {
    global $wpdb;
 
    if (isset($_POST['username'], $_POST['password'])) {
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
 
        $table_name = $wpdb->prefix . 'jobplugin_users';
        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE username = %s", $username));
 
        if ($user && password_verify($password, $user->password)) {
            setcookie('jobplugin_logged_in', $user->username, time() + 3600, '/');
            wp_redirect(home_url());
            exit;
        } else {
            wp_redirect(add_query_arg('login_error', 'Invalid username or password.', $_SERVER['HTTP_REFERER']));
            exit;
        }
    }
    wp_redirect(add_query_arg('login_error', 'Please enter valid login credentials.', $_SERVER['HTTP_REFERER']));
    exit;
}
add_action('admin_post_nopriv_jobplugin_login_user', 'jobplugin_login_user');
add_action('admin_post_jobplugin_login_user', 'jobplugin_login_user');
 
 
function user_profile_extended_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>You need to log in to access your profile. <a href="' . wp_login_url() . '">Login</a></p>';
    }
 
    $current_user = wp_get_current_user();
    $profile_photo = get_user_meta($current_user->ID, 'profile_photo', true);
    $education = get_user_meta($current_user->ID, 'education', true);
    $experience = get_user_meta($current_user->ID, 'experience', true);
    $linkedin = get_user_meta($current_user->ID, 'linkedin', true);
    $github = get_user_meta($current_user->ID, 'github', true);
 
    // New Job Preference fields
    $location = get_user_meta($current_user->ID, 'location', true);
    $job_type = get_user_meta($current_user->ID, 'job_type', true);
    $salary_expectation = get_user_meta($current_user->ID, 'salary_expectation', true);
 
    // Success message after profile update
    $success_message = isset($_GET['profile_updated']) ? '<p style="color: green;">Profile updated successfully!</p>' : '';
 
    ob_start(); ?>
    <div class="profile-container">
        <h2>Your Profile</h2>
        <?php echo $success_message; ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="process_extended_profile_update">
            <?php wp_nonce_field('extended_profile_update_nonce', 'profile_nonce'); ?>
 
            <!-- Profile Photo -->
            <label for="profile_photo">Profile Photo:</label>
            <?php if ($profile_photo): ?>
                <img src="<?php echo esc_url($profile_photo); ?>" alt="Profile Photo" style="max-width: 150px; display: block; margin-bottom: 10px;">
            <?php endif; ?>
            <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
            
            <!-- Basic Details -->
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>">
            
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>">
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" required>
            
            <label for="description">Bio:</label>
            <textarea id="description" name="description"><?php echo esc_textarea($current_user->description); ?></textarea>
            
            <!-- Education -->
            <label for="education">Education:</label>
            <textarea id="education" name="education"><?php echo esc_textarea($education); ?></textarea>
            
            <!-- Experience -->
            <label for="experience">Experience:</label>
            <textarea id="experience" name="experience"><?php echo esc_textarea($experience); ?></textarea>
            
            <!-- LinkedIn -->
            <label for="linkedin">LinkedIn Profile:</label>
            <input type="url" id="linkedin" name="linkedin" value="<?php echo esc_url($linkedin); ?>" placeholder="https://linkedin.com/in/your-profile">
            
            <!-- GitHub -->
            <label for="github">GitHub Profile:</label>
            <input type="url" id="github" name="github" value="<?php echo esc_url($github); ?>" placeholder="https://github.com/your-profile">
 
            <!-- Job Preferences -->
            <label for="location">Preferred Job Location:</label>
            <input type="text" id="location" name="location" value="<?php echo esc_attr($location); ?>" placeholder="City, Country">
            
            <label for="job_type">Preferred Job Type:</label>
            <select id="job_type" name="job_type">
                <option value="Full-Time" <?php selected($job_type, 'Full-Time'); ?>>Full-Time</option>
                <option value="Part-Time" <?php selected($job_type, 'Part-Time'); ?>>Part-Time</option>
                <option value="Freelance" <?php selected($job_type, 'Freelance'); ?>>Freelance</option>
                <option value="Contract" <?php selected($job_type, 'Contract'); ?>>Contract</option>
            </select>
            
            <label for="salary_expectation">Salary Expectation:</label>
            <input type="text" id="salary_expectation" name="salary_expectation" value="<?php echo esc_attr($salary_expectation); ?>" placeholder="e.g., $50,000 per year">
 
            <input type="submit" value="Update Profile">
        </form>
    </div>
 
    <style>
        .profile-container {
            background-color: white;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            max-width: 600px;
            margin: 0 auto;
        }
 
        .profile-container h2 {
            text-align: center;
        }
 
        .profile-container label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }
 
        .profile-container input[type="text"],
        .profile-container input[type="email"],
        .profile-container input[type="url"],
        .profile-container textarea,
        .profile-container select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
 
        .profile-container input[type="submit"] {
            background-color: navy;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
    <?php return ob_get_clean();
}
add_shortcode('user_profile', 'user_profile_extended_shortcode');
 
function process_extended_profile_update() {
    if (!is_user_logged_in() || !isset($_POST['profile_nonce']) || !wp_verify_nonce($_POST['profile_nonce'], 'extended_profile_update_nonce')) {
        wp_redirect(add_query_arg('profile_error', 'Invalid request.', wp_get_referer()));
        exit;
    }
 
    $current_user_id = get_current_user_id();
 
    // Sanitize and update user meta
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $description = sanitize_textarea_field($_POST['description']);
    $education = sanitize_textarea_field($_POST['education']);
    $experience = sanitize_textarea_field($_POST['experience']);
    $linkedin = esc_url_raw($_POST['linkedin']);
    $github = esc_url_raw($_POST['github']);
 
    // New job preferences
    $location = sanitize_text_field($_POST['location']);
    $job_type = sanitize_text_field($_POST['job_type']);
    $salary_expectation = sanitize_text_field($_POST['salary_expectation']);
 
    if (!is_email($email)) {
        wp_redirect(add_query_arg('profile_error', 'Invalid email address.', wp_get_referer()));
        exit;
    }
 
    // Handle profile photo upload
    if (!empty($_FILES['profile_photo']['name'])) {
        $file = $_FILES['profile_photo'];
        $upload = wp_handle_upload($file, ['test_form' => false]);
 
        if (isset($upload['url'])) {
            // Save the uploaded file URL in the user meta
            update_user_meta($current_user_id, 'profile_photo', esc_url($upload['url']));
        } else {
            wp_redirect(add_query_arg('profile_error', 'Error uploading photo.', wp_get_referer()));
            exit;
        }
    }
 
    // Update user data
    $update_data = [
        'ID'         => $current_user_id,
        'user_email' => $email,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'description'=> $description,
    ];
 
    $result = wp_update_user($update_data);
 
    if (is_wp_error($result)) {
        wp_redirect(add_query_arg('profile_error', 'Error updating profile.', wp_get_referer()));
        exit;
    }
 
    // Update additional fields
    update_user_meta($current_user_id, 'education', $education);
    update_user_meta($current_user_id, 'experience', $experience);
    update_user_meta($current_user_id, 'linkedin', $linkedin);
    update_user_meta($current_user_id, 'github', $github);
 
    // Save job preferences
    update_user_meta($current_user_id, 'location', $location);
    update_user_meta($current_user_id, 'job_type', $job_type);
    update_user_meta($current_user_id, 'salary_expectation', $salary_expectation);
 
    wp_redirect(add_query_arg('profile_updated', 'true', wp_get_referer()));
    exit;
}
add_action('admin_post_process_extended_profile_update', 'process_extended_profile_update');
add_action('admin_post_nopriv_process_extended_profile_update', 'process_extended_profile_update');
 
function display_user_profile_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>You need to log in to view your profile. <a href="' . wp_login_url() . '">Login</a></p>';
    }
 
    $current_user = wp_get_current_user();
    $profile_photo = get_user_meta($current_user->ID, 'profile_photo', true);
    $education = get_user_meta($current_user->ID, 'education', true);
    $experience = get_user_meta($current_user->ID, 'experience', true);
    $linkedin = get_user_meta($current_user->ID, 'linkedin', true);
    $github = get_user_meta($current_user->ID, 'github', true);
 
    // New job preference fields
    $location = get_user_meta($current_user->ID, 'location', true);
    $job_type = get_user_meta($current_user->ID, 'job_type', true);
    $salary_expectation = get_user_meta($current_user->ID, 'salary_expectation', true);
 
    ob_start(); ?>
    <div class="user-profile-container">
        <h2><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?>'s Profile</h2>
        
        <!-- Profile Photo -->
        <div class="profile-photo">
            <?php if ($profile_photo): ?>
                <img src="<?php echo esc_url($profile_photo); ?>" alt="Profile Photo" style="max-width: 150px; border-radius: 50%;">
            <?php else: ?>
                <p><em>No profile photo uploaded.</em></p>
            <?php endif; ?>
        </div>
        
        <!-- Basic Info -->
        <p><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
        <p><strong>Bio:</strong> <?php echo nl2br(esc_html($current_user->description)); ?></p>
        
        <!-- Education -->
        <p><strong>Education:</strong> <?php echo nl2br(esc_html($education ?: 'No education details provided.')); ?></p>
        
        <!-- Experience -->
        <p><strong>Experience:</strong> <?php echo nl2br(esc_html($experience ?: 'No experience details provided.')); ?></p>
        
        <!-- Job Preferences -->
        <p><strong>Preferred Job Location:</strong> <?php echo esc_html($location ?: 'Not provided'); ?></p>
        <p><strong>Preferred Job Type:</strong> <?php echo esc_html($job_type ?: 'Not provided'); ?></p>
        <p><strong>Salary Expectation:</strong> <?php echo esc_html($salary_expectation ?: 'Not provided'); ?></p>
        
        <!-- Social Links -->
        <p><strong>LinkedIn:</strong>
            <?php if ($linkedin): ?>
                <a href="<?php echo esc_url($linkedin); ?>" target="_blank"><?php echo esc_html($linkedin); ?></a>
            <?php else: ?>
                <em>Not provided</em>
            <?php endif; ?>
        </p>
        
        <p><strong>GitHub:</strong>
            <?php if ($github): ?>
                <a href="<?php echo esc_url($github); ?>" target="_blank"><?php echo esc_html($github); ?></a>
            <?php else: ?>
                <em>Not provided</em>
            <?php endif; ?>
        </p>
    </div>
 
    <style>
        .user-profile-container {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }
 
        .user-profile-container h2 {
            margin-bottom: 20px;
        }
 
        .profile-photo img {
            display:flex;
            margin: 0 auto 10px;
        }
 
        .user-profile-container p {
            margin: 10px 0;
        }
 
        .user-profile-container a {
            color: #0073aa;
            text-decoration: none;
        }
 
        .user-profile-container a:hover {
            text-decoration: underline;
        }
    </style>
    <?php return ob_get_clean();
}
add_shortcode('display_user_profile', 'display_user_profile_shortcode');
 
 
 
 
 
 