</div>

<!-- ═══════ MODAL: DETAIL TRANSAKSI ═══════ -->
<div id="modal-detail-txn" class="hidden">
  <div class="modal-backdrop" onclick="closeDetailModal()"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg">
      <div class="modal-header">
        <div>
          <h3 class="modal-title"><?= iconsax('document-text', 'w-5 h-5 text-primary-600') ?> Detail Transaksi</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5" id="detail-subtitle-pub"></p>
        </div>
        <button class="btn btn-ghost btn-icon" onclick="closeDetailModal()"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm" id="detail-list-pub"></dl>
        <div class="mt-4" id="detail-bukti-pub"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeDetailModal()">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════ MODAL: DETAIL PERJALANAN DINAS ═══════ -->
<div id="modal-detail-perjadin-pub" class="hidden">
  <div class="modal-backdrop" onclick="closePerjadinModalPub()"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg" style="max-width:48rem">
      <div class="modal-header">
        <div>
          <h3 class="modal-title"><?= iconsax('airplane', 'w-5 h-5 text-primary-600') ?> Detail Perjalanan Dinas</h3>
          <div class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 flex-wrap" id="perjadin-pub-subtitle"></div>
        </div>
        <button class="btn btn-ghost btn-icon" onclick="closePerjadinModalPub()"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body space-y-4" id="perjadin-pub-body" style="max-height:75vh;overflow-y:auto"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closePerjadinModalPub()">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════ MODAL: DETAIL DANA TAKTIS ═══════ -->
<div id="modal-detail-dt-pub" class="hidden">
  <div class="modal-backdrop" onclick="closeDtModalPub()"></div>
  <div class="modal-container">
    <div class="modal-box modal-box-lg">
      <div class="modal-header">
        <div>
          <h3 class="modal-title"><?= iconsax('moneys', 'w-5 h-5 text-primary-600') ?> Detail Dana Taktis</h3>
          <div class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 flex-wrap" id="dt-pub-subtitle"></div>
        </div>
        <button class="btn btn-ghost btn-icon" onclick="closeDtModalPub()"><?= iconsax('close-circle', '') ?></button>
      </div>
      <div class="modal-body" id="dt-pub-body" style="max-height:75vh;overflow-y:auto"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeDtModalPub()">Tutup</button>
      </div>
    </div>
  </div>
</div>
