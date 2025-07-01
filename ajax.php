<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug-Log starten
file_put_contents(__DIR__ . '/phpfehler.log', "[" . date('Y-m-d H:i:s') . "] Starte AJAX\n", FILE_APPEND);

define('_JEXEC', 1);

// Konfiguration & Joomla-Framework laden (Pfad ggf. anpassen!)
require_once __DIR__ . '/../../configuration.php';
file_put_contents(__DIR__ . '/phpfehler.log', "[" . date('Y-m-d H:i:s') . "] Config geladen\n", FILE_APPEND);

if (!defined('JPATH_BASE')) {
    define('JPATH_BASE', realpath(__DIR__ . '/../../'));
}

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';


file_put_contents(__DIR__ . '/phpfehler.log', "KONTROLLE: Vor Helper\n", FILE_APPEND);
require_once __DIR__ . '/helper.php';
file_put_contents(__DIR__ . '/phpfehler.log', "KONTROLLE: Nach Helper\n", FILE_APPEND);

file_put_contents(__DIR__ . '/phpfehler.log', "[" . date('Y-m-d H:i:s') . "] Helper geladen\n", FILE_APPEND);

file_put_contents(__DIR__.'/phpfehler.log', "[User] ".print_r($user, true)."\n", FILE_APPEND);
file_put_contents(__DIR__.'/phpfehler.log', "[SESSION_ID] ".session_id()."\n", FILE_APPEND);
file_put_contents(__DIR__.'/phpfehler.log', "[COOKIES] ".print_r($_COOKIE, true)."\n", FILE_APPEND);
file_put_contents(__DIR__.'/phpfehler.log', "[USER] ".print_r($user, true)."\n", FILE_APPEND);


if (!class_exists('ModBuegelListeHelper')) {
    file_put_contents(__DIR__ . '/phpfehler.log', "[" . date('Y-m-d H:i:s') . "] helper.php wurde nicht geladen!\n", FILE_APPEND);
    die(json_encode(['error' => 'helper.php nicht geladen']));
}

// Benutzer laden
// Application-Objekt explizit holen!
$app = JFactory::getApplication('site'); // "site" = Frontend-Context!
$app->initialise();

$user = JFactory::getUser();
file_put_contents(__DIR__ . '/phpfehler.log', "[" . date('Y-m-d H:i:s') . "] User geladen: " . print_r($user, true) . "\n", FILE_APPEND);

$userId = $user->id;

header('Content-Type: application/json');
file_put_contents(__DIR__ . '/phpfehler.log', "[" . date('Y-m-d H:i:s') . "] AJAX.php wird ausgeführt (userId: $userId)\n", FILE_APPEND);

// Hilfsfunktion zur bool-Umwandlung
if (!function_exists('boolVal')) {
    function boolVal($val) {
        // PHP filter_var erkennt: 0, "0", "false", false = false, alles andere true
        return filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ? true : false;
    }
}

// POST-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = isset($_GET['task']) ? $_GET['task'] : '';
    file_put_contents(__DIR__ . '/phpfehler.log', "[" . date('Y-m-d H:i:s') . "] POST-Task: $task\n", FILE_APPEND);

    try {
        if ($task === 'batchfavorit') {
            $ids = explode(',', $_POST['buegel_ids'] ?? '');
            $status = boolVal($_POST['status'] ?? 0);
            foreach ($ids as $buegelId) {
                if ($buegelId) {
                    ModBuegelListeHelper::setFavorit($userId, (int)$buegelId, $status);
                }
            }
        } elseif ($task === 'favorit') {
            $buegelId = (int)($_POST['buegel_id'] ?? 0);
            $isChecked = isset($_POST['checked']) ? boolVal($_POST['checked']) : null;
            if ($buegelId && $isChecked !== null) {
                ModBuegelListeHelper::setFavorit($userId, $buegelId, $isChecked);
            }
        } elseif ($task === 'batchdruck') {
            $ids = explode(',', $_POST['buegel_ids'] ?? '');
            $status = boolVal($_POST['status'] ?? 0);
            foreach ($ids as $buegelId) {
                if ($buegelId) {
                    ModBuegelListeHelper::setToPrint($userId, (int)$buegelId, $status);
                }
            }
        } elseif ($task === 'druck') {
            $buegelId = (int)($_POST['buegel_id'] ?? 0);
            $isChecked = isset($_POST['checked']) ? boolVal($_POST['checked']) : null;
            if ($buegelId && $isChecked !== null) {
                ModBuegelListeHelper::setToPrint($userId, $buegelId, $isChecked);
            }
        }
    } catch (Throwable $e) {
        file_put_contents(__DIR__ . '/phpfehler.log', "[" . date('Y-m-d H:i:s') . "] FEHLER: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// IMMER den aktuellen Stand zurückgeben
$favCount = ModBuegelListeHelper::countFavoriten($userId);
$druckCount = ModBuegelListeHelper::countToPrint($userId);

file_put_contents(__DIR__ . '/phpfehler.log', "[" . date('Y-m-d H:i:s') . "] Ausgabe fav: $favCount, druck: $druckCount\n", FILE_APPEND);

$maxCount = ModBuegelListeHelper::getTotalCount();
echo json_encode([
    "favCount" => $favCount,
    "druckCount" => $druckCount,
    "maxCount" => $maxCount
]);
exit;
