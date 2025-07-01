<?php
defined('_JEXEC') or die;

// Sortierfunktion
function sortLink($field, $label, $sort, $order, $search) {
    $link = 'qr1?buegel_sort=' . $field . '&buegel_order=' . ($sort === $field && $order === 'ASC' ? 'DESC' : 'ASC');
    if (!empty($search)) $link .= '&buegel_search=' . urlencode($search);
    return '<a href="' . $link . '">' . $label . '</a>';
}

// Userdaten
$user       = JFactory::getUser();
$favCount   = ModBuegelListeHelper::countFavoriten($user->id);
$druckCount = ModBuegelListeHelper::countToPrint($user->id);
$maxCount   = ModBuegelListeHelper::getTotalCount();
$hasFilter  = !empty($filter) || !empty($koll) || !empty($search);
?>

<!-- Sticky-UI-Bereich oben -->
<div class="sticky-ui-wrapper">
  <div class="sticky-inner">
    <div class="google-searchbar">
      <form method="get" class="buegel-search-form" id="buegel-search-form">
        <input type="text" name="buegel_search" id="buegel_search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Suchen..." class="search-input" autocomplete="off" />
        <input type="hidden" name="buegel_filter" id="buegel_filter" value="<?php echo htmlspecialchars($filter); ?>">
        <input type="hidden" name="buegel_koll" id="buegel_koll" value="<?php echo htmlspecialchars($koll); ?>">
        <button type="submit" id="search-btn" class="search-btn">Suchen</button>
        <?php if (!empty($search)): ?>
          <button type="button" id="reset-btn" class="reset-btn">Zurücksetzen</button>
        <?php endif; ?>
      </form>
      <div id="chip-container" class="chip-container"></div>
    </div>
    <div class="buegel-filter-outer">
      <button type="button" class="buegel-filter-btn<?php if($filter==='alle') echo ' active'; ?>" data-filter="alle">Alle anzeigen</button>
      <button type="button" class="buegel-filter-btn<?php if($filter==='favoriten') echo ' active'; ?>" data-filter="favoriten">Favoriten</button>
      <button type="button" class="buegel-filter-btn<?php if($filter==='print') echo ' active'; ?>" data-filter="print">Print</button>
      <button type="button" class="buegel-filter-btn<?php if($koll==='42501') echo ' active'; ?>" data-filter="kollektion" data-koll="42501">42501</button>
      <button type="button" class="buegel-filter-btn<?php if($koll==='42408') echo ' active'; ?>" data-filter="kollektion" data-koll="42408">42408</button>
    </div>
    <button type="button" id="generate-pdf-btn" class="generate-pdf-btn">PDF generieren</button>
    <button id="ajaxPDFButton" type="button">Etiketten mit QR erstellen (AJAX)</button>
    <div id="pdfDownloadLink" style="display:none; margin-top:15px;"></div>
    <div id="pdfStatus" style="margin-top:10px; font-weight:bold;"></div>
  </div>
</div>

<!-- Tabelle -->
<div id="buegel-tabelle-wrapper" class="<?php echo $hasFilter ? '' : 'd-none'; ?>">
  <table class="buegel-table">
    <thead>
      <tr>
        <th><?php echo sortLink('kollektionsnummer', 'KOLLEKTION', $sort, $order, $search); ?></th>
        <th><?php echo sortLink('buegelnummer', 'BÜGELNR.',   $sort, $order, $search); ?></th>
        <th><?php echo sortLink('buegelname',    'BÜGELNAME',  $sort, $order, $search); ?></th>
        <th class="checkbox-cell">
          <div class="icon-checkbox-head fav-icon">
            <svg viewBox="0 0 24 24" width="20" height="20">
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 6.5 3.5 5 5.5 5
                       c1.54 0 3.04 1.04 3.57 2.36h1.87C13.46 6.04 14.96 5 16.5 5
                       18.5 5 20 6.5 20 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
            <span id="fav-count"><?php echo $favCount; ?>/<?php echo $maxCount; ?></span>
            <input type="checkbox" id="checkAllFavoriten">
          </div>
        </th>
        <th class="checkbox-cell">
          <div class="icon-checkbox-head print-icon">
            <svg viewBox="0 0 24 24" width="20" height="20">
              <path d="M19 8H5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v3zm0 2a2 2 0 0 1 2 2v6
                       a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h14zm-4 5h-6v2h6v-2z"/>
            </svg>
            <span id="druck-count"><?php echo $druckCount; ?>/<?php echo $maxCount; ?></span>
            <input type="checkbox" id="checkAllDruck">
          </div>
        </th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td><?php echo htmlspecialchars($item->kollektionsnummer); ?></td>
        <td><?php echo htmlspecialchars($item->buegelnummer); ?></td>
        <td><?php echo strtoupper(htmlspecialchars($item->buegelname)); ?></td>
        <td class="checkbox-cell">
          <input type="checkbox" class="favorit-checkbox"
                 data-buegelid="<?php echo (int)$item->id;?>"
                 <?php if(ModBuegelListeHelper::isFavorit($user->id,$item->id)) echo 'checked';?>>
        </td>
        <td class="checkbox-cell">
          <input type="checkbox" class="druck-checkbox"
                 data-buegelid="<?php echo (int)$item->id;?>"
                 <?php if(ModBuegelListeHelper::isToPrint($user->id,$item->id)) echo 'checked';?>>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  // 1) UI-Blockhöhe einmalig messen und als CSS-Variable speichern
  const stickyInner = document.querySelector('.sticky-inner');
  const uiHeight = stickyInner ? stickyInner.offsetHeight : 0;
  document.documentElement.style.setProperty('--sticky-ui-height', uiHeight + 'px');

  // 2) Restlicher JS-Code (Scroll-Effekt, Filter-Buttons, etc.)
  const tableWrapper = document.getElementById('buegel-tabelle-wrapper');
  function zeigeTabelle() {
    tableWrapper.classList.remove('d-none');
  }

  const stickyWrapper = document.querySelector('.sticky-ui-wrapper');
  if (stickyWrapper) {
    const triggerOffset = stickyWrapper.offsetTop;
    window.addEventListener('scroll', function () {
      stickyWrapper.classList.toggle('is-sticky', window.scrollY > triggerOffset);
    });
  }

  document.querySelectorAll('.buegel-filter-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
      if (this.dataset.filter === 'alle') {
        location.href = 'qr1';
      } else {
        zeigeTabelle();
      }
    });
  });

  const searchForm = document.getElementById('buegel-search-form');
  if (searchForm) {
    searchForm.addEventListener('submit', zeigeTabelle);
  }
});
</script>
