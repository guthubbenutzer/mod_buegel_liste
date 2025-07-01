<?php
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__, 2)); // ggf. anpassen!
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Hole die gewünschte IDs aus GET, POST oder einfach als Array zum Testen:
$ids = [503]; // Testweise, oder hole sie per $_GET['ids'] etc.

// Joomla DB holen
$db = JFactory::getDbo();
$query = $db->getQuery(true)
    ->select('*')
    ->from($db->qn('#__pg45_buegel_model_aktuell'))
    ->where('id IN (' . implode(',', array_map('intval', $ids)) . ')');
$db->setQuery($query);
$buegelRows = $db->loadAssocList();

header('Content-Type: application/json');
echo json_encode(['buegelRows' => $buegelRows]);
exit;
?>
