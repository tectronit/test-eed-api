<?php

class EED {
	private $eedid = "HLGByMYnGVBEpepCGjhQsjwydgrzQNvA";
	private $sessionid;

	public function __construct() {
		$this->ensureSessionStarted();
		$this->checkSessionID();
		$this->sessionid = $_SESSION["eed_sessionid"];
	}

	private function ensureSessionStarted() {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
			echo "<p><b>Session started.</b></p>";
		}
	}

	private function showError($msg = "Errore generico nel servizio shop") {
		die("<b>ERRORE:</b> " . htmlspecialchars($msg));
	}

	private function callEED($params) {
		$url = "https://shop.euras.com/eed.php?format=json&id=" . urlencode($this->eedid) . "&" . $params;
		echo "<p><b>Chiamata URL:</b> $url</p>";

		$contextOptions = [
			"http" => ["method" => "GET", "timeout" => 30],
			"ssl" => ["verify_peer" => false, "verify_peer_name" => false]
		];

		$context = stream_context_create($contextOptions);
		$json = @file_get_contents($url, false, $context);

		if ($json === false) {
			$this->showError("Impossibile raggiungere l’API (file_get_contents fallito)");
		}

		echo "<pre>" . htmlspecialchars($json) . "</pre>";
		$data = json_decode($json, true);
		if (!is_array($data)) {
			$this->showError("JSON non valido");
		}
		if (isset($data["fehlernummer"]) && $data["fehlernummer"] !== "0") {
			$this->showError("Errore API: " . ($data["fehlermeldung"] ?? 'Errore sconosciuto'));
		}

		return $data;
	}

	private function generateSessionID() {
		$data = $this->callEED("art=neuesitzung");
		$_SESSION["eed_sessionid"] = $data["sessionid"];
		echo "<p><b>Nuovo session ID:</b> " . htmlspecialchars($data["sessionid"]) . "</p>";
		return $data["sessionid"];
	}

	private function checkSessionID() {
		if (empty($_SESSION["eed_sessionid"])) {
			$this->generateSessionID();
		} else {
			echo "<p><b>Session ID esistente:</b> " . htmlspecialchars($_SESSION["eed_sessionid"]) . "</p>";
		}
	}

	public function articleSearch($keyword) {
		$data = $this->callEED("sessionid=" . urlencode($this->sessionid) . "&anzahl=10&art=artikelsuche&suchbg=" . urlencode($keyword));

		if ($data["gesamtanzahltreffer"] > 0) {
			foreach ($data["treffer"] as $values) {
				echo "<p>Articolo trovato: <b>" . htmlspecialchars($values["artikelbezeichnung"]) . "</b></p>";
			}
			echo "<p>Totale risultati: " . intval($data["gesamtanzahltreffer"]) . ", Pagina 1 di " . intval($data["anzahlseiten"]) . "</p>";
		} else {
			echo "<p>Nessun risultato per: <b>" . htmlspecialchars($keyword) . "</b></p>";
		}
	}
}

$eed = new EED();
$eed->articleSearch("REMOTE");

?>
