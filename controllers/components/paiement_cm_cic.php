<?php
/**
 * Composant permettant de communiquer avec le module de paiement du Crédit Mutuel / CIC version 3.0
 * http://www.cmcicpaiement.fr/
 */
class PaiementCmCicComponent extends Object {
/**
 * Paramètres du composant
 * 
 * @var array
 */
	var $settings = array(
		'CMCIC_SERVEUR' => 'https://paiement.creditmutuel.fr/test/', // URL du serveur de paiement
		'CMCIC_VERSION' => '3.0',                                    // Numéro de version de l'interface de paiement
		'CMCIC_TPE' => '1234567',                                    // Numéro de TPE
		'CMCIC_CLE' => '',                                           // Clé secrète
		'CMCIC_URLOK' => '',                                         // URL de retour en cas de paiement accepté
		'CMCIC_URLKO' => '',                                         // URL de retour en cas de paiement annulé ou refusé
		'CMCIC_CODESOCIETE' => '',                                   // Code société
	);
	
/**
 * Paramètres de la requête d'appel d'un paiement
 * 
 * @var array 
 */
	var $params = array(
		'langue' => "FR",    // Langue de l'interface de paiement
		'devise' => 'EUR',   // Devise au format de la norme ISO 4217
		'date' => '',        // Date de la transaction au format d/m/Y:H:m:s
		'montant' => '',     // Montant sans espaces au format xxxx.yy
		'reference' => '',   // Référence unique, alphanumérique, 12 caractères maximum
		'texteLibre' => '',  // Texte libre, 3200 caractères maximum
		'email' => '',       // Adresse email du client
		'nbrEch' => '',      // Nombre d'échéances, entre 2 et 4
		'dateEch1' => '',    // Si nbrEch > 1, date de l'échéance 1 au format dd/mm/yyyy
		'montantEch1' => '', // Montant de l'échéance 1 sans espaces au format xxxx.yy
		'dateEch2' => '',    // Si nbrEch > 1, date de l'échéance 2 au format dd/mm/yyyy
		'montantEch2' => '', // Montant de l'échéance 2 sans espaces au format xxxx.yy
		'dateEch3' => '',    // Si nbrEch > 2, date de l'échéance 3 au format dd/mm/yyyy
		'montantEch3' => '', // Montant de l'échéance 3 sans espaces au format xxxx.yy
		'dateEch4' => '',    // Si nbrEch > 3, date de l'échéance 4 au format dd/mm/yyyy
		'montantEch4' => '', // Montant de l'échéance 4 sans espaces au format xxxx.yy
		'options' => ''      // Autres options
	);
	
/**
 * Initialisation du Composant
 * 
 * @param $controller Contrôleur appelant
 * @param $settings Paramètres
 */
	function initialize(&$controller, $settings = array()) {
		$this->settings = array_merge($this->settings, $settings);
		
		foreach ($this->settings as $key => $value) {
			define($key, $value);
		}
		
		if (!PHP5) {
			App::import('Vendor', 'PaiementCmCic.CMCIC_Tpe', array('file' => 'paiement_cm_cic'.DS.'php4'.DS.'CMCIC_Tpe.inc.php'));
		} else {
			App::import('Vendor', 'PaiementCmCic.CMCIC_Tpe', array('file' => 'paiement_cm_cic'.DS.'php5'.DS.'CMCIC_Tpe.inc.php'));
		}
	}
	
/**
 * Préparation de la requête de paiement
 * 
 * @param $params Paramètres de l'appel
 */
	function call_request($params = array()) {
		$this->params = array_merge($this->params, $params);
		
		// Date remplie ?
		if (empty($this->params['date'])) {
			$this->params['date'] = date('d/m/Y:H:i:s');
		}
		
		$oTpe = new CMCIC_Tpe($this->params['langue']);
		$oHmac = new CMCIC_Hmac($oTpe);
		
		$cgi1_fields = sprintf(CMCIC_CGI1_FIELDS,
			$oTpe->sNumero,
			$this->params['date'],
			$this->params['montant'],
			$this->params['devise'],
			$this->params['reference'],
			$this->params['texteLibre'],
			$oTpe->sVersion,
			$oTpe->sLangue,
			$oTpe->sCodeSociete, 
			$this->params['email'],
			$this->params['nbrEch'],
			$this->params['dateEch1'],
			$this->params['montantEch1'],
			$this->params['dateEch2'],
			$this->params['montantEch2'],
			$this->params['dateEch3'],
			$this->params['montantEch3'],
			$this->params['dateEch4'],
			$this->params['montantEch4'],
			$this->params['options']
		);
		
		$sMAC = $oHmac->computeHmac($cgi1_fields);
		
		return array(
			'url' => $oTpe->sUrlPaiement,
			'version' => $oTpe->sVersion,
			'TPE' => $oTpe->sNumero,
			'date' => $this->params['date'],
			'montant' => $this->params['montant'],
			'devise' => $this->params['devise'],
			'reference' => $this->params['reference'],
			'MAC' => $sMAC,
			'url_retour' => $oTpe->sUrlKO,
			'url_retour_ok' => $oTpe->sUrlOK,
			'url_retour_err' => $oTpe->sUrlKO,
			'lgue' => $oTpe->sLangue,
			'societe' => $oTpe->sCodeSociete,
			'texte-libre' => HtmlEncode($this->params['texteLibre']),
			'mail' => $this->params['email'],
			'nbrech' => $this->params['nbrEch'],
			'dateech1' => $this->params['dateEch1'],
			'montantech1' => $this->params['montantEch1'],
			'dateech2' => $this->params['dateEch2'],
			'montantech2' => $this->params['montantEch2'],
			'dateech3' => $this->params['dateEch3'],
			'montantech3' => $this->params['montantEch3'],
			'dateech4' => $this->params['dateEch4'],
			'montantech4' => $this->params['montantEch4'],
			'options' => $this->params['options']
		);
	}
	
/**
 * Traitement de la réponse du serveur de paiement
 * Pas de paramètre, les données seront dans $_POST ou $_GET
 */
	function call_response() {
		$CMCIC_bruteVars = getMethode();
		
		$oTpe = new CMCIC_Tpe();
		$oHmac = new CMCIC_Hmac($oTpe);
		
		$cgi2_fields = sprintf(CMCIC_CGI2_FIELDS, 
			$oTpe->sNumero,
			$CMCIC_bruteVars['date'],
			$CMCIC_bruteVars['montant'],
			$CMCIC_bruteVars['reference'],
			$CMCIC_bruteVars['texte-libre'],
			$oTpe->sVersion,
			$CMCIC_bruteVars['code-retour'],
			$CMCIC_bruteVars['cvx'],
			$CMCIC_bruteVars['vld'],
			$CMCIC_bruteVars['brand'],
			$CMCIC_bruteVars['status3ds'],
			$CMCIC_bruteVars['numauto'],
			@$CMCIC_bruteVars['motifrefus'],
			@$CMCIC_bruteVars['originecb'],
			@$CMCIC_bruteVars['bincb'],
			@$CMCIC_bruteVars['hpancb'],
			@$CMCIC_bruteVars['ipclient'],
			@$CMCIC_bruteVars['originetr'],
			@$CMCIC_bruteVars['veres'],
			@$CMCIC_bruteVars['pares']
		);
		
		if ($oHmac->computeHmac($cgi2_fields) == strtolower($CMCIC_bruteVars['MAC'])) {
			$MAC_match = true;
			$receipt = CMCIC_CGI2_MACOK;
		} else {
			$MAC_match = false;
			$receipt = CMCIC_CGI2_MACNOTOK.$cgi2_fields;
		}
		
		$response = array(
			'MAC_match' => $MAC_match,
			'receipt' => $receipt
		);
		
		foreach ($CMCIC_bruteVars as $field => $value) {
			$response[$field] = $value;
		}
		
		return $response;
	}
}