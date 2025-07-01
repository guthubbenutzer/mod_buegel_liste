<?php
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__, 2));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// --- Nur noch für die PDF-Bridge! ---
header('Content-Type: application/json');

// 1. AJAX-Daten empfangen
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['buegel_ids']) || !is_array($data['buegel_ids'])) {
    echo json_encode(['error' => 'Ungültige Daten']);
    exit;
}

// 2. Aktuellen Joomla-User holen
$user = JFactory::getUser();
if ($user->guest) {
    echo json_encode(['error' => 'Nicht eingeloggt!']);
    exit;
}
$userId = (int)$user->id;

// 3. Kundennummer aus jos_users holen (hier: username = Kundennummer, ggf. anpassen!)
$db = JFactory::getDbo();
$query = $db->getQuery(true)
    ->select($db->qn('username'))
    ->from($db->qn('#__users'))
    ->where($db->qn('id') . ' = ' . $userId);
$db->setQuery($query);
$kundennummer = $db->loadResult();
if (!$kundennummer) {
    echo json_encode(['error' => 'Kundennummer nicht gefunden!']);
    exit;
}

// 4. Die gewünschten Zeilen aus jos_pg45_buegel_model_aktuell laden
$ids = array_map('intval', $data['buegel_ids']);
$idsList = implode(',', $ids);
$query = $db->getQuery(true)
    ->select('*')
    ->from($db->qn('#__pg45_buegel_model_aktuell'))
    ->where('id IN (' . $idsList . ')');
$db->setQuery($query);
$buegelRows = $db->loadAssocList();

error_log("DATA: " . print_r($data, true));
error_log("IDs: " . print_r($ids, true));
error_log("IDsList: " . $idsList);
error_log("SQL: " . $query->__toString());
error_log("buegelRows: " . print_r($buegelRows, true));
exit;


if (!$buegelRows) {
    echo json_encode(['error' => 'Keine passenden Bügel gefunden!']);
    exit;
}

// Optional: Werte in das richtige Datenformat bringen, falls benötigt

// 5. Parameter für den PDF-Generator vorbereiten (analog zu deinem db_pg45_qr_etiketten.php)
//    Da dieses Script POST erwartet, werden die Daten so zusammengebaut wie erwartet:
$postFields = array(
    0 => array(
        array(
            'a_kdnr' => $kundennummer,
            // weitere Werte falls nötig:
            // 'a_kdname' => $user->name, ...
        ),
    ),
    1 => "qr",
    2 => $buegelRows,   // die ausgewählten Datensätze aus der Datenbank
    3 => "kollname_az"
);

// 6. PDF-Script per cURL aufrufen (POST als JSON!)
$pdfScript = JPATH_ROOT . '/preisetiketten/templates/merri/php/etiketten/fpdi/src/db_pg45_qr_etiketten.php';
$pdfUrl = '/preisetiketten/templates/merri/php/etiketten/fpdi/src/db_pg45_qr_etiketten.php';

// Wir machen den Aufruf als internen HTTP-Request!
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $pdfUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest'
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 7. Antwort an JS zurückgeben (ist bereits JSON von db_pg45_qr_etiketten.php)
if ($httpcode == 200 && $response) {
    echo $response;
} else {
    echo json_encode(['error' => 'PDF-Erzeugung fehlgeschlagen', 'debug' => $response]);
}
exit;
?>
