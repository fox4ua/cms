<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">Настройки CMS</h1>
        <div class="text-muted">Общие системные настройки, сгруппированные по разделам.</div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-3">
        <div class="list-group">
            <?php foreach ($groups as $group): ?>
                <a class="list-group-item list-group-item-action <?= $activeGroup === $group ? 'active' : '' ?>" href="<?= site_url('/admin/settings/' . $group) ?>">
                    <?= esc($groupLabels[$group] ?? $group) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-md-9">
        <form method="post" action="<?= site_url('/admin/settings/' . $activeGroup) ?>" class="card shadow-sm">
            <?= csrf_field() ?>
            <div class="card-header bg-white">
                <strong><?= esc($groupLabels[$activeGroup] ?? $activeGroup) ?></strong>
            </div>
            <div class="card-body">
                <?php foreach ($settings as $item): ?>
                    <div class="mb-3">
                        <label class="form-label" for="setting_<?= esc($item['setting_key']) ?>">
                            <?= esc($item['setting_label']) ?>
                        </label>

                        <?php if ($item['field_type'] === 'textarea'): ?>
                            <textarea class="form-control" rows="4" id="setting_<?= esc($item['setting_key']) ?>" name="<?= esc($item['setting_key']) ?>"><?= esc($item['setting_value']) ?></textarea>
                        <?php elseif ($item['field_type'] === 'checkbox'): ?>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="setting_<?= esc($item['setting_key']) ?>" name="<?= esc($item['setting_key']) ?>" value="1" <?= (int) $item['setting_value'] === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="setting_<?= esc($item['setting_key']) ?>">Включено</label>
                            </div>
                        <?php elseif ($item['field_type'] === 'select'): ?>
                            <?php $options = json_decode((string) $item['field_options'], true) ?: []; ?>
                            <select class="form-select" id="setting_<?= esc($item['setting_key']) ?>" name="<?= esc($item['setting_key']) ?>">
                                <?php foreach ($options as $value => $label): ?>
                                    <option value="<?= esc($value) ?>" <?= (string) $item['setting_value'] === (string) $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input class="form-control" type="<?= $item['field_type'] === 'number' ? 'number' : 'text' ?>" id="setting_<?= esc($item['setting_key']) ?>" name="<?= esc($item['setting_key']) ?>" value="<?= esc($item['setting_value']) ?>">
                        <?php endif; ?>

                        <?php if (! empty($item['description'])): ?>
                            <div class="form-text"><?= esc($item['description']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer bg-white text-end">
                <button class="btn btn-primary" type="submit">Сохранить</button>
            </div>
        </form>
    </div>
</div>
