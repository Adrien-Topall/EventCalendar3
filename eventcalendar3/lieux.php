<?php 

function cree_page_lieux_content(){

	global $ec3, $wpdb;

	$table_schedule = $wpdb->prefix . 'ec3_schedule';
	$table_lieux = $wpdb->prefix . 'ec3_lieux';
	$table_opt = $wpdb->prefix . 'ec3_add_opt';
	$url = admin_url();

	// En cas de besoin pour faire des moulinette 
	if (isset($_GET['updateBdd'])) {

		

		// réinport des options dans schedule
		/*$status = $wpdb->get_results("SELECT * FROM $table_opt");
	    foreach ($status as $key => $value) {

	    	$wpdb->update( $table_schedule, array( 'option_id' => $value->option_id ), array( 'status' => $value->nom ) );
	    	$wpdb->show_errors();
	    	$wpdb->print_error();
	    	print_r($wpdb->last_error);
	    	print_r($wpdb->last_result);
	    	echo $wpdb->last_query;
	    }*/
	}

	 // enregistre un nouveau lieux envoyer par le formulaire
    if ( isset($_POST['submit_lieu']) && !isset($_POST['edit']) ){

      $departement = $_POST['departement'];
      $ville = $_POST['ville'];
      $lieu = $_POST['lieu'];
      $adresse = $_POST['adresse'];

      $wpdb->insert( $table_lieux, array( 'departement' => $departement, 'nom_ville' => $ville, 'nom_lieux' => $lieu, 'adresse' => $adresse, 'longitude' => '', 'latitude' => '' ), array( '%s', '%s', '%s', '%s', '%f', '%f' ) );
    }

    // modifie un lieux envoyer par le formulaire
    if (isset($_POST['submit_lieu']) && isset($_POST['edit'])) {

      $departement = $_POST['departement'];
      $ville = $_POST['ville'];
      $lieu = $_POST['lieu'];
      $adresse = $_POST['adresse'];
      $lieu_id = $_POST['edit'];

      $wpdb->update( $table_lieux, array( 'departement' => $departement, 'nom_ville' => $ville, 'nom_lieux' => $lieu, 'adresse' => $adresse, 'longitude' => '', 'latitude' => '' ), array( 'lieux_id' => $lieu_id ), array( '%s', '%s', '%s', '%s', '%f', '%f' ) );
    }

	?><h2>Lieux <a href="/wp-admin/admin.php?page=lieux&action=new" class="add-new-h2">ajouter</a>
	<!-- En cas de besoin pour faire des moulinette -->
	
	<!-- <a href="/wp-admin/admin.php?page=lieux&updateBdd=true"><h2>Update</h2></a> -->
	<?php
	if (!empty($_REQUEST["action"])) {
		if ($_REQUEST["action"] == 'new' || $_REQUEST["action"] == 'modif') {
			?><a href="<?php //echo $ec3->myfiles; ?>/wp-admin/admin.php?page=lieux" class="add-new-h2">lister</a><?php
		}
	}
	else{ $_REQUEST["action"] = 'vide'; }
	?></h2><?php

    // Recupere tous les lieux dans la base de donné
    $tous_les_lieux = $wpdb->get_results("SELECT * FROM $table_lieux ORDER BY $table_lieux.nom_ville ASC");

	    switch ($_REQUEST["action"]) {

	    	case 'new':
	    		
		    	// Si c'est une modification on récupaire les infos du lieu dans la DB
		    	if (isset($_GET["id"])) {
		    		$lieu_id = $_GET["id"];
		    		$info_lieu = $wpdb->get_row("SELECT * FROM $table_lieux WHERE lieux_id = $lieu_id");
		    	}
		    	else{
		    		$info_lieu = new stdClass;
		    		$info_lieu->departement = '';
		    		$info_lieu->nom_ville = '';
		    		$info_lieu->nom_lieux = '';
		    		$info_lieu->adresse = '';
		    	}

			    // Formulaire pour créer et modifier un lieux
			    ?>
			      <div class="wrap">
			      <?php if (isset($_GET["id"])) { 
			      		?><h2>Modifier le lieu</h2><?php
			      }
				  else{ 
						?><h2>Ajouter un lieu</h2><?php
				  } ?>
				  
			      

			      <form action="<?php echo $url; ?>?page=lieux" method="post">
			        <table>
			          <tr>
			            <td><label for="departement">Numéro du département :</label></td>
			            <td><input type="text" id="departement" name="departement" value="<?php echo $info_lieu->departement; ?>" placeholder="Departement" ></td>
			          </tr>
			          <tr>
			            <td><label for="ville">Ville :</label></td>
			            <td><input type="text" id="ville" name="ville" value="<?php echo $info_lieu->nom_ville; ?>" placeholder="Ville" ></td>
			          </tr>
			          <tr>
			            <td><label for="lieu">Nom du lieu :</label></td>
			            <td><input type="text" id="lieu" name="lieu" value="<?php echo $info_lieu->nom_lieux; ?>" size="40" placeholder="Nom du lieu"></td>
			          </tr>
			          <tr>
			            <td><label for="adresse">Adresse :</label></td>
			            <td><input type="text" id="adresse" name="adresse" value="<?php echo $info_lieu->adresse; ?>" size="40" placeholder="Adresse"></td>
			          </tr>
			          <?php 
			          	if (isset($_GET["id"])) {
			          		?><input type="hidden" name="edit" value="<?php echo $lieu_id; ?>" > <?php
			          	}
			           ?>
			          <tr>
			            <td colspan="2"><input type="submit" value="Enregistrer" name="submit_lieu" class="button button-primary"></td>
			          </tr>
			        </table>
			      </form>

			    </div>
			    <?php
	    		break;

	    	case 'delete':
	    			$lieux_a_delete = $wpdb->get_row("SELECT * FROM $table_lieux");
	    			if (!isset($_GET['confirme'])) {
	    				?><div class="widefat">
		    				<h2>Etez vous sur de vouloir supprimer le lieu : <span style="color:red;"><?php echo $lieux_a_delete->nom_lieux; ?></span></h2>
			    			<a href='/wp-admin/admin.php?page=lieux&action=delete&id=<?php echo $_GET['id']; ?>&confirme=oui' id='delete_lieu'>Supprimer</a>
			    			<span> - </span>
			    			<a href='/wp-admin/admin.php?page=lieux'>Annuler</a>			
	    				</div>
		    			<?php
	    			}
	    			elseif ($_GET['confirme'] == 'oui') {
	    				$lieu_id = $_GET["id"];
	    				$wpdb->delete( $table_lieux, array( 'lieux_id' => $lieu_id ) );
	    				$wpdb->update( $table_schedule, array( 'lieux_id' => '' ), array( 'lieux_id' => $lieu_id ) );

	    				?><h2>Le lieux à bien été supprimé !</h2><?php
	    				echo '<script>setTimeout(function(){ window.location.replace("'.$url.'?page=lieux"); }, 3000);</script>';
	    			}
	    			
	    		break;	
	    	
	    	default:
	    		//static $order_lieu = "lieu_asc";
				//static $order_dep = "dep_asc";
				//static $order_ville = "ville_asc";

				$order_lieu = "lieu_asc";
				$order_dep = "dep_asc";
				$order_ville = "ville_asc";
	    		
				if (isset($_GET["order"])) {

					if ($_GET["order"] == "lieu_asc") {
						$tous_les_lieux = $wpdb->get_results("SELECT * FROM $table_lieux ORDER BY nom_lieux ASC");
						$order_lieu = "lieu_desc";
					}
					elseif($_GET["order"] == "lieu_desc"){
						$tous_les_lieux = $wpdb->get_results("SELECT * FROM $table_lieux ORDER BY nom_lieux DESC");
						$order_lieu = "lieu_asc";
					}
					elseif($_GET["order"] == "dep_asc"){
						$tous_les_lieux = $wpdb->get_results("SELECT * FROM $table_lieux ORDER BY departement ASC");
						$order_dep = "dep_desc";
					}
					elseif($_GET["order"] == "dep_desc"){
						$tous_les_lieux = $wpdb->get_results("SELECT * FROM $table_lieux ORDER BY departement DESC");
						$order_dep = "dep_asc";
					}
					elseif($_GET["order"] == "ville_asc"){
						$tous_les_lieux = $wpdb->get_results("SELECT * FROM $table_lieux ORDER BY nom_ville ASC");
						$order_ville = "ville_desc";
					}
					elseif($_GET["order"] == "ville_desc"){
						$tous_les_lieux = $wpdb->get_results("SELECT * FROM $table_lieux ORDER BY nom_ville DESC");
						$order_ville = "ville_asc";
					}

				}
			

	    		// Affiche la liste des lieux 
			    ?> 
			    <table class="wp-list-table widefat fixed striped posts">
			    	<thead>
			    		<tr>
				    		<th scope="col" class="manage-column"><a href="/wp-admin/admin.php?page=lieux&order=<?php echo $order_lieu; ?>">Lieux</a></th>
			    			<th scope="col" class="manage-column column-author"><a href="/wp-admin/admin.php?page=lieux&order=<?php echo $order_dep; ?>">Departement</a></th>
			    			<th scope="col" class="manage-column column-categories"><a href="/wp-admin/admin.php?page=lieux&order=<?php echo $order_ville; ?>">Ville</a></th>
			    			<th scope="col" class="manage-column">Adresse</th>
			    		</tr>
			    	</thead>
			    	<tbody>
			    <?php
			    foreach ($tous_les_lieux as $key_lieu) {
			    	?><tr><?php
			    		?><td scope="col" class="manage-column post-title"><strong><a href="/wp-admin/admin.php?page=lieux&action=new&id=<?php echo $key_lieu->lieux_id; ?>"><?php echo $key_lieu->nom_lieux; ?></a></strong>
							<div class="blockLink">
								<a href="/wp-admin/admin.php?page=lieux&action=new&id=<?php echo $key_lieu->lieux_id; ?>">Modifier</a> | 
								<a href="/wp-admin/admin.php?page=lieux&action=delete&id=<?php echo $key_lieu->lieux_id; ?>" id="delete_lieu">Supprimer</a>
			    			</div>
			    		</td><?php
			    		?><td scope="col" class="manage-column"><?php echo $key_lieu->departement; ?></td><?php
			    		?><td scope="col" class="manage-column"><?php echo $key_lieu->nom_ville; ?></td><?php
			    		?><td scope="col" class="manage-column"><?php echo $key_lieu->adresse; ?></td><?php
			    		
			    	?></tr><?php
			    }
			    	?>
			    	</tbody>
			    </table><?php
	   
	    		break;
	    }

}

