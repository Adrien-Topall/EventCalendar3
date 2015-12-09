<?php 
/************************************************************************
// Création du type de post 'oeuvre'
*************************************************************************/

function creation_type_ec3(){
	$labels = array(
		'name'               => 'event',
		'singular_name'      => 'event',
		'menu_name'          => 'event',
		'name_admin_bar'     => 'event',
		'add_new'            => 'Ajouter',
		'add_new_item'       => 'Ajouter un event',
		'new_item'           => 'Nouvelle event',
		'edit_item'          => 'Modifier l\' event',
		'view_item'          => 'Voir l\'event',
		'all_items'          => 'Tout les events',
		'search_items'       => 'Rechercher un event',
		// 'parent_item_colon'  => __( 'Parent Books:', 'your-plugin-textdomain' ),
		'not_found'          => 'Aucun event trouvé',
		'not_found_in_trash' => 'Aucun event dans la corbeille'
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'page-event' ),
		'capability_type'    => 'post',
		'has_archive'        => false, // Si true, la liste des autos est visible sur {url_du_site}/accueil
		'hierarchical'       => false,
		'menu_position'      => 6,
		'menu_icon'      	 => 'dashicons-calendar-alt',
		'supports'           => array( 'title', 'editor', 'thumbnail' )
	);

	register_post_type('poste_ec3',$args);
}
add_action('init','creation_type_ec3');





