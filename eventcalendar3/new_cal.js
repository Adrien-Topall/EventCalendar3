// fichier jQuery pour le front end.
jQuery(function($) {
// Mettez vos fonctions avec des $ ici

//console.log('teste');

	$(document).ready(function() {

	/*****************************************
	* CALENDRIER
	******************************************/
		var d = new Date();
		var month = d.getMonth()+1;
		var yyyy = d.getFullYear();

		var endMonth = month;
		for (var i = 1; i <= 17; i++) {
			endMonth++;
			if (endMonth > 12) { endMonth = 1;  }
		}

		var listeMois = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

		$('.mois').hide();
		$('#mois_'+month).show();
		//$('.listemois a').hide();
		//$('#lien_mois_'+month).show();

		$('#lien_mois_'+month).addClass('active');
		var current = month;
		var currentY = yyyy;

		$('.prev').click(function(){
			if (current != month || currentY != yyyy) {
				var old = current;
				current --;
				if (current < 1) { current = 12; currentY--; }
				$('.mois').hide();
				$('#mois_'+current).show();
				$('.nomMois').html( listeMois[current-1] + " " + currentY );
				/*$('.lien_mois').removeClass('active').hide();
				$('#lien_mois_'+current).addClass('active').show();*/
			}

		});

		$('.next').click(function(){
			//if (current != endMonth) {
				current ++;
				if (current > 12) { current = 1; currentY++;}
				$('.mois').hide();
				$('#mois_'+current).show();
				$('.nomMois').html( listeMois[current-1] + " " + currentY );
				/*$('.lien_mois').removeClass('active').hide();
				$('#lien_mois_'+current).addClass('active').show();*/
			//}

		});
	});
});