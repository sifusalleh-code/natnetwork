{{--
    Toolbar bulk action untuk senarai akaun ahli / transaksi kelulusan (Admin > Akaun pengguna).
    Guna dalam satu <form method="post" x-data="..."> yang membungkus keseluruhan jadual/senarai;
    setiap checkbox baris guna x-model="selected" :value="id" name="ids[]".

    Params:
    - actions (array) senarai ['key','label','needsReason' => bool, 'confirm' => string, 'danger' => bool]
      (endpoint diambil daripada `action` attribute <form> yang membungkus partial ini)
--}}
<div x-show="selected.length > 0" x-transition style="position: sticky; bottom: 0; z-index: 20; display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; padding: .7rem 1rem; margin-top: .8rem; background: #1f2937; color: #fff; border-radius: .5rem;">
    <strong x-text="selected.length + ' dipilih'"></strong>
    <template x-for="opt in {{ json_encode($actions) }}" :key="opt.key">
        <button type="button" class="button" :class="opt.danger ? 'button-secondary' : ''"
            @click="
                action = opt.key;
                reason = '';
                if (opt.needsReason) { reason = prompt(opt.reasonLabel || 'Sebab (wajib):'); if (reason === null || reason.trim() === '') return; }
                if (!confirm(opt.confirm.replace('%d', selected.length))) return;
                $refs.actionField.value = opt.key; $refs.reasonField.value = reason; $refs.confirmField.value = '1';
                $el.closest('form').requestSubmit();
            "
            x-text="opt.label"></button>
    </template>
    <input type="hidden" name="action" x-ref="actionField">
    <input type="hidden" name="reason" x-ref="reasonField">
    <input type="hidden" name="confirm" x-ref="confirmField">
</div>
