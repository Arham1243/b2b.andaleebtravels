/* Shared checkout / hold-confirm layout (hp / hcf) */
.hp {
    --c-brand:       #cd1b4f;
    --c-brand2:      #a8173f;
    --c-brand-soft:  #fdeef3;
    --c-ink:         #1a2540;
    --c-slate:       #4a5568;
    --c-muted:       #8492a6;
    --c-line:        #dde3ef;
    --c-bg:          #f3f5fb;
    --c-white:       #ffffff;
    --c-green:       #0f9d58;
    --c-green-soft:  #e8f9f1;
    --c-amber:       #d97706;
    --c-amber-soft:  #fef3c7;
    --c-shadow:      0 2px 8px rgba(26,37,64,.07);
    --c-shadow-md:   0 6px 22px rgba(26,37,64,.11);
    --mono: "JetBrains Mono", ui-monospace, Menlo, monospace;
    --sans: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
    font-family: var(--sans);
    color: var(--c-ink);
    background: var(--c-bg);
    padding-bottom: 3rem;
}
.hp * { box-sizing: border-box; }
.hp a { text-decoration: none; }
.hp-shell { padding-top: 1.25rem; }

.hp-crumb { display:flex; align-items:center; gap:.35rem; font-size:.8rem; margin-bottom:1.25rem; flex-wrap:wrap; }
.hp-crumb a { color:var(--c-brand); font-weight:600; display:inline-flex; align-items:center; gap:.22rem; padding:.2rem .45rem; border-radius:6px; transition:background .12s; }
.hp-crumb a:hover { background:var(--c-brand-soft); }
.hp-crumb span { color:var(--c-ink); font-weight:700; padding:.2rem .45rem; }
.hp-crumb__sep { color:var(--c-muted); font-size:.85rem; }

.hcf-hold-notice {
    display:flex; align-items:center; gap:.75rem;
    background:#fffbeb; border:1px solid #fcd34d; border-radius:10px;
    padding:.75rem 1rem; margin-bottom:1.25rem;
    font-size:.82rem; color:#78350f;
}
.hcf-hold-notice i { font-size:1.2rem; color:var(--c-amber); flex-shrink:0; }

.hp-card { background:var(--c-white); border:1px solid var(--c-line); border-radius:14px; box-shadow:var(--c-shadow); overflow:hidden; }
.hp-card__head { display:flex; align-items:center; gap:.75rem; padding:.8rem 1.1rem; border-bottom:1px solid var(--c-line); background:linear-gradient(135deg,rgba(205,27,79,.035) 0%,transparent 70%); }
.hp-card__head-icon { font-size:1.35rem; color:var(--c-brand); flex-shrink:0; }
.hp-card__eyebrow { font-size:.58rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--c-muted); line-height:1; }
.hp-card__title { font-size:.95rem; font-weight:700; color:var(--c-ink); margin-top:.04rem; }
.hp-card__age { font-size:.7rem; font-weight:500; color:var(--c-muted); background:var(--c-bg); padding:.06rem .38rem; border-radius:4px; margin-left:.3rem; vertical-align:middle; }

