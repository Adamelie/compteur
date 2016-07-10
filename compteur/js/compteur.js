/**
 * 
 */
'use strict';

/** application */
var appliCompteur = angular.module('appliCompteur', ['ngCookies', 'compteur'])
.constant("PATTERN", {
	"DATE": "[0-3][0-9]\/[0-1][0-9]\/20[0-9]{2}"
});

/** module du compteur */
var compteur = angular.module('compteur', []);


/** controller */
var ctrlCompteur = compteur.controller('ctrlCompteur', 
		['$scope', '$http', '$cookies', '$filter', 'PATTERN',
		 function($scope, $http, $cookies, $filter, PATTERN) {
			// mise en scope des constantes
			$scope.PATTERN = PATTERN;
			// recuperation des donnees en session
			$scope.loger = $cookies.get('loger');
			$scope.nom = $cookies.get('nom');
			// formatage de la date du jour
			var date = $scope.date = new Date();
			var dateFormat = date.getFullYear() + "-" + (date.getMonth()+1) + "-" + date.getDate();
			var premierJourAnnee = new Date();
			premierJourAnnee.setMonth(0);
			premierJourAnnee.setDate(1);
			premierJourAnnee.setHours(0, 0, 0, 0);

			// date de debut de mois
			var premierJourMois = new Date();
			premierJourMois.setDate(1);
			premierJourMois.setHours(0, 0, 0, 0);

			// date de fin de mois
			var dernierJourMois = new Date();
			dernierJourMois.setDate(new Date(date.getFullYear(), date.getMonth()+1, 0).getDate());
			dernierJourMois.setHours(0, 0, 0, 0);

			var mots = {};
			var motsTotal = {};

			// initialisation des variables du scope
//			$scope.afficherModifierProjet = false;
			function dessinerGraphique() {
				google.charts.load("current", {packages:['corechart','bar']});
				google.charts.setOnLoadCallback(drawChart);
				google.charts.setOnLoadCallback(drawChartTotal);
			}

			function drawChart() {
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'date');
				data.addColumn('number', 'Mots');
				for (var i in mots) {
					data.addRows([[i, mots[i]]]);
				}
				var options = {
						title: 'Compteur de mots',
						legend: { position: 'none' },
						backgroundColor : 'black',
						legendTextColor : '#8258FA',
						titleColor : '#8258FA',
						axisColor : '#8258FA',
						borderColor : '#8258FA',
						focusBorderColor : 'red',
						colors: ['#8258FA']
				};

				var chart = new google.visualization.ColumnChart(document.getElementById('graphique'));
				chart.draw(data, options);
			}

			function drawChartTotal() {
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'date');
				data.addColumn('number', 'Mots');
				for (var i in motsTotal) {
					data.addRows([[i, motsTotal[i]]]);
				}
				var options = {
						title: 'Compteur de mots total',
						legend: { position: 'none' },
						backgroundColor : 'black',
						legendTextColor : '#8258FA',
						titleColor : '#8258FA',
						axisColor : '#8258FA',
						borderColor : '#8258FA',
						focusBorderColor : 'red',
						colors: ['#8258FA']
				};

				var chart = new google.visualization.ColumnChart(document.getElementById('graphiqueTotal'));
				chart.draw(data, options);
			}

			/**
			 * Calcul de la difference entre deux dates
			 */
			function nombreJourEntreDeuxdate(d1, d2){
				var WNbJours = d2.getTime() - d1.getTime();
				return Math.ceil(WNbJours/(1000*60*60*24));
			}

			/**
			 * Formatte la date pour les traitement
			 */
			function formatDateTraitement(date){
				var dateArray = date.split("/");
				return dateArray[2] + "-" + dateArray[1] + "-" + dateArray[0];
			}

			/**
			 * Calcul des moyennes
			 */
			var moyennes = function () {
				$scope.user.moyenne_mensuelle = Math.ceil($scope.user.compteur_annuel / (new Date().getMonth()+1));
				$scope.user.moyenne_quotidienne = Math.ceil($scope.user.compteur_annuel / nombreJourEntreDeuxdate(premierJourAnnee, date));
			}

			/**
			 * Calcul des objectifs
			 */
			var objectifs = function () {
				for (var i in $scope.user.objectifs) {
					var objectif = $scope.user.objectifs[i];
					// calcul du nombre de mots restant a ecrire
					objectif.nb_mots_restants = objectif.nb_mots_quotidiens - $scope.user.compteur_quotidien;
					// calcul du nombre de jour restant avant la deadline
					objectif.nb_jour_restants = nombreJourEntreDeuxdate(date, new Date(objectif.date_fin));
				}
			}

			/**
			 * Calcul du nombre de jours dans un mois
			 */
			function getNbJours(date){
				return new Date(date.getFullYear(), date.getMonth()+1, -1).getDate()+1;
			}

			/** 
			 * affichage compteur long
			 */
			function miseEnFormeCompteur() {
				Array.filter(document.getElementsByClassName('scroll'), function(elem){
					elem.scrollTop = 100;
				});
			};

			function preparerGraphique() {
				var compteurTotal = {};
				var dateMin = new Date();
				var dateMax = 0;
				for (var i in $scope.user.projets) {
					// initialisation des valeurs par defaut des champs date
					$scope.user.projets[i].dateCompteur = $filter('date')(date, "dd/MM/yyyy");
					// mis en place du graphique
					for (var j in $scope.user.projets[i].compteurs) {
						var compteur = $scope.user.projets[i].compteurs[j];
						var clef = $filter('date')(compteur.date, "dd/MM/yyyy");
						var dateCompteur = new Date(compteur.date);
						if (dateCompteur > dateMax) {
							dateMax = dateCompteur;
						} else if (dateCompteur < dateMin) {
							dateMin = dateCompteur;
						}

						if (angular.isDefined(compteurTotal[clef])) {
							compteurTotal[clef] = compteurTotal[clef] + parseInt(compteur.nb_mots);
						} else {
							compteurTotal[clef] = parseInt(compteur.nb_mots);
						}
//						var mois = dateCompteur.getMonth();
//						var annee = dateCompteur.getFullYear();
//						if (angular.isDefined(motsTotal[annee]) && angular.isDefined(compteurTotal[annee][mois])) {
//							motsTotal[annee][mois] = motsTotal[annee][mois] + parseInt(compteur.nb_mots);
//						} else {
//							motsTotal[annee][mois] = parseInt(compteur.nb_mots);
//						}
					}
				}
				console.log(dateMin);
				console.log(dateMax);
				for (var jour = dateMin; jour <= dateMax; jour.setDate(jour.getDate() + 1)) {
					var dateJour = $filter('date')(jour, "dd/MM/yyyy");
					var clef = $filter('date')(jour, "MM/yyyy");
					if (angular.isDefined(compteurTotal[dateJour])) {
						if (angular.isDefined(motsTotal[clef])) {
							motsTotal[clef] = motsTotal[clef] + compteurTotal[dateJour];
						} else {
							motsTotal[clef] = compteurTotal[dateJour];
						}
					} else {
						if (angular.isUndefined(motsTotal[clef])) {
							motsTotal[clef] = 0;
						}
					}
				}
				for (var jour = 1; jour <= dernierJourMois.getDate(); jour++) {
					var dateJour = $filter('date')(new Date(date.getFullYear(), date.getMonth(), (jour)), "dd/MM/yyyy");
					if (angular.isDefined(compteurTotal[dateJour])) {
						mots[jour] = compteurTotal[dateJour];
					} else {
						mots[jour] = 0;
					}
				}
				console.log(angular.toJson(compteurTotal))
			}

			/** 
			 * recuperation des donnees 
			 */
			var chargement = function () {
				// donnees de l'utilisateur
				var request = $http({
					method: "post",
					url: window.location.href + "php/dao/login.php",
					data: {
						name: $scope.nom
					},
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
				});

				/* Check whether the HTTP Request is successful or not. */
				request.success(function (reponse) {
					if (reponse.erreur == '')  {
						$scope.user = reponse.objet;
						// initialisation des moyennes
						moyennes();
						// initialisation des objectifs
						objectifs();

						$scope.objectifDateDebut = $filter('date')(date, "dd/MM/yyyy");
						var compteurTotal = {};
						for (var i in $scope.user.projets) {
							// initialisation des valeurs par defaut des champs date
							$scope.user.projets[i].dateCompteur = $filter('date')(date, "dd/MM/yyyy");
						}
						// alimentation des donnees pour le graphique
						preparerGraphique();
						// dessiner le graphique
						dessinerGraphique();
					} else {
						alert (reponse.erreur);
					}
				});
			};

			/** 
			 * s'il y a une session en cours au demarrage, on charge les donnees
			 */
			if ($scope.loger) {
				chargement();
			}

			/** 
			 * login
			 */
			$scope.login = function () {
				// evite les probleme de nom vide
				if (angular.isDefined($scope.nom)) {
					// session persistante
					$scope.loger = true;
					$cookies.put('loger', $scope.loger);
					$cookies.put('nom', $scope.nom);
					// chargement des donnees
					chargement();
					// mise en forme compteurs
					miseEnFormeCompteur();
				}
			}

			/** 
			 * logoff
			 */
			$scope.logoff = function () {
				$scope.loger = false;
				$scope.nom = "";
				$cookies.remove('loger');
				$cookies.remove('nom');
				location.reload();
			};

			/** 
			 * creer un nouveau projet
			 */
			$scope.creerProjet = function () {
				// evite les probleme de nom vide
				if (angular.isDefined($scope.nouveauProjet)) {
					// cas de la creation (vs modification)
					if (angular.isUndefined($scope.idProjet)) {
						$scope.idProjet = "";
					}
					var request = $http({
						method: "post",
						url: window.location.href + "php/dao/projet-creation.php",
						data: {
							project: $scope.nouveauProjet,
							user: 	 $scope.user.id,
							id:      $scope.idProjet
						},
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
					});

					/* Check whether the HTTP Request is successful or not. */
					request.success(function (reponse) {
						if (reponse.erreur == '')  {
							$scope.user.projets[reponse.objet.id] = reponse.objet;
							$scope.afficherCreerProjet();
							document.getElementById("projetSubmit").value = "Cr\u00e9er";
							$scope.user.projets[reponse.objet.id].dateCompteur = $filter('date')(date, "dd/MM/yyyy");
							delete $scope.nouveauProjet;
							delete $scope.idProjet;
						} else {
							alert (reponse.erreur);
						}

					});
				}
			};

			/** 
			 * mettre a jour le compteur d'un projet
			 */
			$scope.mettreCompteurProjetAJour = function (projet) {
				var dernierElement = projet.compteurs.length - 1;
				var ancienCompteur = 0;
				var dateCompteur = dateFormat;

				// evite les probleme de nom vide
				if (angular.isDefined(projet.nouveauxMots) || angular.isDefined(projet.motsSession)) {				
					// recuperation de la date du formulaire si presente
					if (angular.isDefined(projet.dateCompteur)) {
						// si la date du compteur est superieure a la date du jour on arrete le traitement
						if (new Date(formatDateTraitement(projet.dateCompteur)) > date) {
							alert("la date saisie est superieure a la date du jour.");
							return;
						}
						projet.dateCompteur = formatDateTraitement(projet.dateCompteur);
						dateCompteur = projet.dateCompteur;
					} else {
						projet.dateCompteur = "";
					}
					// recuperation du compteur du jour en cours s'il y en a un
					var idCompteur = projet.id + dateCompteur;
					if (angular.isDefined(projet.compteurs[idCompteur])) {
						ancienCompteur = parseInt(projet.compteurs[idCompteur].nb_mots);
					}

					if (angular.isDefined(projet.motsSession)) {
						projet.nouveauxMots = ancienCompteur + parseInt(projet.motsSession);
					}
					var request = $http({
						method: "post",
						url: window.location.href + "php/dao/compteur-projet-creation.php",
						data: {
							project: projet,
						},
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
					});

					/* Check whether the HTTP Request is successful or not. */
					request.success(function (reponse) {
						if (reponse.erreur == '')  {
							// mise a jour des compteurs
							var nombreMotsAjoutes = projet.nouveauxMots - ancienCompteur;
							$scope.user.compteur_quotidien += nombreMotsAjoutes;
							$scope.user.compteur_mensuel += nombreMotsAjoutes;
							$scope.user.compteur_annuel += nombreMotsAjoutes;
							reponse.objet.nb_mots_supplementaire = nombreMotsAjoutes;
							// ajout du nouveau compteur
							projet.compteurs[reponse.objet.id] = reponse.objet;
							// calcule des moyennes
							moyennes();
							// calcule des objectifs
							objectifs();
							// reinitialisaion des champs
							delete projet.nouveauxMots;
							delete projet.motsSession;
							projet.dateCompteur = $filter('date')(date, "dd/MM/yyyy");
							// historisation des anciens compteur
							$scope.ancienCompteur = reponse.objet.nb_mots;
							// mise en forme compteurs
							miseEnFormeCompteur();
							// alimentation des donnees pour le graphique
							preparerGraphique();
							// dessiner le graphique
							drawChart();
							drawChartTotal();
						} else {
							alert (reponse.erreur);
						}
					});
				}
			};


			/** 
			 * creer un nouvel objectif
			 */
			$scope.creerObjectifMensuel = function () {
				$scope.objectifDateDebut = $filter('date')(premierJourMois, "dd/MM/yyyy");
				$scope.objectifDateFin = $filter('date')(dernierJourMois, "dd/MM/yyyy");
				$scope.objectifTitre = "Objectif mensuel";
				$scope.user.has_objectif_mensuel = true;
				$scope.creerObjectif();
			};

			/** 
			 * creer un nouvel objectif
			 */
			$scope.creerObjectif = function () {
				// evite les probleme de nom vide
				if (angular.isDefined($scope.objectifMots) 
						&& angular.isDefined($scope.objectifDateDebut)
						&& angular.isDefined($scope.objectifDateFin)) {
					$scope.objectifDateDebut = $scope.objectifDateDebut;
					$scope.objectifDateFin = $scope.objectifDateFin;
					if (angular.isUndefined($scope.objectifTitre)) {
						$scope.objectifTitre = "";
					}
					var request = $http({
						method: "post",
						url: window.location.href + "php/dao/objectif-global-creation.php",
						data: {
							wordGoal: $scope.objectifMots,
							dateStart: formatDateTraitement($scope.objectifDateDebut),
							dateGoal: formatDateTraitement($scope.objectifDateFin),
							title: $scope.objectifTitre,
							user: 	 $scope.user.id
						},
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
					});

					/* Check whether the HTTP Request is successful or not. */
					request.success(function (reponse) {
						if (reponse.erreur == '')  {
							// recuperation du nouvel objectif
							$scope.user.objectifs[reponse.objet.id] = reponse.objet;
							// reinitialisation du formulaire
							$scope.afficherCreerObjectif();
							delete $scope.objectifTitre;
							delete $scope.objectifMots;
							$scope.objectifDateDebut = $filter('date')(date, "dd/MM/yyyy");
							delete $scope.objectifDateFin;
						} else {
							alert (reponse.erreur);
						}
					});
				}
			};

			/** 
			 * afficher/masquer les details d'un projet
			 */
			$scope.afficherProjet = function (projet) {
				projet.visible = !projet.visible;
			};

			/** 
			 * afficher/masquer la creation d'un objectif
			 */
			$scope.afficherCreerObjectif = function () {
				$scope.creerObjectifVisible=!$scope.creerObjectifVisible;
			};

			/** 
			 * afficher/masquer la creation d'un projet
			 */
			$scope.afficherCreerProjet = function () {
				$scope.creerProjetVisible=!$scope.creerProjetVisible;
			};

			/** 
			 * modifier les details d'un projet
			 */
			$scope.modifierProjet = function (projet) {
				projet.afficherModifierProjet=!projet.afficherModifierProjet;
				$scope.nouveauProjet = projet.titre;
				$scope.idProjet = projet.id;
				$scope.creerProjet();
			};

			/** 
			 * modifier les details d'un projet
			 */
			$scope.modifierObjectif = function (objectif) {
				if (!$scope.creerObjectifVisible) {
					$scope.afficherCreerObjectif();
				}
				$scope.objectifTitre = objectif.titre;
				$scope.objectifMots = objectif.nb_mots;
				$scope.objectifDateDebut = $filter('date')(objectif.date_fin, "dd/MM/yyyy");
				$scope.objectifDateFin = $filter('date')(objectif.date, "dd/MM/yyyy");
				$scope.idObjectif = projet.id;
				document.getElementById("projetSubmit").value = "Modifier";
				document.getElementById("afficheCreerProjet").focus();
				document.getElementById("nomProjet").focus();
			};

			/** 
			 * modifier les details d'un projet
			 */
			$scope.compterMots = function (projet) {
				var text = projet.texte;
				var reg = new RegExp('[%#\'\-]', 'gi');
				var text = text.replace(reg, "");
				var s = text ? text.split(/\s+/) : 0; // it splits the text on space/tab/enter
				projet.motsSession = s ? s.length : '';
			};

		}])

		.directive('focusMe', ['$timeout', function($timeout) {
			return {
				link: function(scope, element, attrs) {
					scope.$watch(attrs.focusMe, function(value) {
						if (value) {
							$timeout(function() {
								element[0].focus();
							});
						}
					});
				}
			};
		}]);