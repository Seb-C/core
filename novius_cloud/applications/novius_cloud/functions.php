<?php

/*
 *  Fonctions de Debug
 *  Les fonctions de debug ne sont disponibles qu'en local, ou pour les gens de Novius
 */

if ( !EN_PROD || \NC::isIpIn() ) {
	/** @brief Fonction d'affichage d'informations de debug.
	 *  Cette fonction affiche et/ou retourne un bloc HTML <pre>...</pre> contenant des informations (print_r)
	 *  au sujet de la variable passée en premier argument.
	 *
	 *  Un ou plusieurs arguments peuvent être passés à la fonction d().
	 *  Le premier argument est toujours la variable à afficher.
	 *  Les arguments suivants peuvent être :
	 *    - String = le nom de la variable, ou définition d'un paramètre d'affichage
	 *    - Tableau associatif = définition de plusieurs paramètres d'affichage
	 *    - Booléen = raccourci pour indiquer si le résultat de la fonction d() doit être affiché (true) ou seulement retourné (false)
	 *    - Entier = raccourci pour indiquer le nombre de lignes de backtrace à afficher
	 *
	 *  Paramètres d'affichage :
	 *    - print (booléen) = indique si le résultat de la fonction d() doit être affiché (true) ou seulement retourné (false)
	 *    - style_css = css du bloc HTML <pre>...</pre>
	 *    - var_dump = affiche le résultat de var_dump($var) au lieu de print_r($var)
	 *    - var_export = affiche le résultat de var_export($var) au lieu de print_r($var)
	 *    - serialize = affiche le résultat de serialize($var) au lieu de print_r($var)
	 *    - brut = affiche les informations sur la variable sans l'encadrer par une balise HTML <pre>...</pre>
	 *    - trace = Nombre de lignes de backtrace à afficher
	 *
	 *  Exemples :
	 *    $var = array('toto' => true, 'pi' => 3.14159);
	 *    $var2 = array('titi' => false);
	 *
	 *    d($var);
	 *      ->  Affiche le résultat de print_r($var) dans le bloc HTML <pre>...</pre>
	 *              Array
	 *              (
	 *                  [toto] => 1
	 *                  [pi] => 3.14159
	 *              )
	 *
	 *    d($var, 'variable 1');
	 *    d($var2, 'variable 2');
	 *      ->  Affiche un intitulé avant le résultat de print_r($var) dans le bloc HTML <pre>...</pre>
	 *          Utile pour différencier plusieurs appels successifs à d()
	 *              variable 1 = Array
	 *              (
	 *                  [toto] => 1
	 *                  [pi] => 3.14159
	 *              )
	 *              variable 2 = Array
	 *              (
	 *                  [titi] =>
	 *              )
	 *
	 *    d($var, 'var_dump');
	 *      ->  Affiche le résultat de var_dump($var) dans le bloc HTML <pre>...</pre>
	 *          Utile pour afficher des informations précises sur un très grand tableau ou objet
	 *              array(2) {
	 *                ["toto"]=>
	 *                bool(true)
	 *                ["pi"]=>
	 *                float(3.14159)
	 *              }
	 *
	 *    $resultat = d($var, false, 'brut');
	 *      ->  Renvoie (dans la variable $resultat), au lieu de l'afficher, le résultat de d($var) sans balise <pre>...</pre>
	 *          Utile pour stocker temporairement le résultat de la fonction d(), par exemple pour l'écrire ensuite dans un fichier de log
	 *
	 * @source Publinova
	 */
	function d()
	{
		$args = func_get_args();
		if ( empty($args) ) {
			$args = array(null);
		}

		$color      = '#ffffff';
		$background = '#c43c35';

		if (date('d/m') == '01/04') {
			$color      = 'rgb('.rand(0, 255).', '.rand(0, 255).', '.rand(0, 255).')';
			$background = 'rgb('.rand(0, 255).', '.rand(0, 255).', '.rand(0, 255).')';
		}

		// Params
		$label  = '';
		$value  = array_shift($args);
		$params = array(
			'print'      => true,
			'brut'       => false,
			'var_dump'   => false,
			'var_export' => false,
			'serialize'  => false,
			'trace_jump' => 1,
			'trace'      => 1,
			'style_css'  => '
			text-align: left;
			background: '.$background.';
			color: '.$color.';
			font-weight: bold;
			padding: 10px;
			text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
			border: 2px solid #7b1c17;
			overflow: auto;
			border-radius: 4px;
			-moz-border-radius: 4px;
			-webkit-border-radius: 4px;'
		);

		foreach ($args as $i => $a) {
			if ( is_bool($a) ) {
				// Booléen : définition du paramètre print
				$params['print'] = $a;
			} elseif ( is_int($a) ) {
				// Nombre de ligne de trace
				$params['trace'] = $a;
			} elseif ( is_string($a) ) {
				// Chaîne : on découpe sur un éventuel signe "="
				$strparam = explode('=', $a, 2);
				if ( count($strparam) == 1 ) { // si pas de signe "="
					if ( isset($params[$a]) ) {
						$params[$a] = true;
					} else {
						$label = $a;
					}
				} else { // si signe "=" trouvé
					$params[$strparam[0]] = $strparam[1]; // définition du paramètre correspondant
				}
			} elseif ( is_array($args[$i]) ) {
				$params = array_merge($params, $a);
			}
		}

		// Génération des informations sur la variable
		if ( $params['var_dump'] ) {
			ob_start();
			var_dump($value);
			$output = ob_get_clean();
		} elseif ( $params['var_export'] ) {
			ob_start();
			var_export($value);
			$output = ob_get_clean();
		} elseif ( $params['serialize'] ) {
			$output = serialize($value);
		} elseif ( is_bool($value) ) {
			$output = $value ? 'true' : 'false';
		} elseif ( !isset($value) ) {
            $output = 'null';
        } else {
			$output = print_r($value, true);
		}

		// Affichage trace ?
		$trace_output = \NC::trace($params['trace'], $params['trace_jump']);
        if ( !empty($trace_output) ) {
            $trace_output = '<div class="trace" style="font-size: 11px; color: #eee; margin-bottom: 10px;">' . implode('<br />', $trace_output) . '</div>';
        } else {
            $trace_output = '';
        }

		// Label ?
		if ( !empty($label) ) {
			$label = htmlspecialchars($label) . ' = ';
		}

		// Génération du résultat
		if ( empty($params['brut']) ) {
			$output = htmlspecialchars($output);
			$output = '<pre class="debug" style="' . str_replace("\n", '', $params['style_css']) . '">' . $trace_output . $label . $output . '</pre>';
		}

		if ( $params['print'] ) {
			echo $output;
		}
		return $output;
	}

	function dd()
	{
		$args = func_get_args();
        $args[] = array(
            'trace_jump'    => 3,
        );
		call_user_func_array('d', $args);
		exit;
	}

	function v()
	{
		$args   = func_get_args();
		$args[] = array(
			'var_dump'      => 1,
            'trace_jump'    => 3,
		);
		call_user_func_array('d', $args);
	}

	function vv()
	{
		$args = func_get_args();
        $args[] = array(
            'var_dump'      => 1,
            'trace_jump'    => 3,
        );
		call_user_func_array('d', $args);
		exit;
	}
} else {
	function d() {}
	function dd(){}
	function v() {}
	function vv() {}
}