.hp-badge { font-size:.6rem; font-weight:700; padding:.18rem .5rem; border-radius:4px; text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; }
.hp-badge--ref { background:var(--c-green-soft); color:var(--c-green); }
.hp-badge--nr  { background:#fff0f3; color:#c0143c; }

.hp-pnr-tag {
    display:inline-flex; align-items:center; gap:.28rem;
    font-family:var(--mono); font-size:.7rem; font-weight:700; color:var(--c-brand);
    background:var(--c-brand-soft); border:1px solid rgba(205,27,79,.2);
    padding:.18rem .55rem; border-radius:6px;
}

.hp-flight { padding:0; }
.hp-leg { padding:.9rem 1.1rem; }
.hp-leg--ret { border-top:1px dashed var(--c-line); }
.hp-leg__tag { display:inline-flex; align-items:center; gap:.28rem; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--c-brand); margin-bottom:.6rem; background:var(--c-brand-soft); padding:.2rem .6rem; border-radius:20px; }
.hp-leg__row { display:grid; grid-template-columns:160px 1fr auto 1fr; align-items:center; gap:.5rem 1.1rem; }
.hp-leg__airline { display:flex; gap:.6rem; align-items:center; min-width:0; }
.hp-leg__logo-wrap { width:48px; height:48px; flex-shrink:0; border-radius:11px; border:1.5px solid var(--c-line); background:#fff; box-shadow:0 1px 6px rgba(26,37,64,.07); display:flex; align-items:center; justify-content:center; padding:3px; overflow:hidden; }
.hp-leg__logo { width:100%; height:100%; object-fit:contain; display:block; }
.hp-leg__aname { font-size:.83rem; font-weight:700; color:var(--c-ink); line-height:1.25; }
.hp-leg__aflight { font-family:var(--mono); font-size:.67rem; color:var(--c-muted); margin-top:.06rem; }
.hp-leg__pt { min-width:0; }
.hp-leg__pt--arr { text-align:right; }
.hp-leg__time { font-family:var(--mono); font-size:1.28rem; font-weight:700; color:var(--c-ink); line-height:1; display:inline-flex; align-items:center; gap:.25rem; }
.hp-leg__dt { font-size:.68rem; color:var(--c-muted); margin-top:.16rem; white-space:nowrap; }
.hp-leg__city { font-size:.73rem; color:var(--c-slate); font-weight:500; margin-top:.05rem; }
.hp-nextday { font-size:.55rem; font-weight:700; background:var(--c-amber-soft); color:var(--c-amber); padding:.03rem .28rem; border-radius:4px; font-family:var(--sans); }
.hp-leg__bridge { display:flex; flex-direction:column; align-items:center; gap:.22rem; min-width:120px; }
.hp-leg__bridge-dur { font-size:.7rem; font-weight:600; color:var(--c-slate); font-family:var(--mono); }
.hp-leg__bridge-track { width:100%; display:flex; align-items:center; gap:.2rem; }
.hp-leg__bridge-dot { width:6px; height:6px; border-radius:50%; background:var(--c-brand); flex-shrink:0; }
.hp-leg__bridge-line { flex:1; height:1px; background:var(--c-muted); opacity:.35; }
.hp-leg__bridge-via { font-family:var(--mono); font-size:.58rem; font-weight:700; color:#fff; background:var(--c-amber); padding:.08rem .32rem; border-radius:4px; flex-shrink:0; }
.hp-leg__bridge-stop { font-size:.63rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; padding:.1rem .42rem; border-radius:4px; }
.hp-leg__bridge-stop--ok  { background:var(--c-green-soft); color:var(--c-green); }
.hp-leg__bridge-stop--via { background:var(--c-amber-soft); color:var(--c-amber); }
.hcf-locked {
    display:inline-flex; align-items:center; gap:.28rem;
    font-size:.65rem; font-weight:700; color:var(--c-green);
    background:var(--c-green-soft); padding:.18rem .55rem; border-radius:20px;
}
.hcf-pax-readonly { padding:.85rem 1.1rem; }
.hcf-pax-readonly__grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:.55rem .85rem; }
.hcf-pax-field { display:flex; flex-direction:column; gap:.18rem; }
.hcf-pax-field__label { font-size:.6rem; font-weight:700; letter-spacing:.09em; text-transform:uppercase; color:var(--c-muted); display:flex; align-items:center; gap:.28rem; }
.hcf-pax-field__label i { font-size:.8rem; }
.hcf-pax-field__val { font-size:.84rem; font-weight:600; color:var(--c-ink); }

.hcf-wallet-toggle { border-bottom:1px solid var(--c-line); }
.hcf-wallet-toggle__label { display:block; cursor:pointer; padding:.8rem 1.1rem; }
.hcf-wallet-toggle__label input[type=checkbox] { display:none; }
.hcf-wallet-toggle__body { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
.hcf-wallet-toggle__left { display:flex; align-items:center; gap:.75rem; }
.hcf-wallet-toggle__switch {
    width:40px; height:22px; background:#e4e9f0; border-radius:11px; flex-shrink:0;
    position:relative; transition:background .2s;
}
.hcf-wallet-toggle__slider {
    position:absolute; top:3px; left:3px;
    width:16px; height:16px; border-radius:50%; background:#fff;
    transition:transform .2s; box-shadow:0 1px 4px rgba(0,0,0,.2);
}
.hcf-wallet-toggle__label input:checked ~ .hcf-wallet-toggle__body .hcf-wallet-toggle__switch { background:var(--c-green); }
.hcf-wallet-toggle__label input:checked ~ .hcf-wallet-toggle__body .hcf-wallet-toggle__slider { transform:translateX(18px); }
.hcf-wallet-applied { padding:.6rem 1.1rem .8rem; background:#f0fdf4; border-bottom:1px solid #bbf7d0; display:flex; flex-direction:column; gap:.35rem; }
.hcf-wallet-applied__row { display:flex; justify-content:space-between; font-size:.78rem; color:var(--c-slate); }
.hcf-wallet-applied__row--rem { font-weight:700; color:var(--c-ink); }

.hcf-payment-remaining { padding:.8rem 1.1rem; }
.hcf-payment-remaining__title { font-size:.72rem; font-weight:700; color:var(--c-muted); letter-spacing:.06em; text-transform:uppercase; margin-bottom:.6rem; }
.hcf-payment-options { display:flex; flex-direction:column; gap:.55rem; }
.hcf-payment-option input[type=radio] { display:none; }
.hcf-payment-option__body {
    display:flex; align-items:center; gap:.8rem;
    padding:.75rem 1rem; border:1.5px solid var(--c-line); border-radius:11px;
    cursor:pointer; transition:border-color .15s, background .15s;
}
.hcf-payment-option input:checked ~ .hcf-payment-option__body {
    border-color:var(--c-brand); background:var(--c-brand-soft);
}
.hcf-pay-icon { font-size:1.5rem; color:var(--c-brand); width:32px; text-align:center; flex-shrink:0; }
.hcf-pay-info { flex:1; }
.hcf-pay-name { font-size:.85rem; font-weight:700; color:var(--c-ink); }
.hcf-pay-desc { font-size:.72rem; color:var(--c-muted); margin-top:.1rem; }
.hcf-payment-option__check { margin-left:auto; color:var(--c-brand); font-size:1.1rem; display:none; }
.hcf-payment-option input:checked ~ .hcf-payment-option__body .hcf-payment-option__check { display:block; }

.hp-summary { background:var(--c-white); border:1px solid var(--c-line); border-radius:14px; box-shadow:var(--c-shadow); overflow:hidden; position:sticky; top:90px; }
.hp-summary__head { padding:.85rem 1.1rem; background:linear-gradient(135deg, var(--c-brand) 0%, var(--c-brand2) 100%); color:#fff; font-size:.92rem; font-weight:700; display:flex; align-items:center; gap:.45rem; }
.hp-summary__head i { font-size:1.1rem; }
.hp-summary__body { padding:.75rem 1.1rem; border-bottom:1px solid var(--c-line); display:flex; flex-direction:column; gap:.4rem; }
.hp-sum-row { display:flex; justify-content:space-between; align-items:center; font-size:.8rem; color:var(--c-slate); }
.hp-sum-row span:last-child { font-family:var(--mono); font-weight:600; color:var(--c-ink); }
.hcf-sum-wallet { padding:.6rem 1.1rem; background:#f0fdf4; border-bottom:1px solid #bbf7d0; display:flex; flex-direction:column; gap:.35rem; }
.hcf-sum-wallet__deduct .hcf-sum-wallet__amt { color:var(--c-green); font-weight:700; font-family:var(--mono); }
.hp-summary__total { display:flex; justify-content:space-between; align-items:center; padding:.8rem 1.1rem; font-size:.86rem; font-weight:700; color:var(--c-ink); border-bottom:1px solid var(--c-line); }
.hp-summary__total-amount { font-family:var(--mono); font-size:1.22rem; font-weight:700; color:var(--c-brand); display:flex; align-items:baseline; gap:.05rem; }
.hp-summary__meta { padding:.6rem 1.1rem; border-bottom:1px solid var(--c-line); display:flex; flex-direction:column; gap:.28rem; }
.hp-summary__meta-row { display:flex; align-items:center; gap:.4rem; font-size:.74rem; color:var(--c-slate); }
.hp-summary__meta-row i { color:var(--c-brand); font-size:.85rem; flex-shrink:0; }
.hp-summary__footer { padding:.85rem 1.1rem; display:flex; flex-direction:column; gap:.5rem; }

.hp-btn-pay {
    width:100%; display:flex; align-items:center; justify-content:center; gap:.35rem;
    background:linear-gradient(180deg, var(--c-brand) 0%, var(--c-brand2) 100%);
    color:#fff !important; font:inherit; font-size:.88rem; font-weight:700;
    padding:.75rem 1rem; border:none; border-radius:9px; cursor:pointer;
    box-shadow:0 5px 16px rgba(205,27,79,.3);
    transition:transform .13s, box-shadow .13s;
}
.hp-btn-pay:hover { transform:translateY(-1px); box-shadow:0 9px 24px rgba(205,27,79,.4); }
.hp-btn-pay:disabled { opacity:.6; cursor:not-allowed; transform:none; }

.hp-summary__secure { font-size:.68rem; color:var(--c-muted); text-align:center; display:flex; align-items:center; justify-content:center; gap:.28rem; }
.hp-summary__secure i { color:var(--c-green); }

.dirham { font-family:"UAEDirham","Segoe UI",sans-serif; font-size:.8em; font-weight:400; color:inherit; margin-right:.04rem; vertical-align:baseline; }

@media (max-width: 991px) {
    .hp-leg__row { grid-template-columns:1fr 1fr; gap:.5rem; }
    .hp-leg__airline { grid-column:1 / -1; }
    .hp-leg__bridge { grid-column:1 / -1; flex-direction:row; justify-content:center; gap:.5rem; }
    .hp-leg__bridge-track { width:160px; }
    .hp-leg__pt--arr { text-align:left; }
    .hp-summary { position:static; }
}
