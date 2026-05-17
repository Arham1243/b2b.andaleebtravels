<style>
/* ════════════════════════════════════════════════════════════
   PROFILE SETTINGS PAGES  ps-*
   ════════════════════════════════════════════════════════════ */

/* ── Page shell ─────────────────────────────────────────── */
.ps { padding: 28px 0 48px; min-height: 80vh; }

.ps-shell {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 20px;
    align-items: start;
}

/* ── Sidebar ─────────────────────────────────────────────── */
.ps-nav {
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 14px;
    overflow: hidden;
    position: sticky;
    top: 80px;
}

.ps-nav__menu { padding: 8px 10px 8px; }

.ps-nav__item {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 10px;
    border-radius: 8px;
    text-decoration: none;
    color: #4a5568;
    font-size: .82rem;
    font-weight: 500;
    transition: background .12s, color .12s;
    margin-bottom: 2px;
    width: 100%;
}

.ps-nav__item:hover {
    background: #f5f7fa;
    color: #1a2540;
    text-decoration: none;
}

.ps-nav__item--active {
    background: #fdf1f4 !important;
    color: var(--c-brand, #cd1b4f) !important;
    font-weight: 700;
}

.ps-nav__item-icon { font-size: 1rem; flex-shrink: 0; }
.ps-nav__item-text { flex: 1; }

.ps-nav__item-arrow {
    font-size: .85rem;
    opacity: .4;
}


/* ── Main content area ───────────────────────────────────── */
.ps-main { min-width: 0; }

/* ── Page header ─────────────────────────────────────────── */
.ps-page-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 22px;
}

.ps-page-head__icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--c-brand, #cd1b4f);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.ps-page-head__title {
    font-size: 1.2rem;
    font-weight: 800;
    color: #1a2540;
    margin: 0;
}

.ps-page-head__sub {
    font-size: .73rem;
    color: #8492a6;
    margin: 0;
}

/* ── Content card ────────────────────────────────────────── */
.ps-card {
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 16px;
}

.ps-card__head {
    padding: 14px 20px 12px;
    border-bottom: 1px solid #f0f3f8;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

.ps-card__title {
    font-size: .92rem;
    font-weight: 700;
    color: #1a2540;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 7px;
}

.ps-card__title i { color: var(--c-brand, #cd1b4f); font-size: 1.05rem; }

.ps-card__body { padding: 18px 20px; }

/* ── Form grid ───────────────────────────────────────────── */
.ps-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px 20px;
}

.ps-field { display: flex; flex-direction: column; gap: 5px; }

.ps-field__label {
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #8492a6;
}

.ps-field__label .req { color: var(--c-brand, #cd1b4f); }

.ps-field__input {
    border: 1px solid #e4e9f0;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: .84rem;
    color: #1a2540;
    background: #fff;
    transition: border-color .15s;
    outline: none;
    width: 100%;
}

.ps-field__input:focus { border-color: var(--c-brand, #cd1b4f); }
.ps-field__input[readonly] { background: #f8faff; color: #8492a6; cursor: not-allowed; }

.ps-field__hint {
    font-size: .68rem;
    color: #b0bac8;
}

/* ── Avatar picker ───────────────────────────────────────── */
.ps-avatar-pick {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 0;
}

.ps-avatar-pick__preview {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e4e9f0;
    flex-shrink: 0;
}

.ps-avatar-pick__btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border: 1px dashed #c9d2df;
    border-radius: 8px;
    font-size: .78rem;
    font-weight: 600;
    color: #4a5568;
    cursor: pointer;
    background: #f8faff;
    transition: border-color .15s, color .15s;
}

.ps-avatar-pick__btn:hover {
    border-color: var(--c-brand, #cd1b4f);
    color: var(--c-brand, #cd1b4f);
}

.ps-avatar-pick__name { font-size: .68rem; color: #8492a6; margin-top: 4px; }

/* ── Save button ─────────────────────────────────────────── */
.ps-btn-save {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 20px;
    background: var(--c-brand, #cd1b4f);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
    text-decoration: none;
}

.ps-btn-save:hover { background: #a8153e; color: #fff; }

/* ── Password field wrapper ──────────────────────────────── */
.ps-pwd-wrap { position: relative; }
.ps-pwd-wrap .ps-field__input { padding-right: 42px; }

.ps-pwd-wrap__toggle {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    font-size: 1.15rem;
    color: #8492a6;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    display: flex;
    align-items: center;
}

.ps-pwd-wrap__toggle:hover { color: var(--c-brand, #cd1b4f); }

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 900px) {
    .ps-shell { grid-template-columns: 1fr; }
    .ps-nav   { position: static; }
    .ps-form-grid { grid-template-columns: 1fr; }
}
</style>