function get_lieux($id_shed='', $post_ID=''){

	global $ec3, $wpdb;

	$table_schedule = $wpdb->prefix . 'ec3_schedule';
	$table_lieux = $wpdb->prefix . 'ec3_lieux';
	$table_opt = $wpdb->prefix . 'ec3_add_opt';

	if ($id_shed == '_' || $id_shed == 'def' ) {
		$id_shed = '';
		//$lieux_id = get_post_meta($post_ID, 'ec3_default_');
   		//$lieu_default = (object) ['lieux_id' => $lieux_id];
   		$lieu_id = get_post_meta($post_ID, 'ec3_lieu_default');
   		if (empty($lieu_id) || !isset($lieu_id)) {
   			$lieu_default = new stdClass;
			$lieu_default->lieux_id = 99999;
   		}
   		else{
   			$lieu_default = new stdClass;
			$lieu_default->lieux_id = $lieu_id[0];
   		}
   		
		$lieu = 'def_lieux';
	}
	else{
		if (empty($id_shed)) {
			$id_shed = '99999';
		}
		$lieu_default = $wpdb->get_row("SELECT lieux_id FROM $table_schedule WHERE sched_id=$id_shed");
		
		if (count($lieu_default)>0) {
			$lieu_id = get_post_meta($post_ID, 'ec3_lieu_default');
			if ( !empty($lieu_id) && $lieu_default->lieux_id == $lieu_id[0] ) {
				$lieu_default->lieux_id = 99999;
				$lieu = 'lieux_'.$id_shed;
			}
			else{ $lieu = 'lieux_'.$id_shed; }
		}
		else{
			$lieu_default = new stdClass;
			$lieu_default->lieux_id = 99999;
			$lieu = 'lieux_';
		}
		
	}
	
	$tous_les_lieux = $wpdb->get_results("SELECT * FROM $table_lieux ORDER BY nom_ville");
	?>
	<select name="ec3_<?php echo $lieu; ?>" id="ec3_<?php echo $lieu; ?>"><?php
	if (empty($lieu_default->lieux_id) || $lieu_default->lieux_id == '99999') {
		?><option value="99999" selected="selected" >Lieux par default</option> <?php
	}
	else{ ?><option value="99999">Lieux par default</option> <?php }
	foreach ($tous_les_lieux as $key_lieu) {
		?> 
			<option value="<?php echo $key_lieu->lieux_id; ?>" 
					<?php if ($lieu_default->lieux_id == $key_lieu->lieux_id) {
						?>selected="selected"<?php
					} ?>
				>
				<?php echo $key_lieu->nom_ville." - ".$key_lieu->nom_lieux." (".$key_lieu->departement.")"; ?>
			</option>
		<?php
	}
	?></select> <?php
}

