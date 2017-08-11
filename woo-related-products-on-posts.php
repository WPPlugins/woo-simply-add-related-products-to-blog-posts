<?php
/*
Plugin Name: Woo Related Products on Posts
Plugin URI: https://said.solutions/
Description: Allows Display of Related WooCommerce Products on Posts.
Author: Matt Shirk
Version: 2.5
Author URI: https://said.solutions/
*/


// Remove bad stuff from any string entered as a color

function wrpp_xss_strip($wrpp_input) {
	$wrpp_input = strip_tags($wrpp_input);
	$wrpp_input = htmlspecialchars($wrpp_input);
	$wrpp_input = preg_replace("/['\"&()]<>/","",$wrpp_input);
	return $wrpp_input; 
}

// Get all products from WooCommerce DB

global $wpdb;
$wrpp_tnme = $wpdb->prefix . 'posts';
$wrpp_relateddProds = $wpdb->get_results( "SELECT post_title, ID 
	FROM $wrpp_tnme 
	WHERE post_type = 'product' 
	AND post_title != 'Auto Draft' 
	GROUP BY ID ASC", OBJECT );


// Adds the Related Product Selection meta box in the Blog Post Editor page

function wrpp_custom_metaaa() {
	add_meta_box( 'wrpp_metaaa', __( 'Add WooCommerce Related Products to Bottom of This Post', 'prfx-textdomain' ), 'wrpp_metaaa_callback', 'post' );
}
add_action( 'add_meta_boxes', 'wrpp_custom_metaaa',9999 );


// Outputs the Related Product Selection meta box in the Blog Post Editor page

function wrpp_metaaa_callback( $post ) {

	wp_nonce_field( basename( __FILE__ ), 'prfx_nonce' );
	$wrpp_stored_meta = get_post_meta( $post->ID ); 

	// Output HTML for selecting Related Products on the Blog Post Editor page
	?>
	
	<p>
		<span class="prfx-row-title"><?php _e( 'Select Related Products that will appear at the bottom of this post', 'prfx-textdomain' )?></span>
		<div class="prfx-row-content">

			<?php
			$wrpp_productCount = 0;
			global $wrpp_relateddProds;
			foreach ($wrpp_relateddProds as $wrpp_relateddProd) {
				$wrpp_prodpostid = $wrpp_relateddProd->ID;
				$wrpp_prodposttitle = $wrpp_relateddProd->post_title;
				$wrpp_prodposttitle = wrpp_xss_strip($wrpp_prodposttitle);
				$wrpp_productCount++;
				$wrpp_itemnm = 'wrpp-meta-checkbox' . $wrpp_productCount;
				?>
				<div style="display:block;margin:0px auto;height:20px;vertical-align:top;">	
					<label class="relprodsadmin" for="<?php echo $wrpp_itemnm;?>">
						<input type="checkbox" name="<?php echo $wrpp_itemnm;?>" id="<?php echo $wrpp_itemnm;?>" value="yes" <?php if ( isset ( $wrpp_stored_meta[$wrpp_itemnm] ) ) checked( $wrpp_stored_meta[$wrpp_itemnm][0], $wrpp_prodpostid ); ?> />
						<?php _e( $wrpp_prodposttitle, 'prfx-textdomain' )?>
					</label>
				</div>
				<?php } ?>

			</div>
		</p>

		<?php }

	// This Function saves the custom meta input on blog post editor page

		function wrpp_metadata_save( $post_id ) {

	// Checks save status
			$wrpp_is_autosave = wp_is_post_autosave( $post_id );
			$wrpp_is_revision = wp_is_post_revision( $post_id );
			$wrpp_is_valid_nonce = ( isset( $_POST[ 'prfx_nonce' ] ) && wp_verify_nonce( $_POST[ 'prfx_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';

	// Exits script depending on save status
			if ( $wrpp_is_autosave || $wrpp_is_revision || !$wrpp_is_valid_nonce ) {
				return;
			}

	// Checks for input and sanitizes/saves if needed
			if( isset( $_POST[ 'meta-text' ] ) ) {
				update_post_meta( $post_id, 'meta-text', sanitize_text_field( $_POST[ 'meta-text' ] ) );
			}

	// gets selected products
			$wrpp_productCountz = 0;
			global $wrpp_relateddProds;
			foreach ($wrpp_relateddProds as $wrpp_relateddProd) {
				$wrpp_rr = $wrpp_relateddProd->ID;
				$wrpp_productCountz++;
				$wrpp_itemnmz = 'wrpp-meta-checkbox' . $wrpp_productCountz;
	// Checks for input and saves
				if( isset( $_POST[ $wrpp_itemnmz ] ) ) {
					update_post_meta( $post_id, $wrpp_itemnmz, $wrpp_rr );
				} else {
					update_post_meta( $post_id, $wrpp_itemnmz, '' );
				}

			}

		}
		add_action( 'save_post', 'wrpp_metadata_save' );


			// Define the woocommerce_after_main_content callback
		function wrpp_displayProducts($wrpp_content) {

			// Checks to make sure this is a post and nothing else
			if ( is_singular() && !is_archive() && !is_cart() && !is_checkout() && !is_account_page()  )  {
				$wrpp_productCountzz = 0;
				global $wrpp_relateddProds;
				$wrpp_countter = 0;
				$wrpp_contenta = '';
				foreach ($wrpp_relateddProds as $wrpp_relateddProd) {
					$wrpp_productCountzz++;
					$wrpp_id = $wrpp_relateddProd->ID;
					$wrpp_itemnmzz = 'wrpp-meta-checkbox' . $wrpp_productCountzz;
					$wrpp_key_value = get_post_meta( get_the_ID(), $wrpp_itemnmzz, true );
					$wrpp_add_to_cart = do_shortcode('[add_to_cart id="' . $wrpp_id .'"]');

			// Check if the custom field has a value.
					if ( !empty( $wrpp_key_value ) ) {
						$wrpp_tit = get_the_title($wrpp_id);
						$wrpp_tit = wrpp_xss_strip($wrpp_tit);
						$wrpp_img = get_the_post_thumbnail( $wrpp_id, 'thumbnail',array('title' => '' . $wrpp_tit . '','alt' => '' . $wrpp_tit . '') );
						$wrpp_link = get_permalink($wrpp_id); 
						$wrpp_countter++;
						$wrpp_contenta = $wrpp_contenta . '<div class="relprods" style="display:inline-block;">
						<div class="prodImg"><a href="' . $wrpp_link . '">' . $wrpp_img . '</a></div>' . $wrpp_add_to_cart . '</div>';
					}

				}

			// First check if there are Products and then check if there are Related Products for this blog post
				if ($wrpp_productCountzz > 0) {
					if ($wrpp_countter > 0) {
						/*$wrpp_csswidth = 100/$wrpp_countter;
						$wrpp_csswidthb = $wrpp_csswidth * 0.25;
						$wrpp_csswidth = $wrpp_csswidth - $wrpp_csswidthb;
						$wrpp_csswidthb = $wrpp_csswidthb/2;*/
						$wrpp_styles = '<style>
						div.prodImg{
							min-height:100px;
						}
						div.relprods span{
							display:none !important;
						}
						div.relprods p{
							border:0px !important;
						}
						div.relprods {
							width: auto;
							margin: 0px 2.5%;
						}
						div.relprods a {
							text-align:center;
							display:block !important;
						}
						h2.relprodtit{
							text-align: center;
							border-bottom: 1px solid;
							margin-bottom: 20px;
							padding-bottom: 10px;
						}
						div.relprodscontainer {
							text-align: center;
							border-bottom: 1px solid;
							padding-bottom: 20px;
						}
					</style>'; } else {
						$wrpp_styles = '<style></style>';
					}

					if ($wrpp_countter > 0) {
						$wrpp_content = $wrpp_content . '<div class="relprodscontainer"><h2 class="relprodtit">Related Products:</h2>' . $wrpp_contenta . '</div>' . $wrpp_styles;}
						return $wrpp_content;
				}

			} elseif(is_archive()){

				echo strip_tags(substr($wrpp_content, 0,500)) . '.....<br>';
			}

			else  {
				return $wrpp_content;

			}

		}

// add the action that displays the related products after the content
		add_filter( 'the_content', 'wrpp_displayProducts', 999999, 2 );


//Adds the meta box stylesheet when appropriate
function wrpp_admin_stylessss(){
	global $wrpp_typenow;
	if( $wrpp_typenow == 'post' ) {
		wp_enqueue_style( 'wrpp_metaaa_box_styles', plugin_dir_url( __FILE__ ) . 'meta-box-styles.css' );
	}
}
add_action( 'admin_print_styles', 'wrpp_admin_stylessss' ); ?>