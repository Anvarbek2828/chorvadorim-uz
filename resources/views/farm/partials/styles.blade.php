{{-- Ferma sahifalari (xodimlar, ferma jurnali) uchun jadval uslublari: yangi Tailwind klasslarisiz --}}
<style>
    .stf-scroll { overflow-x: auto; }
    .stf-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    .stf-table th, .stf-table td { padding: .5rem .75rem; text-align: left; border-bottom: 1px solid #e5e7eb; vertical-align: top; white-space: nowrap; }
    .stf-table th { font-weight: 600; color: #4b5563; background: #f9fafb; }
    .stf-table .stf-num { text-align: right; }
    .stf-table .stf-wrap { white-space: normal; min-width: 14rem; }
    .stf-input { padding: .25rem .5rem; font-size: .875rem; border-radius: .375rem; border: 1px solid #d1d5db; max-width: 100%; }
    .stf-pre { white-space: pre-wrap; word-break: break-word; font-size: .8125rem; }
    .stf-badge { display: inline-block; padding: .125rem .5rem; border-radius: .25rem; font-size: .75rem; }
    .stf-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: .75rem; border-radius: .375rem; }
    .stf-secret { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 1.25rem; letter-spacing: .05em; user-select: all; }
    html.dark .stf-table th { background: #273548; color: #cbd5e1; }
    html.dark .stf-table th, html.dark .stf-table td { border-color: #3e4c63; }
    html.dark .stf-warning { background: #3a2313; border-color: #78350f; color: #fde68a; }
    html.dark .stf-warning * { color: #fde68a; }
</style>
