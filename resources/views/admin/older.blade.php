@extends('layout.admin')

@section('page_title', 'Orders')
@section('topbar_title', 'Order management')

@push('styles')
<style>
.orders-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.orders-summary {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}

.orders-stat {
  background: #fff;
  border: 1px solid #e8eee9;
  border-radius: 15px;
  padding: 17px 19px;
  box-shadow: 0 5px 17px rgba(23, 65, 42, .035);
}

.orders-stat-label {
  color: #87938b;
  font-size: 11px;
  font-weight: 700;
}

.orders-stat-value {
  color: #24332b;
  font: 800 25px 'Manrope', sans-serif;
  margin-top: 7px;
}

.orders-stat.pending .orders-stat-value {
  color: #a8792f;
}

.orders-stat.completed .orders-stat-value {
  color: #238052;
}

.orders-stat.revenue .orders-stat-value {
  color: #5869ad;
}

.orders-panel {
  background: #fff;
  border: 1px solid #e8eee9;
  border-radius: 16px;
  box-shadow: 0 5px 17px rgba(23, 65, 42, .035);
  overflow: hidden;
}

.orders-panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  padding: 18px 20px;
  border-bottom: 1px solid #edf1ee;
}

.orders-panel-title {
  font: 800 16px 'Manrope', sans-serif;
  margin: 0;
  color: #24332b;
}

.orders-filter-stack {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px 20px 18px;
  border-bottom: 1px solid #edf1ee;
}

.orders-date-panel {
  padding: 16px 18px;
  border: 1px solid #cde4d4;
  border-radius: 15px;
  background: linear-gradient(135deg, #e5f3e9 0%, #f3faf5 58%, #eaf6ee 100%);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.75);
}

.orders-filter-panel {
  padding: 16px 18px;
  border: 1px solid #d5e8da;
  border-radius: 15px;
  background: linear-gradient(135deg, #f1f8f3 0%, #fbfefc 100%);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.85);
}

.orders-date-heading, .orders-filter-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 9px;
  margin-bottom: 12px;
}

.orders-date-title, .orders-filter-title {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #1b5037;
  font-size: 12px;
  font-weight: 850;
  margin: 0;
}
.orders-filter-title { color: #1b5037; }

.orders-filter-icon {
  width: 29px;
  height: 29px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  color: #176b4b;
  background: #e0f0e5;
  font-size: 13px;
}

.orders-period-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 12px;
}
.orders-period-title { color: #294233; font-size: 12px; font-weight: 850; }
.orders-period-caption { color: #617b69; font-size: 10px; margin-top: 2px; }
.orders-period-buttons { display: flex; flex-wrap: wrap; gap: 7px; }
.orders-period-button {
  border: 1px solid #bcd8c5; border-radius: 999px; padding: 8px 14px;
  background: rgba(255,255,255,.88); color: #1f6744; font-size: 11px; font-weight: 850;
  transition: .15s ease;
}
.orders-period-button:hover, .orders-period-button.is-active {
  border-color: #176b4b; background: #176b4b; color: #fff;
  box-shadow: 0 4px 10px rgba(23,107,75,.14);
}

.orders-date-fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 220px));
  gap: 10px;
}
.orders-date-field label, .orders-filter-field label {
  display: block;
  margin-bottom: 5px;
  color: #607267;
  font-size: 10px;
  font-weight: 800;
}
.orders-date-panel .form-control, .orders-filter-panel .form-control,
.orders-filter-panel .form-select {
  min-width: 0;
  height: 40px;
  border-color: #dfe9e2;
  border-radius: 9px;
  font-size: 12px;
}