function get_option_event($id_shed='', $post_ID=''){

	global $ec3, $wpdb;

	if ($id_shed == '_') {
		$id_shed = '';
	}
	$table_schedule = $wpdb->prefix . 'ec3_schedule';
	$table_lieux = $wpdb->prefix . 'ec3_lieux';
	$table_opt = $wpdb->prefix . 'ec3_add_opt';

	if (!empty($id_shed)) {
		$option_default = $wpdb->get_row("SELECT option_id FROM $table_schedule WHERE sched_id=$id_shed");
	}
	else{
		$option_default = new stdClass;
		$option_default->option_id = 1;
	}
	


	$toutes_les_options = $wpdb->get_results("SELECT * FROM $table_opt");



	foreach ($toutes_les_options as $key_option) {

		if ( get_post_type() == 'exposition' && $key_option->post_type_expo == 'true') {
			?> 
				<label class="radio_liste" for="ec3_option_<?php echo $id_shed; ?>"><?php echo $key_option->nom; ?></label>&nbsp;
	          	<input class="radio_liste" type="radio" name="ec3_option_<?php echo $id_shed; ?>" value="<?php echo $key_option->option_id; ?>" 
					<?php if ($option_default->option_id == $key_option->option_id) {
							?> checked="checked" <?php
						} ?> >
			<?php
		}
		elseif ( get_post_type() != 'exposition' && $key_option->post_type_expo == false) {
			?> 
				<label class="radio_liste" for="ec3_option_<?php echo $id_shed; ?>"><?php echo $key_option->nom; ?></label>&nbsp;
	          	<input class="radio_liste" type="radio" name="ec3_option_<?php echo $id_shed; ?>" value="<?php echo $key_option->option_id; ?>" 
					<?php if ($option_default->option_id == $key_option->option_id) {
							?> checked="checked" <?php
						} ?> >
			<?php
		}
	}
}


