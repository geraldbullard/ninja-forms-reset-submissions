<?php
/**
 * Plugin Name: Ninja Forms 3+ Reset Submissions
 * Plugin URI: https://www.elykinnovation.com
 * Description: Adds a "Reset Submissions" button to the Submissions listing page in Ninja Forms v 3 and higher
 * Version: 1.0.0
 * Author: ELYK Innovation, Inc
 * Author URI: https://www.elykinnovation.com
 * License: GPL2
 */

function admin_head_nf_subs_reset()
{
	$screen = get_current_screen();
	// if we are on the NF Subs page and a Form ID has been selected, and there is no resetform GET
	if (
        $screen->post_type == 'nf_sub' &&
        isset($_GET['form_id']) &&
        $_GET['form_id'] > '0' &&
        !isset($_GET['resetform'])
    ) {
	?>
	<script>
		function resetSubmissions(url) {
		    window.location = url + '&resetform=1';
		}
		jQuery(document).ready(function(){
		    jQuery("#posts-filter .alignleft.actions").append(
		        '<input type="button" id="reset-submission" class="button" value="Reset" onclick="if(confirm(\'Are you sure?\'))resetSubmissions(window.location);">'
			);
		});
	</script>
	<?php
	}
}
add_action('admin_head-edit.php', 'admin_head_nf_subs_reset');

function admin_init_nf_subs_reset()
{
    // If the "Reset" subs button has been cliecked
	if (isset($_GET['resetform']) && $_GET['resetform'] == '1') {
	    global $wpdb;
		// Reset the main form seq_num
		$wpdb->query("
            UPDATE `" . $wpdb->prefix . "nf3_forms` 
            SET `seq_num` = NULL 
            WHERE `id` = " . $_GET['form_id']
        );
		// Remove the form meta values for seq_num
		$wpdb->query("
            DELETE FROM `" . $wpdb->prefix . "nf3_form_meta` 
            WHERE `parent_id` = " . $_GET['form_id'] . " 
            AND `key` = '_seq_num' 
            AND `meta_key` = '_seq_num' 
        ");
		// Get the post id's assocaiated with the form
		$pids = $wpdb->get_results(
			$wpdb->prepare("
                SELECT * FROM `" . $wpdb->prefix . "postmeta` WHERE `meta_key` LIKE '_form_id' AND `meta_value` LIKE " . $_GET['form_id']
            )
        );
		// loop thru and remove those posts, and remove the postmeta entires
		foreach ($pids as $p => $id) {
		    $wpdb->query("
                DELETE FROM `" . $wpdb->prefix . "posts` WHERE `ID` LIKE " . $id->post_id
            );
		    $wpdb->query("
                DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE `meta_id` LIKE " . $id->meta_id
            );
        }
		// Strip "&resetform=1" from the url
		$redirect = str_replace("&resetform=1", "", $_SERVER['REQUEST_URI']);
		// Now redirect
        wp_redirect($redirect);
		exit();
	}
}
add_action('admin_init', 'admin_init_nf_subs_reset');
