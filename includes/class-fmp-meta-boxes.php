<?php
/**
 * Meta boxes for CPTs (Animals in Phase 1).
 *
 * @package Farm_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FMP_Meta_Boxes
 */
class FMP_Meta_Boxes {

	/**
	 * Register meta boxes.
	 */
	public static function register() {
		add_meta_box(
			'fmp_animal_details',
			__( 'Animal details', 'farm-management' ),
			array( __CLASS__, 'render_animal_meta_box' ),
			'fmp_animal',
			'normal',
			'default'
		);
		add_meta_box(
			'fmp_crop_details',
			__( 'Crop details', 'farm-management' ),
			array( __CLASS__, 'render_crop_meta_box' ),
			'fmp_crop',
			'normal',
			'default'
		);
	}

	/**
	 * Render Animal details meta box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public static function render_animal_meta_box( $post ) {
		wp_nonce_field( 'fmp_save_animal', 'fmp_animal_nonce' );

		$tag          = get_post_meta( $post->ID, '_fmp_tag', true );
		$species      = get_post_meta( $post->ID, '_fmp_species', true );
		$breed        = get_post_meta( $post->ID, '_fmp_breed', true );
		$sex          = get_post_meta( $post->ID, '_fmp_sex', true );
		$date_of_birth = get_post_meta( $post->ID, '_fmp_date_of_birth', true );
		$acquired_date = get_post_meta( $post->ID, '_fmp_acquired_date', true );
		$status       = get_post_meta( $post->ID, '_fmp_status', true );
		$weight       = get_post_meta( $post->ID, '_fmp_weight', true );
		$notes        = get_post_meta( $post->ID, '_fmp_notes', true );
		?>
		<div class="fmp-meta-box fmp-animal-details">
			<div class="fmp-meta-row">
				<label for="fmp_tag"><?php esc_html_e( 'Tag/ID', 'farm-management' ); ?></label>
				<input type="text" id="fmp_tag" name="fmp_tag" value="<?php echo esc_attr( $tag ); ?>" class="regular-text" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_species"><?php esc_html_e( 'Species', 'farm-management' ); ?></label>
				<input type="text" id="fmp_species" name="fmp_species" value="<?php echo esc_attr( $species ); ?>" class="regular-text" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_breed"><?php esc_html_e( 'Breed', 'farm-management' ); ?></label>
				<input type="text" id="fmp_breed" name="fmp_breed" value="<?php echo esc_attr( $breed ); ?>" class="regular-text" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_sex"><?php esc_html_e( 'Sex', 'farm-management' ); ?></label>
				<select id="fmp_sex" name="fmp_sex">
					<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
					<option value="male" <?php selected( $sex, 'male' ); ?>><?php esc_html_e( 'Male', 'farm-management' ); ?></option>
					<option value="female" <?php selected( $sex, 'female' ); ?>><?php esc_html_e( 'Female', 'farm-management' ); ?></option>
					<option value="other" <?php selected( $sex, 'other' ); ?>><?php esc_html_e( 'Other', 'farm-management' ); ?></option>
				</select>
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_date_of_birth"><?php esc_html_e( 'Date of birth', 'farm-management' ); ?></label>
				<input type="date" id="fmp_date_of_birth" name="fmp_date_of_birth" value="<?php echo esc_attr( $date_of_birth ); ?>" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_acquired_date"><?php esc_html_e( 'Acquired date', 'farm-management' ); ?></label>
				<input type="date" id="fmp_acquired_date" name="fmp_acquired_date" value="<?php echo esc_attr( $acquired_date ); ?>" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_status"><?php esc_html_e( 'Status', 'farm-management' ); ?></label>
				<select id="fmp_status" name="fmp_status">
					<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
					<option value="alive" <?php selected( $status, 'alive' ); ?>><?php esc_html_e( 'Alive', 'farm-management' ); ?></option>
					<option value="sold" <?php selected( $status, 'sold' ); ?>><?php esc_html_e( 'Sold', 'farm-management' ); ?></option>
					<option value="dead" <?php selected( $status, 'dead' ); ?>><?php esc_html_e( 'Dead', 'farm-management' ); ?></option>
				</select>
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_weight"><?php esc_html_e( 'Weight (kg)', 'farm-management' ); ?></label>
				<input type="number" id="fmp_weight" name="fmp_weight" value="<?php echo esc_attr( $weight ); ?>" step="0.01" min="0" class="small-text" />
			</div>
			<div class="fmp-meta-row fmp-meta-row-full">
				<label for="fmp_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label>
				<textarea id="fmp_notes" name="fmp_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Save Animal meta on save_post_fmp_animal.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_animal_meta( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['fmp_animal_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_animal_nonce'] ) ), 'fmp_save_animal' ) ) {
			return;
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			return;
		}

		$fields = array(
			'fmp_tag'           => 'sanitize_text_field',
			'fmp_species'       => 'sanitize_text_field',
			'fmp_breed'         => 'sanitize_text_field',
			'fmp_sex'           => 'sanitize_text_field',
			'fmp_date_of_birth' => 'sanitize_text_field',
			'fmp_acquired_date' => 'sanitize_text_field',
			'fmp_status'        => 'sanitize_text_field',
			'fmp_weight'        => 'sanitize_text_field',
			'fmp_notes'         => 'sanitize_textarea_field',
		);

		$meta_map = array(
			'fmp_tag'           => '_fmp_tag',
			'fmp_species'       => '_fmp_species',
			'fmp_breed'         => '_fmp_breed',
			'fmp_sex'           => '_fmp_sex',
			'fmp_date_of_birth' => '_fmp_date_of_birth',
			'fmp_acquired_date' => '_fmp_acquired_date',
			'fmp_status'        => '_fmp_status',
			'fmp_weight'        => '_fmp_weight',
			'fmp_notes'         => '_fmp_notes',
		);

		foreach ( $fields as $input => $sanitize_cb ) {
			if ( ! isset( $_POST[ $input ] ) ) {
				continue;
			}
			$value = call_user_func( $sanitize_cb, wp_unslash( $_POST[ $input ] ) );
			$meta_key = $meta_map[ $input ];
			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	/**
	 * Render Crop details meta box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public static function render_crop_meta_box( $post ) {
		wp_nonce_field( 'fmp_save_crop', 'fmp_crop_nonce' );

		$crop_name            = get_post_meta( $post->ID, '_fmp_crop_name', true );
		$crop_type            = get_post_meta( $post->ID, '_fmp_crop_type', true );
		$planting_date        = get_post_meta( $post->ID, '_fmp_planting_date', true );
		$expected_harvest     = get_post_meta( $post->ID, '_fmp_expected_harvest_date', true );
		$field_location       = get_post_meta( $post->ID, '_fmp_field_location', true );
		$status               = get_post_meta( $post->ID, '_fmp_crop_status', true );
		$notes                = get_post_meta( $post->ID, '_fmp_crop_notes', true );

		if ( $crop_name === '' && $post->post_title !== '' ) {
			$crop_name = $post->post_title;
		}
		?>
		<div class="fmp-meta-box fmp-crop-details">
			<div class="fmp-meta-row">
				<label for="fmp_crop_name"><?php esc_html_e( 'Crop name', 'farm-management' ); ?></label>
				<input type="text" id="fmp_crop_name" name="fmp_crop_name" value="<?php echo esc_attr( $crop_name ); ?>" class="regular-text" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_crop_type"><?php esc_html_e( 'Crop type', 'farm-management' ); ?></label>
				<select id="fmp_crop_type" name="fmp_crop_type">
					<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
					<option value="veg" <?php selected( $crop_type, 'veg' ); ?>><?php esc_html_e( 'Vegetable', 'farm-management' ); ?></option>
					<option value="fruit" <?php selected( $crop_type, 'fruit' ); ?>><?php esc_html_e( 'Fruit', 'farm-management' ); ?></option>
					<option value="grain" <?php selected( $crop_type, 'grain' ); ?>><?php esc_html_e( 'Grain', 'farm-management' ); ?></option>
				</select>
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_planting_date"><?php esc_html_e( 'Planting date', 'farm-management' ); ?></label>
				<input type="date" id="fmp_planting_date" name="fmp_planting_date" value="<?php echo esc_attr( $planting_date ); ?>" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_expected_harvest_date"><?php esc_html_e( 'Expected harvest date', 'farm-management' ); ?></label>
				<input type="date" id="fmp_expected_harvest_date" name="fmp_expected_harvest_date" value="<?php echo esc_attr( $expected_harvest ); ?>" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_field_location"><?php esc_html_e( 'Field / location', 'farm-management' ); ?></label>
				<input type="text" id="fmp_field_location" name="fmp_field_location" value="<?php echo esc_attr( $field_location ); ?>" class="regular-text" />
			</div>
			<div class="fmp-meta-row">
				<label for="fmp_crop_status"><?php esc_html_e( 'Status', 'farm-management' ); ?></label>
				<select id="fmp_crop_status" name="fmp_crop_status">
					<option value=""><?php esc_html_e( '— Select —', 'farm-management' ); ?></option>
					<option value="planned" <?php selected( $status, 'planned' ); ?>><?php esc_html_e( 'Planned', 'farm-management' ); ?></option>
					<option value="planted" <?php selected( $status, 'planted' ); ?>><?php esc_html_e( 'Planted', 'farm-management' ); ?></option>
					<option value="harvested" <?php selected( $status, 'harvested' ); ?>><?php esc_html_e( 'Harvested', 'farm-management' ); ?></option>
				</select>
			</div>
			<div class="fmp-meta-row fmp-meta-row-full">
				<label for="fmp_crop_notes"><?php esc_html_e( 'Notes', 'farm-management' ); ?></label>
				<textarea id="fmp_crop_notes" name="fmp_crop_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Save Crop meta on save_post_fmp_crop.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_crop_meta( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( get_post_type( $post_id ) !== 'fmp_crop' ) {
			return;
		}
		if ( ! isset( $_POST['fmp_crop_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fmp_crop_nonce'] ) ), 'fmp_save_crop' ) ) {
			return;
		}
		if ( ! current_user_can( FMP_Capabilities::MANAGE_FARM ) ) {
			return;
		}

		// Guard: do not call wp_update_post() inside save_post_* without guards (avoids re-entry loop).
		static $saving = false;
		if ( $saving ) {
			return;
		}
		$saving = true;

		try {
			$crop_name = isset( $_POST['fmp_crop_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_crop_name'] ) ) : '';
			$crop_type = isset( $_POST['fmp_crop_type'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_crop_type'] ) ) : '';
			$planting_date = isset( $_POST['fmp_planting_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_planting_date'] ) ) : '';
			$expected_harvest = isset( $_POST['fmp_expected_harvest_date'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_expected_harvest_date'] ) ) : '';
			$field_location = isset( $_POST['fmp_field_location'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_field_location'] ) ) : '';
			$status = isset( $_POST['fmp_crop_status'] ) ? sanitize_text_field( wp_unslash( $_POST['fmp_crop_status'] ) ) : '';
			$notes = isset( $_POST['fmp_crop_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fmp_crop_notes'] ) ) : '';

			update_post_meta( $post_id, '_fmp_crop_name', $crop_name );
			update_post_meta( $post_id, '_fmp_crop_type', $crop_type );
			update_post_meta( $post_id, '_fmp_planting_date', $planting_date );
			update_post_meta( $post_id, '_fmp_expected_harvest_date', $expected_harvest );
			update_post_meta( $post_id, '_fmp_field_location', $field_location );
			update_post_meta( $post_id, '_fmp_crop_status', $status );
			update_post_meta( $post_id, '_fmp_crop_notes', $notes );

			if ( $crop_name !== '' ) {
				remove_action( 'save_post_fmp_crop', array( __CLASS__, 'save_crop_meta' ), 10 );
				wp_update_post( array(
					'ID'         => $post_id,
					'post_title' => $crop_name,
				) );
				add_action( 'save_post_fmp_crop', array( __CLASS__, 'save_crop_meta' ), 10, 2 );
			}
		} finally {
			$saving = false;
		}
	}

	/**
	 * Set list table columns for fmp_crop.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function crop_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( $key === 'title' ) {
				$new['fmp_crop']   = __( 'Crop', 'farm-management' );
				$new['fmp_location'] = __( 'Location', 'farm-management' );
				$new['fmp_crop_status'] = __( 'Status', 'farm-management' );
				$new['fmp_expected_harvest'] = __( 'Expected Harvest', 'farm-management' );
			} elseif ( $key !== 'date' ) {
				$new[ $key ] = $label;
			}
		}
		return $new;
	}

	/**
	 * Output list table column content for fmp_crop.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function crop_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'fmp_crop':
				$name = get_post_meta( $post_id, '_fmp_crop_name', true );
				if ( $name === '' ) {
					$name = get_the_title( $post_id );
				}
				echo '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">' . esc_html( $name ?: '—' ) . '</a>';
				break;
			case 'fmp_location':
				echo esc_html( get_post_meta( $post_id, '_fmp_field_location', true ) ?: '—' );
				break;
			case 'fmp_crop_status':
				$status = get_post_meta( $post_id, '_fmp_crop_status', true );
				$labels = array(
					'planned'   => __( 'Planned', 'farm-management' ),
					'planted'   => __( 'Planted', 'farm-management' ),
					'harvested' => __( 'Harvested', 'farm-management' ),
				);
				echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : ( $status ?: '—' ) );
				break;
			case 'fmp_expected_harvest':
				echo esc_html( get_post_meta( $post_id, '_fmp_expected_harvest_date', true ) ?: '—' );
				break;
		}
	}
}