.orders-filter {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.orders-filter .form-control,
.orders-filter .form-select {
  min-width: 0;
  height: 40px;
  font-size: 12px;
}

.orders-filter .btn {
  height: 40px;
  white-space: nowrap;
  font-size: 12px;
  font-weight: 700;
}

.orders-table-wrap {
  overflow-x: auto;
}

.order-summary-row {
  cursor: pointer;
}

.order-summary-row:focus {
  outline: 2px solid #8cb99d;
  outline-offset: -2px;
}

.order-expand-hint {
  display: block;
  margin-top: 3px;
  color: #8a978e;
  font-size: 10px;
  font-weight: 600;
}

.order-expand-hint i {
  transition: transform .18s ease;
}

.order-summary-row[aria-expanded="true"] .order-expand-hint i {
  transform: rotate(180deg);
}

.order-items-detail>td {
  padding: 0 !important;
  background: #f7faf8 !important;
}

.order-items-panel {
  margin: 10px 14px 14px;
  padding: 14px 16px;
  border: 1px solid #e1ebe4;
  border-radius: 12px;
  background: #fff;
}

.order-line-table thead th {
  color: #829087;
  font-size: 10px;
  letter-spacing: .5px;
  text-transform: uppercase;
  border-bottom: 1px solid #edf1ee;
}

.order-line-table tbody td {
  font-size: 11px;
  padding: 9px 8px;
}

.orders-table {
  width: 100%;
  min-width: 1080px;
  border-collapse: collapse;
}

.orders-table thead th {
  padding: 12px 13px;
  text-align: left;
  color: #89948d;
  font-size: 10px;
  letter-spacing: .7px;
  text-transform: uppercase;
  white-space: nowrap;
  border-bottom: 1px solid #edf1ee;
}

.orders-table tbody td {
  padding: 13px;
  color: #4f5c54;
  font-size: 12px;
  vertical-align: middle;
  border-bottom: 1px solid #f0f3f0;
}

.orders-table tbody tr:hover {
  background: #fbfcfb;
}

.order-id {
  font-weight: 800;
  color: #26382e;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 30px;
  padding: 5px 9px;
  font-size: 10px;
  font-weight: 800;
  white-space: nowrap;
  text-transform: capitalize;
}

.status-pill.pending {
  background: #fff5e5;
  color: #9b6a1b;
}

.status-pill.confirmed,
.status-pill.preparing {
  background: #edf1ff;
  color: #5869ad;
}

.status-pill.ready {
  background: #e8f5ed;
  color: #28774e;
}

.status-pill.out_for_delivery {
  background: #eaf3ff;
  color: #285f9b;
}

.delivery-admin-panel {
  --delivery-ink: #183629;
  --delivery-muted: #78877d;
  margin-top: 18px;
  padding: 20px;
  border: 1px solid #dce9df;
  border-radius: 18px;
  background: linear-gradient(145deg, #f8fcf9 0%, #ffffff 68%);
  box-shadow: 0 10px 28px rgba(26, 69, 43, .055);
}

.delivery-panel-heading { padding-bottom: 17px; border-bottom: 1px solid #e8f0ea; }
.delivery-panel-icon {
  width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center;
  flex: 0 0 44px; border-radius: 14px; color: #18764d; background: #e8f5ed; font-size: 20px;
}
.delivery-eyebrow { color: #7a8b80; font-size: 9px; letter-spacing: .13em; font-weight: 850; }
.delivery-panel-title { color: var(--delivery-ink); font-size: 17px; font-weight: 850; letter-spacing: -.025em; margin-top: 2px; }
.delivery-panel-subtitle { color: var(--delivery-muted); font-size: 11px; margin-top: 3px; line-height: 1.5; }
.delivery-progress-summary { padding: 17px 0 12px; }
.delivery-progress-label { color: #77887c; font-size: 9px; letter-spacing: .12em; font-weight: 850; }
.delivery-progress-caption { color: #7c8a80; font-size: 11px; margin-top: 3px; }
.delivery-progress-percent { color: #17764c; font-size: 20px; letter-spacing: -.04em; }
.delivery-progress-track { height: 7px; background: #e8efe9; border-radius: 30px; overflow: hidden; }
.delivery-progress-track span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #27875a, #72b98a); transition: width .25s ease; }
.delivery-visual-stepper { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 8px; padding: 13px 0 19px; }
.delivery-visual-step { min-width: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 4px; color: #9aa79e; }
.delivery-visual-step-icon { width: 30px; height: 30px; border: 1px solid #dce5de; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #f1f4f2; color: #8a988e; font-size: 13px; }
.delivery-visual-step-label { color: #68796e; font-size: 10px; font-weight: 800; }
.delivery-visual-step-description { color: #9aa69d; font-size: 9px; line-height: 1.35; }
.delivery-visual-step.is-done .delivery-visual-step-icon { color: #fff; border-color: #218354; background: #218354; }
.delivery-visual-step.is-done .delivery-visual-step-label { color: #23764d; }
.delivery-visual-step.is-current .delivery-visual-step-icon { color: #1c744c; border: 2px solid #268659; background: #e9f5ed; box-shadow: 0 0 0 4px rgba(38, 134, 89, .09); }
.delivery-visual-step.is-current .delivery-visual-step-label { color: #174f36; }
.delivery-info-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 9px; }
.delivery-info-card { min-width: 0; display: flex; align-items: flex-start; gap: 9px; padding: 11px; border: 1px solid #e8efe9; border-radius: 12px; background: rgba(255,255,255,.86); }
.delivery-info-wide { grid-column: span 1; }
.delivery-info-icon { width: 29px; height: 29px; flex: 0 0 29px; display: inline-flex; justify-content: center; align-items: center; border-radius: 9px; color: #27784f; background: #eef6f0; font-size: 13px; }
.delivery-info-card > div { min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.delivery-info-label { color: #87958b; font-size: 8px; font-weight: 850; letter-spacing: .1em; }
.delivery-info-card strong { color: #294233; font-size: 11px; font-weight: 800; overflow-wrap: anywhere; }
.delivery-info-link, .delivery-info-muted { color: #86948a; font-size: 10px; text-decoration: none; overflow-wrap: anywhere; }
.delivery-info-link:hover { color: #176c47; text-decoration: underline; }
.delivery-payment-state { font-size: 9px; font-weight: 800; }
.delivery-cod-due { margin-top: 12px; padding: 11px 13px; border: 1px solid #f0dfb9; border-radius: 11px; background: #fff9ed; color: #83611d; font-size: 11px; }
.delivery-cod-receipt { margin-top: 12px; display: flex; align-items: flex-start; gap: 10px; padding: 12px; border: 1px solid #d5eadc; border-radius: 12px; background: #eff8f1; color: #266a46; }
.delivery-cod-receipt-icon { font-size: 19px; }
.delivery-cod-receipt strong { display: block; font-size: 11px; }
.delivery-cod-receipt .small { color: #557a63; font-size: 10px; }
.delivery-next-action { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-top: 15px; padding: 14px; border: 1px solid #e0ebe3; border-radius: 13px; background: #fff; }
.delivery-next-copy { display: flex; flex-direction: column; gap: 3px; }
.delivery-next-copy strong { color: #264433; font-size: 12px; }
.delivery-next-copy span { color: #849187; font-size: 10px; }
.delivery-next-button { border-radius: 999px; padding: 9px 16px; font-size: 11px; font-weight: 800; box-shadow: 0 5px 13px rgba(25, 117, 72, .13); }
.delivery-next-button:disabled { opacity: .65; cursor: wait; }
.delivery-feedback { flex-basis: 100%; padding: 8px 10px; border-radius: 9px; font-size: 10px; }
.delivery-feedback.is-success { color: #216a43; background: #eaf6ee; }
.delivery-feedback.is-error { color: #98423d; background: #fff0ef; }
.delivery-activity { margin-top: 16px; padding-top: 15px; border-top: 1px solid #e8f0ea; }
.delivery-activity-heading { display: flex; align-items: center; justify-content: space-between; margin-bottom: 11px; }
.delivery-activity-heading strong { display: block; color: #294233; font-size: 12px; margin-top: 2px; }
.delivery-activity-count { padding: 4px 8px; border-radius: 20px; color: #65766b; background: #f0f4f1; font-size: 9px; font-weight: 800; }
.delivery-activity-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
.delivery-activity-item { display: flex; align-items: flex-start; gap: 9px; }
.delivery-activity-dot { flex: 0 0 25px; width: 25px; height: 25px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: #27784f; background: #edf6ef; font-size: 11px; }
.delivery-activity-item > div { display: flex; flex-direction: column; gap: 2px; }
.delivery-activity-item strong { color: #344c3c; font-size: 10px; }
.delivery-activity-item span:not(.delivery-activity-dot) { color: #839087; font-size: 9px; }
.delivery-activity-item small { color: #849087; font-size: 9px; line-height: 1.4; }
.delivery-activity-empty { color: #89958c; font-size: 10px; margin: 0; }
/* Premium COD cash-collection dialog */
.cod-settlement-dialog {
  width: min(540px, calc(100% - 28px));
  max-width: none;
  max-height: min(92vh, 780px);
  margin: auto;
  padding: 0;
  overflow: auto;
  border: 0;
  border-radius: 22px;
  background: transparent;
  color: #24392d;
  box-shadow: 0 28px 90px rgba(10, 31, 19, .30);
}
.cod-settlement-dialog::backdrop {
  background: rgba(9, 25, 16, .62);
  backdrop-filter: blur(5px);
}
.cod-settlement-dialog[open] { animation: codSettlementEnter .22s ease-out both; }
@keyframes codSettlementEnter {
  from { opacity: 0; transform: translateY(12px) scale(.985); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.cod-settlement-shell {
  overflow: hidden;
  border: 1px solid rgba(255,255,255,.72);
  border-radius: 22px;
  background: #fff;
}
.cod-settlement-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 23px 24px 20px;
  background: linear-gradient(135deg, #f2f8f3 0%, #fbfcf8 58%, #fff8e9 100%);
  border-bottom: 1px solid #eaf0ea;
}
.cod-settlement-brand { display: flex; align-items: flex-start; gap: 13px; min-width: 0; }
.cod-settlement-icon {
  display: inline-flex;
  flex: 0 0 48px;
  width: 48px;
  height: 48px;
  align-items: center;
  justify-content: center;
  border-radius: 15px;
  color: #8d6826;
  background: #fff0cc;
  box-shadow: inset 0 0 0 1px #f0dfb8;
  font-size: 21px;
}
.cod-settlement-eyebrow { display: block; margin: 1px 0 5px; color: #779080; font-size: 9px; font-weight: 900; letter-spacing: .13em; }
.cod-settlement-header h3 { margin: 0 0 5px; color: #203a2b; font-size: 20px; line-height: 1.2; font-weight: 850; letter-spacing: -.035em; }
.cod-settlement-header p { margin: 0; max-width: 360px; color: #76857a; font-size: 12px; line-height: 1.5; }
.cod-settlement-close {
  flex: 0 0 34px;
  width: 34px;
  height: 34px;
  border: 1px solid #e3ebe4;
  border-radius: 11px;
  color: #718176;
  background: rgba(255,255,255,.82);
  transition: background .15s ease, color .15s ease, transform .15s ease;
}
.cod-settlement-close:hover { color: #263d30; background: #fff; transform: rotate(4deg); }
.cod-settlement-content { padding: 21px 24px 18px; }
.cod-settlement-total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 17px 19px;
  border: 1px solid #245c40;
  border-radius: 16px;
  color: #fff;
  background: linear-gradient(120deg, #174b32 0%, #236644 72%, #2d7650 100%);
  box-shadow: 0 8px 20px rgba(25, 91, 57, .14);
}
.cod-settlement-total-copy { display: flex; flex-direction: column; gap: 3px; }
.cod-settlement-total-copy > span { color: #c2ddca; font-size: 9px; font-weight: 900; letter-spacing: .14em; }
.cod-settlement-total-copy strong { color: #fff; font-size: 34px; line-height: 1.1; font-weight: 900; letter-spacing: -.055em; font-variant-numeric: tabular-nums; }
.cod-settlement-total-copy small { color: #d5e8da; font-size: 10px; }
.cod-settlement-cash-icon { display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border: 1px solid rgba(255,255,255,.19); border-radius: 15px; color: #f7dfaa; background: rgba(255,255,255,.10); font-size: 23px; }
.cod-settlement-hint { display: flex; align-items: flex-start; gap: 9px; margin: 14px 0 18px; padding: 10px 12px; border: 1px solid #f0e5c9; border-radius: 11px; color: #806a3d; background: #fffbf1; font-size: 10px; line-height: 1.55; }
.cod-settlement-hint i { flex: 0 0 auto; margin-top: 1px; color: #b28a3f; }
.cod-settlement-fields { display: grid; grid-template-columns: minmax(0, 1fr); gap: 14px; }
.cod-settlement-field { display: grid; gap: 6px; }
.cod-settlement-field > label { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 0; color: #405548; font-size: 11px; font-weight: 850; }
.cod-settlement-field > label span { color: #98a39b; font-size: 9px; font-weight: 800; letter-spacing: .08em; }
.cod-settlement-field > small { color: #929d95; font-size: 10px; }
.cod-settlement-input-wrap { display: flex; align-items: center; min-height: 48px; border: 1px solid #dce8df; border-radius: 12px; background: #fff; transition: border-color .15s ease, box-shadow .15s ease; }
.cod-settlement-input-wrap:focus-within { border-color: #36855c; box-shadow: 0 0 0 3px rgba(54,133,92,.10); }
.cod-settlement-input-wrap > span { padding-left: 14px; color: #789080; font-size: 16px; font-weight: 800; }
.cod-settlement-input-wrap input { width: 100%; min-width: 0; height: 46px; padding: 0 13px 0 7px; border: 0; outline: 0; color: #244331; background: transparent; font-size: 17px; font-weight: 850; font-variant-numeric: tabular-nums; }
.cod-settlement-field > input[type="text"] { width: 100%; min-height: 45px; padding: 10px 13px; border: 1px solid #dce8df; border-radius: 11px; outline: 0; color: #294334; background: #fff; font-size: 12px; transition: border-color .15s ease, box-shadow .15s ease; }
.cod-settlement-field > input[type="text"]:focus { border-color: #36855c; box-shadow: 0 0 0 3px rgba(54,133,92,.10); }
.cod-settlement-field input::placeholder { color: #aab4ad; }
.cod-settlement-confirm {
  display: flex;
  align-items: flex-start;
  gap: 11px;
  margin-top: 18px;
  padding: 13px 14px;
  border: 1px solid #dcebe0;
  border-radius: 13px;
  background: #f5faf6;
  cursor: pointer;
}
.cod-settlement-confirm > input { position: absolute; width: 1px; height: 1px; opacity: 0; }
.cod-settlement-confirm-mark { display: inline-flex; flex: 0 0 22px; width: 22px; height: 22px; align-items: center; justify-content: center; margin-top: 1px; border: 1.5px solid #b8cabe; border-radius: 7px; color: transparent; background: #fff; transition: all .15s ease; }
.cod-settlement-confirm > input:checked + .cod-settlement-confirm-mark { border-color: #21804d; color: #fff; background: #21804d; }
.cod-settlement-confirm > input:focus-visible + .cod-settlement-confirm-mark { outline: 3px solid rgba(33,128,77,.2); outline-offset: 2px; }
.cod-settlement-confirm-copy { display: flex; flex-direction: column; gap: 3px; }
.cod-settlement-confirm-copy strong { color: #31513d; font-size: 11px; font-weight: 850; }
.cod-settlement-confirm-copy small { color: #829087; font-size: 10px; line-height: 1.5; }
.cod-settlement-feedback { margin-top: 12px; padding: 10px 12px; border: 1px solid #f1d4d1; border-radius: 10px; color: #963e38; background: #fff5f4; font-size: 11px; line-height: 1.5; }
.cod-settlement-footer { display: flex; flex-direction: column; gap: 13px; padding: 14px 24px 20px; border-top: 1px solid #edf2ee; background: #fcfdfc; }
.cod-settlement-secure { display: flex; align-items: center; gap: 7px; color: #839187; font-size: 9px; }
.cod-settlement-secure i { color: #3b8960; font-size: 12px; }
.cod-settlement-actions { display: flex; justify-content: flex-end; gap: 9px; }
.cod-settlement-cancel, .cod-settlement-submit { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 42px; padding: 10px 16px; border-radius: 999px; font-size: 11px; font-weight: 850; transition: transform .15s ease, box-shadow .15s ease, background .15s ease; }
.cod-settlement-cancel { border: 1px solid #dfe8e1; color: #617267; background: #fff; }
.cod-settlement-cancel:hover { color: #314b3b; background: #f5f8f5; }
.cod-settlement-submit { border: 1px solid #1d6b42; color: #fff; background: linear-gradient(135deg, #23814f, #17653e); box-shadow: 0 6px 14px rgba(24, 108, 65, .16); }
.cod-settlement-submit:hover { color: #fff; background: linear-gradient(135deg, #1d7548, #145b37); box-shadow: 0 8px 18px rgba(24, 108, 65, .22); transform: translateY(-1px); }
.cod-settlement-submit:disabled { opacity: .65; cursor: wait; transform: none; }
@media (max-width: 575px) {
  .cod-settlement-dialog { width: calc(100% - 18px); max-height: 94vh; border-radius: 17px; }
  .cod-settlement-shell { border-radius: 17px; }
  .cod-settlement-header { padding: 18px 17px 16px; gap: 9px; }
  .cod-settlement-icon { flex-basis: 42px; width: 42px; height: 42px; border-radius: 13px; }
  .cod-settlement-header h3 { font-size: 17px; }
  .cod-settlement-header p { font-size: 11px; }
  .cod-settlement-content { padding: 16px 17px; }
  .cod-settlement-total { padding: 14px 15px; }
  .cod-settlement-total-copy strong { font-size: 30px; }
  .cod-settlement-footer { padding: 13px 17px 16px; }
  .cod-settlement-actions { display: grid; grid-template-columns: 1fr 1.5fr; }
  .cod-settlement-cancel, .cod-settlement-submit { padding: 10px 11px; font-size: 10px; }
}
@media (prefers-reduced-motion: reduce) {
  .cod-settlement-dialog[open] { animation: none; }
  .cod-settlement-close, .cod-settlement-confirm-mark, .cod-settlement-cancel, .cod-settlement-submit { transition: none; }
}
.delivery-admin-panel[aria-busy="true"] { opacity: .78; pointer-events: none; }
@media (max-width: 900px) {
  .delivery-visual-stepper { grid-template-columns: repeat(3, minmax(0, 1fr)); row-gap: 13px; }
  .delivery-info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 575px) {
  .delivery-admin-panel { padding: 14px; }
  .delivery-visual-stepper { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .delivery-info-grid { grid-template-columns: 1fr; }
  .delivery-info-wide { grid-column: auto; }
  .delivery-next-action { align-items: stretch; }
  .delivery-next-button { width: 100%; }
}

.status-pill.completed,
.status-pill.paid {
  background: #e8f5ed;
  color: #20744d;
}

.status-pill.cancelled,
.status-pill.failed,
.status-pill.declined,
.status-pill.expired {
  background: #fff0ef;
  color: #a74c45;
}

.status-pill.unpaid {
  background: #fff5e5;
  color: #9b6a1b;
}

.status-pill.review_required {
  background: #f3eafa;
  color: #8055a3;
}

.items-link {
  color: #176b4b;
  font-weight: 800;
  text-decoration: none;
}

.items-link:hover {
  color: #104d38;
  text-decoration: underline;
}

.order-sub-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 3px;
  color: #7b887f;
  font-size: 10px;
  font-weight: 700;
  text-decoration: none;
}

.order-sub-link:hover {
  color: #176b4b;
}

.status-pill.compact {
  min-width: 42px;
  justify-content: center;
  padding: 4px 7px;
  font-size: 9px;
  letter-spacing: .25px;
}

.orders-table tfoot td {
  padding: 14px 13px;
  background: #f5f8f5;
  border-top: 2px solid #dce8df;
  color: #26382e;
  font-size: 12px;
  font-weight: 800;
}

.orders-table tfoot .grand-total {
  color: #176b4b;
  font-size: 15px;
  white-space: nowrap;
}

.orders-pagination {
  padding: 13px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.orders-pagination .pagination {
  margin: 0;
}

.orders-pagination-info {
  color: #7c8980;
  font-size: 11px;
}

.orders-filter .wide-search {
  grid-column: span 2;
}

.orders-filter .per-page-select {
  min-width: 90px;
}

@media(max-width:1100px) {
  .orders-filter {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media(max-width:700px) {
  .orders-summary {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .orders-filter {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .orders-filter-stack { padding: 13px; }
  .orders-date-panel, .orders-filter-panel { padding: 13px; }
  .orders-date-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }

  .orders-filter .wide-search {
    grid-column: 1/-1;
  }

  .orders-date-fields { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
@php
$orderStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'completed', 'cancelled'];
@endphp
<div class="orders-page">
  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif
  @if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif
  @if($errors->any())
  <div class="alert alert-danger mb-0">{{ $errors->first() }}</div>
  @endif

  <section class="orders-summary">
    <div class="orders-stat">
      <div class="orders-stat-label">Total orders</div>
      <div class="orders-stat-value" data-summary-value="total">{{ number_format($summary['total'] ?? 0) }}</div>
    </div>
    <div class="orders-stat pending">
      <div class="orders-stat-label">Awaiting payment</div>
      <div class="orders-stat-value" data-summary-value="pending">{{ number_format($summary['pending'] ?? 0) }}</div>
    </div>
    <div class="orders-stat completed">
      <div class="orders-stat-label">Completed orders</div>
      <div class="orders-stat-value" data-summary-value="completed">{{ number_format($summary['completed'] ?? 0) }}</div>
    </div>
    <div class="orders-stat revenue">
      <div class="orders-stat-label">Verified revenue today</div>
      <div class="orders-stat-value" data-summary-value="revenue_today">${{ number_format($summary['revenue_today'] ?? 0, 2) }}</div>
    </div>
  </section>

  <section class="orders-panel" data-live-orders data-live-url="{{ route('admin.orders.index', request()->query()) }}" data-current-page="{{ $orders->currentPage() }}">
    <div class="orders-panel-head">
      <div>
        <h1 class="orders-panel-title">Order list</h1>
        <div class="small text-muted mt-1">Search and review POS and website orders. Select any delivery order to manage its full journey here.
        </div>
        <div class="orders-live-indicator mt-2" aria-live="polite"><span class="orders-live-dot"></span><span id="ordersLiveStatus">Live updates active · checking automatically</span></div>
      </div>
      <!-- <div class="d-flex align-items-center gap-3"><span class="small text-muted">{{ $orders->total() }} result(s)</span><a href="{{ route('admin.orders.create') }}" class="btn btn-success btn-sm fw-bold"><i class="bi bi-plus-lg me-1"></i> New order</a></div> -->
    </div>

    <form action="{{ route('admin.orders.index') }}" method="GET" id="ordersFilterForm">
      <input type="hidden" name="period" id="ordersPeriodInput" value="{{ request('period') }}">
      <div class="orders-filter-stack">
        <section class="orders-date-panel" aria-labelledby="ordersDateFilterTitle">
          <div class="orders-date-heading">
            <h2 class="orders-date-title" id="ordersDateFilterTitle"><span class="orders-filter-icon"><i class="bi bi-calendar-range"></i></span>Date range</h2>
            <span class="small fw-semibold" style="color:#52735e">Choose a quick period or set dates manually</span>
          </div>
          <div class="orders-period-row">
            <div>
              <div class="orders-period-title"><i class="bi bi-calendar3 me-1"></i>Quick date range</div>
              <div class="orders-period-caption">The date preset applies to all order records and keeps your other filters.</div>
            </div>
            <div class="orders-period-buttons" role="group" aria-label="Quick order date range">
              <button type="button" class="orders-period-button {{ request('period') === 'today' ? 'is-active' : '' }}" data-order-period="today"><i class="bi bi-sun me-1"></i>Today</button>
              <button type="button" class="orders-period-button {{ request('period') === 'week' ? 'is-active' : '' }}" data-order-period="week"><i class="bi bi-calendar-week me-1"></i>This week</button>
              <button type="button" class="orders-period-button {{ request('period') === 'month' ? 'is-active' : '' }}" data-order-period="month"><i class="bi bi-calendar3 me-1"></i>This month</button>
            </div>
          </div>
          <div class="orders-date-fields">
            <div class="orders-date-field"><label for="adminOrdersStartDate">From date</label><input class="form-control" id="adminOrdersStartDate" type="date" name="start_date" aria-label="Start date" value="{{ request('start_date') }}"></div>
            <div class="orders-date-field"><label for="adminOrdersEndDate">To date</label><input class="form-control" id="adminOrdersEndDate" type="date" name="end_date" aria-label="End date" value="{{ request('end_date') }}"></div>
          </div>
        </section>

        <section class="orders-filter-panel" aria-labelledby="ordersOptionFilterTitle">
          <div class="orders-filter-heading">
            <h2 class="orders-filter-title" id="ordersOptionFilterTitle"><span class="orders-filter-icon"><i class="bi bi-sliders"></i></span>Order filters</h2>
            <span class="small text-muted">Refine by customer, payment, status and source</span>
          </div>
          <div class="orders-filter">
            <div class="orders-filter-field wide-search"><label for="ordersSearch">Search orders</label><input id="ordersSearch" class="form-control search-control" type="text" name="search" placeholder="Order ID, customer, phone, cashier, transaction..." value="{{ request('search') }}"></div>
            <div class="orders-filter-field"><label for="ordersPaymentStatus">Payment status</label><select id="ordersPaymentStatus" class="form-select" name="payment_status"><option value="">All payments</option><option value="paid" @selected(request('payment_status', request('status'))==='paid' )>Paid</option><option value="unpaid" @selected(request('payment_status', request('status'))==='unpaid' )>Unpaid</option></select></div>
            <div class="orders-filter-field"><label for="ordersOrderStatus">Order status</label><select id="ordersOrderStatus" class="form-select" name="order_status"><option value="">All order statuses</option>@foreach($orderStatuses as $status)<option value="{{ $status }}" @selected(request('order_status')===$status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
            <div class="orders-filter-field"><label for="ordersSource">Order source</label><select id="ordersSource" class="form-select" name="source"><option value="">All sources</option><option value="pos" @selected(request('source')==='pos' )>POS</option><option value="website" @selected(request('source')==='website' )>Website</option></select></div>
            <div class="orders-filter-field"><label for="ordersReviewStatus">Review marker</label><select id="ordersReviewStatus" class="form-select" name="review_status"><option value="">All orders</option><option value="open" @selected(request('review_status')==='open')>Needs attention</option><option value="clear" @selected(request('review_status')==='clear')>No open markers</option></select></div>
            <div class="orders-filter-field"><label for="ordersSortBy">Sort by</label><select id="ordersSortBy" class="form-select" name="sort_by"><option value="order_date" @selected(request('sort_by', 'order_date' )==='order_date' )>Order date</option><option value="order_id" @selected(request('sort_by')==='order_id' )>Order number</option><option value="final_price" @selected(request('sort_by')==='final_price' )>Final total</option></select></div>
            <div class="orders-filter-field"><label for="ordersSortDirection">Sort direction</label><select id="ordersSortDirection" class="form-select" name="sort_dir"><option value="desc" @selected(request('sort_dir', 'desc' )==='desc' )>Descending</option><option value="asc" @selected(request('sort_dir')==='asc' )>Ascending</option></select></div>
            <div class="orders-filter-field"><label for="ordersPerPage">Rows per page</label><select id="ordersPerPage" class="form-select per-page-select" name="per_page"><option value="10" @selected(($perPage ?? 10)===10)>10 / page</option><option value="25" @selected(($perPage ?? 10)===25)>25 / page</option><option value="50" @selected(($perPage ?? 10)===50)>50 / page</option><option value="100" @selected(($perPage ?? 10)===100)>100 / page</option></select></div>
            <div class="orders-filter-field d-flex align-items-end gap-2"><button type="submit" class="btn btn-success flex-grow-1"><i class="bi bi-funnel me-1"></i>Apply filters</button><a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Reset</a></div>
          </div>
        </section>
      </div>
    </form>

    <div class="px-3 px-md-4 py-3 d-flex justify-content-end">
      @include('admin.partials.export-actions', ['exportType' => 'orders'])
    </div>
    <div class="orders-table-wrap">
      <table class="orders-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Customer / phone</th>
            <th>Cashier</th>
            <th>Type / source</th>
            <th>Subtotal</th>
            <th>Discount</th>
            <th>Final</th>
            <th>Method</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Date</th>
            <th>Review / receipt</th>
          </tr>
        </thead>
        <tbody id="ordersTableBody">
          @include('admin.partials.orders-table-body', ['orders' => $orders, 'orderStatuses' => $orderStatuses])
        </tbody>
        <tfoot>
          <tr>
            <td colspan="6">FILTERED FINAL TOTAL <span
                class="text-muted fw-normal">(<span data-filtered-order-count>{{ number_format($filteredOrderCount ?? 0) }}</span> matching orders · all
                pages)</span></td>
            <td class="grand-total" data-filtered-order-total>${{ number_format((float) ($filteredFinalTotal ?? 0), 2) }}</td>
            <td colspan="5"></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div id="ordersPagination">
      @include('admin.partials.orders-pagination', ['orders' => $orders])
    </div>
  </section>
</div>

<div class="modal fade" id="orderReviewFlagModal" tabindex="-1" aria-labelledby="orderReviewFlagTitle" aria-hidden="true" data-order-base="{{ url('/admin/orders') }}">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius:18px;overflow:hidden">
      <div class="modal-header" style="background:#e8f4eb;border-bottom:1px solid #d6e7da">
        <div><div class="small fw-bold text-success text-uppercase" style="letter-spacing:.1em">Audit protection</div><h2 class="modal-title fs-5 fw-bold" id="orderReviewFlagTitle">Mark order for review</h2><div class="small text-muted" id="orderReviewFlagSubtitle">Order #—</div></div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" id="orderReviewFlagForm" action="#">
        @csrf
        <div class="modal-body p-4">
          <div class="alert alert-success py-2 small">This adds an audit marker only. It will not edit, cancel, refund, or delete the original order.</div>
          <div class="mb-3"><label for="orderReviewReason" class="form-label fw-semibold">What needs attention?</label><select class="form-select" id="orderReviewReason" name="reason" required><option value="">Choose a reason</option><option value="incorrect_total">Incorrect total</option><option value="wrong_customer">Wrong customer details</option><option value="payment_mismatch">Payment mismatch</option><option value="possible_duplicate">Possible duplicate order</option><option value="wrong_items">Wrong or missing items</option><option value="receipt_needed">Receipt / proof needed</option><option value="other">Other issue</option></select></div>
          <div class="mb-3"><label for="orderReviewNotes" class="form-label fw-semibold">Notes for administrator</label><textarea class="form-control" id="orderReviewNotes" name="notes" rows="4" minlength="5" maxlength="2000" required placeholder="Describe what is wrong and what should be checked..."></textarea></div>
          <div><label for="orderReviewRelatedId" class="form-label fw-semibold">Link to another order (optional)</label><input class="form-control" id="orderReviewRelatedId" name="related_order_id" type="number" min="1" step="1" placeholder="Enter related order number"><div class="form-text">Use this to connect a replacement / corrected order. You can also open or print the receipt from the order row.</div></div>
        </div>
        <div class="modal-footer bg-light"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success"><i class="bi bi-flag-fill me-1"></i>Save review marker</button></div>
      </form>
    </div>
  </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const reviewModal = document.getElementById('orderReviewFlagModal');
  const reviewForm = document.getElementById('orderReviewFlagForm');
  const reviewSubtitle = document.getElementById('orderReviewFlagSubtitle');
  if (reviewModal && reviewForm) {
    document.addEventListener('click', (event) => {
      const trigger = event.target.closest('[data-open-order-review]');
      if (!trigger) return;
      const orderId = trigger.dataset.orderId;
      const base = reviewModal.dataset.orderBase || '/admin/orders';
      reviewForm.action = `${base}/${encodeURIComponent(orderId)}/flags`;
      if (reviewSubtitle) reviewSubtitle.textContent = `Order #${orderId}`;
      reviewForm.reset();
    });
  }

  const ordersFilterForm = document.getElementById('ordersFilterForm');
  const ordersPeriodInput = document.getElementById('ordersPeriodInput');
  document.querySelectorAll('[data-order-period]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!ordersFilterForm || !ordersPeriodInput) return;
      ordersPeriodInput.value = button.dataset.orderPeriod || '';
      const startDate = ordersFilterForm.querySelector('[name="start_date"]');
      const endDate = ordersFilterForm.querySelector('[name="end_date"]');
      if (startDate) startDate.value = '';
      if (endDate) endDate.value = '';
      ordersFilterForm.requestSubmit();
    });
  });
  ['start_date', 'end_date'].forEach((field) => {
    ordersFilterForm?.querySelector(`[name="${field}"]`)?.addEventListener('change', () => {
      if (ordersPeriodInput) ordersPeriodInput.value = '';
    });
  });

  const statusLabels = {
    pending: 'PEND', confirmed: 'CONF', preparing: 'PREP', ready: 'READY',
    out_for_delivery: 'DISP', completed: 'DONE', cancelled: 'CXL'
  };

  const showFeedback = (panel, message, isError = false) => {
    const feedback = panel?.querySelector('[data-delivery-feedback]');
    if (!feedback) return;
    feedback.hidden = false;
    feedback.className = 'delivery-feedback ' + (isError ? 'is-error' : 'is-success');
    feedback.textContent = message;
  };

  const updateSummaryBadges = (orderId, payload) => {
    const summaryRow = document.querySelector(`[data-order-row="${orderId}"]`);
    if (!summaryRow) return;

    summaryRow.dataset.currentOrderStatus = payload.order_status;
    const orderBadge = summaryRow.querySelector('[data-order-status-label]');
    if (orderBadge) {
      orderBadge.className = 'status-pill compact ' + (payload.order_status || 'pending');
      orderBadge.textContent = statusLabels[payload.order_status] || String(payload.order_status || '').slice(0, 5).toUpperCase();
      orderBadge.title = 'Order status: ' + String(payload.order_status || '').replaceAll('_', ' ');
      orderBadge.setAttribute('aria-label', orderBadge.title);
    }

    const paymentBadge = summaryRow.querySelector('[data-payment-status-label]');
    if (paymentBadge) {
      const paymentState = payload.is_paid ? 'paid' : 'unpaid';
      paymentBadge.className = 'status-pill compact ' + paymentState;
      paymentBadge.textContent = payload.is_paid ? 'PAID' : 'UNPD';
      paymentBadge.title = 'Payment: ' + paymentState;
      paymentBadge.setAttribute('aria-label', paymentBadge.title);
    }
  };

  const updateSummaryCards = (summary) => {
    if (!summary) return;
    Object.entries(summary).forEach(([key, value]) => {
      const target = document.querySelector(`[data-summary-value="${key}"]`);
      if (!target) return;
      target.textContent = key === 'revenue_today'
        ? '$' + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : Number(value || 0).toLocaleString();
    });
  };

  const toggleOrderDetails = (row) => {
    const orderId = row.dataset.orderRow;
    const detail = document.getElementById(`order-items-${orderId}`);
    if (!detail) return;
    const opening = detail.classList.contains('d-none');
    detail.classList.toggle('d-none', !opening);
    row.setAttribute('aria-expanded', opening ? 'true' : 'false');
  };

  // Delegated events continue working after the table body is refreshed by AJAX.
  document.addEventListener('click', (event) => {
    const row = event.target.closest('[data-order-row]');
    if (!row || event.target.closest('a,button,form,input,select,textarea,label,dialog')) return;
    toggleOrderDetails(row);
  });

  document.addEventListener('keydown', (event) => {
    const row = event.target.closest('[data-order-row]');
    if (!row || (event.key !== 'Enter' && event.key !== ' ')) return;
    if (event.target !== row && event.target.closest('a,button,form,input,select,textarea,label,dialog')) return;
    event.preventDefault();
    toggleOrderDetails(row);
  });

  // Accessible COD confirmation dialog (also handles panels inserted by AJAX).
  document.addEventListener('click', function (event) {
    const openButton = event.target.closest('[data-open-cod-dialog]');
    if (openButton) {
      const dialog = document.getElementById(openButton.dataset.openCodDialog);
      if (dialog && typeof dialog.showModal === 'function') {
        if (!dialog.open) dialog.showModal();
        window.setTimeout(() => dialog.querySelector('[data-cod-amount]')?.focus({ preventScroll: true }), 30);
      } else if (dialog) {
        dialog.setAttribute('open', 'open');
      }
      return;
    }

    const closeButton = event.target.closest('[data-close-cod-dialog]');
    if (closeButton) {
      const dialog = closeButton.closest('dialog');
      if (dialog?.close) dialog.close();
      else dialog?.removeAttribute('open');
      return;
    }

    // Clicking the shaded backdrop closes the dialog; clicking its content does not.
    const backdropDialog = event.target.closest('dialog.cod-settlement-dialog');
    if (backdropDialog && event.target === backdropDialog) {
      backdropDialog.close?.();
      if (!backdropDialog.close) backdropDialog.removeAttribute('open');
    }
  });

  document.addEventListener('input', function (event) {
    if (!event.target.matches('[data-cod-amount], [name="cod_collection_note"]')) return;
    const dialog = event.target.closest('dialog');
    const feedback = dialog?.querySelector('[data-cod-dialog-feedback]');
    if (feedback) {
      feedback.hidden = true;
      feedback.textContent = '';
    }
  });

  // Save delivery status without reloading the page.
  document.addEventListener('submit', async function (event) {
    const form = event.target.closest('form[data-ajax-status-form]');
    if (!form) return;

    event.preventDefault();
    if (!form.reportValidity()) return;

    const panel = form.closest('[data-delivery-panel]');
    const orderId = form.dataset.orderId;
    const submitter = event.submitter || form.querySelector('[data-status-submit]');
    const originalButtonHtml = submitter?.innerHTML;

    if (submitter) {
      submitter.disabled = true;
      submitter.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Saving…';
    }
    if (panel) panel.setAttribute('aria-busy', 'true');

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form)
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        const messages = payload.errors
          ? Object.values(payload.errors).flat().join(' ')
          : (payload.message || `Unable to update order (HTTP ${response.status}).`);
        const activeDialog = submitter?.closest('dialog');
        const dialogFeedback = activeDialog?.querySelector('[data-cod-dialog-feedback]');
        if (dialogFeedback) {
          dialogFeedback.textContent = messages;
          dialogFeedback.hidden = false;
        } else {
          showFeedback(panel, messages, true);
        }
        return;
      }

      if (!payload.success || !payload.delivery_panel_html || !panel) {
        showFeedback(panel, payload.message || 'The order was saved, but the updated panel could not be rendered.', true);
        return;
      }

      const parsed = new DOMParser().parseFromString(payload.delivery_panel_html, 'text/html');
      const updatedPanel = parsed.querySelector('[data-delivery-panel]');
      if (!updatedPanel) {
        showFeedback(panel, 'The server returned an incomplete delivery panel. Please try again.', true);
        return;
      }

      panel.replaceWith(updatedPanel);
      updateSummaryBadges(orderId, payload);
      updateSummaryCards(payload.summary);
      if (window.CoffeeCornerAdminApplyCounts && payload.action_counts) {
        window.CoffeeCornerAdminApplyCounts(payload.action_counts);
      }
      showFeedback(updatedPanel, payload.message || 'Delivery status updated successfully.');
    } catch (error) {
      const activeDialog = submitter?.closest('dialog');
      const dialogFeedback = activeDialog?.querySelector('[data-cod-dialog-feedback]');
      if (dialogFeedback) {
        dialogFeedback.textContent = 'Connection problem. The server did not confirm the update. Please try again.';
        dialogFeedback.hidden = false;
      } else {
        showFeedback(panel, 'Connection problem. The server did not confirm the update. Please try again.', true);
      }
    } finally {
      if (panel?.isConnected) panel.removeAttribute('aria-busy');
      if (submitter && submitter.isConnected) {
        submitter.disabled = false;
        if (originalButtonHtml) submitter.innerHTML = originalButtonHtml;
      }
    }
  });

  // Poll the current order query every 12 seconds. Orders are inserted into the
  // visible table without a page reload; filtered and later pages are respected.
  const liveRoot = document.querySelector('[data-live-orders]');
  let listPollBusy = false;
  const refreshOrders = async () => {
    if (!liveRoot || listPollBusy || document.visibilityState !== 'visible') return;
    listPollBusy = true;
    const liveStatus = document.getElementById('ordersLiveStatus');
    try {
      const url = new URL(liveRoot.dataset.liveUrl || window.location.href, window.location.origin);
      url.searchParams.set('_live_poll', String(Date.now()));
      const response = await fetch(url.toString(), {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const payload = await response.json();
      updateSummaryCards(payload.summary);

      const countNode = document.querySelector('[data-filtered-order-count]');
      if (countNode && payload.filtered_order_count !== undefined) {
        countNode.textContent = Number(payload.filtered_order_count).toLocaleString();
      }
      const totalNode = document.querySelector('[data-filtered-order-total]');
      if (totalNode && payload.filtered_final_total !== undefined) {
        totalNode.textContent = '$' + Number(payload.filtered_final_total || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }

      if (payload.replace_table && payload.rows_html !== undefined) {
        const tbody = document.getElementById('ordersTableBody');
        if (tbody) {
          const expandedIds = Array.from(tbody.querySelectorAll('[data-order-row][aria-expanded="true"]'))
            .map(row => row.dataset.orderRow);
          tbody.innerHTML = payload.rows_html;
          expandedIds.forEach((orderId) => {
            const row = tbody.querySelector(`[data-order-row="${orderId}"]`);
            const detail = document.getElementById(`order-items-${orderId}`);
            if (row && detail) {
              row.setAttribute('aria-expanded', 'true');
              detail.classList.remove('d-none');
            }
          });
        }
        const pagination = document.getElementById('ordersPagination');
        if (pagination && payload.pagination_html !== undefined) {
          pagination.innerHTML = payload.pagination_html;
        }
      }

      if (window.CoffeeCornerAdminApplyCounts && payload.action_counts) {
        window.CoffeeCornerAdminApplyCounts(payload.action_counts);
      }
      if (liveStatus) {
        const checked = payload.checked_at ? new Date(payload.checked_at) : new Date();
        liveStatus.textContent = 'Live · updated ' + checked.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      }
    } catch (error) {
      if (liveStatus) liveStatus.textContent = 'Live updates reconnecting…';
    } finally {
      listPollBusy = false;
    }
  };

  if (liveRoot) {
    window.setTimeout(refreshOrders, 2500);
    window.setInterval(refreshOrders, 12000);
  }
});
</script>
@endsection

@endsection
