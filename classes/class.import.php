<?php
/**
 * Class RPT_to_P2P
 * This tools allows you to transfer post types relations set with "Relations Post Types" to "Posts To Posts"
 */
class RPT_to_P2P {
	protected $post_type1 = '';
	protected $post_type2 = '';
	protected $relation = '';
	protected $delete = false;

	/**
	 * Init process
	 *
	 * @param $relation
	 * @param $post_type1
	 * @param $post_type2
	 * @param $delete
	 */
	public function __construct( $relation, $post_type1, $post_type2, $delete = false ) {
		$this->relation   = (string) $relation;
		$this->post_type1 = (string) $post_type1;
		$this->post_type2 = (string) $post_type2;
		$this->delete     = (boolean) $delete;

		if ( ! function_exists( 'p2p_type' ) ) {
			die( 'P2P is not installed or it\'s there but not activated !' );
		}

		// Transfer data
		$this->transfer_data( $this->relation, $post_type1, $post_type2 );
		
		if ( $this->delete === true ) {
			$this->delete_rpt_table();
		}
	}


	/**
	 * Get data from table RPT
	 *
	 * @author Julien Maury
	 * @return boolean
	 */
	public function has_rpt_data() {
		global $wpdb;
		$results = $wpdb->get_var(
			"
              SELECT COUNT(id)
              FROM {$wpdb->posts_relations}
			"
		);

		return (int) $results > 0 ? true : false;

	}


	/**
	 * This method allow to get items of one content type which have relation with a another post type... The first arg is the post type use for return IDs...
	 *
	 * @param string $return_post_type
	 * @param string $comparaison_post_type
	 *
	 * @return array|boolean
	 * @author Amaury Balmer
	 */
	public function get_objects_with_relations( $return_post_type = '', $comparaison_post_type = '' ) {
		global $wpdb;

		// Get IDs for both post type
		$ids1 = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", $return_post_type ) );
		$ids2 = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", $comparaison_post_type ) );

		// Build SQL Where
		$where = '';
		foreach ( $ids1 as $id1 ) {
			foreach ( $ids2 as $id2 ) {
				$where .= " OR ( (object_id_1 = $id1 AND object_id_2 = $id2) OR (object_id_1 = $id2 AND object_id_2 = $id1) ) ";
			}
		}

		// Get ID of relations
		$results = $wpdb->get_results("
			SELECT DISTINCT *
			FROM $wpdb->posts_relations WHERE (
				object_id_1 IN (" . implode( ',', $ids1 ) . ") AND object_id_2 IN (" . implode( ',', $ids2 ) . ")
			)
			OR (
				object_id_1 IN (" . implode( ',', $ids2 ) . ") AND object_id_2 IN (" . implode( ',', $ids1 ) . ")
			)");

		return (array) $results;
	}

	/**
	 * Transfer from RPT to P2P
	 *
	 * @param string $relation
	 * @param string $post_type1
	 * @param string $post_type2
	 *
	 * @author Julien Maury
	 */
	public function transfer_data( $relation, $post_type1, $post_type2 ) {

		$have_rpt_data = $this->has_rpt_data();
		if ( empty( $have_rpt_data ) ) {
			die( 'There is no RPT data to transfer !' );
		}

		$data_to_transfert = $this->get_objects_with_relations( $post_type1, $post_type2 );
		if( empty( $data_to_transfert ) ) {
			die( 'There is no match !' );
		}

		$i = 0;
		foreach ( (array) $data_to_transfert as $data ) {
			$i++;

			/**
			 * @link https://github.com/scribu/wp-posts-to-posts/wiki/Creating-connections-programmatically
			 * safe to use => 'prevent_duplicates' and 'cardinality'.
			 */
			p2p_type( $relation )->connect( $data->object_id_1, $data->object_id_2 );
		}

		printf('%d RPT links imported into P2P with success !', $i);
	}

	/**
	 * Delete table after usage
	 * Be careful with this, be sure to have completed all transfers
	 *
	 * @author Julien Maury
	 */
	protected function delete_rpt_table() {
		global $wpdb;
		
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->posts_relations}");
	}

}
