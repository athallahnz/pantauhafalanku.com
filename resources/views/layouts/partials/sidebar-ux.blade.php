<style>
#sidebar {width:272px;--cui-sidebar-width:272px;border-right:1px solid rgba(255,255,255,.12)!important;}
#sidebar .sidebar-header {padding:20px 12px!important;border-bottom-color:rgba(255,255,255,.12)!important;}
#sidebar .sidebar-nav {padding:14px 10px;gap:3px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.3) transparent;}
#sidebar .nav-title {padding:16px 12px 6px;color:rgba(255,255,255,.62);font-size:.68rem;letter-spacing:.1em;}
#sidebar .nav-link {min-height:44px;border-radius:10px;padding:10px 12px;gap:10px;color:rgba(255,255,255,.88);font-size:.875rem;white-space:normal;}
#sidebar .nav-link .nav-icon {flex:0 0 22px;margin:0;width:22px;font-size:1.05rem;}
#sidebar .nav-link:hover {background:rgba(255,255,255,.1);color:#fff;}
#sidebar .nav-link.active {background:rgba(255,255,255,.18);color:#fff;box-shadow:inset 3px 0 #d8c5ff;font-weight:600;}
#sidebar .nav-link:focus-visible,#sidebar-mobile-close:focus-visible {outline:2px solid #fff;outline-offset:-2px;}
#sidebar details>summary {list-style:none;cursor:pointer;display:flex;align-items:center;}
#sidebar details>summary::-webkit-details-marker {display:none;}
#sidebar .sidebar-chevron,#sidebar .recap-chevron {margin-left:auto;font-size:.7rem;transition:transform .18s;}
#sidebar details[open]>summary .sidebar-chevron,#sidebar details[open]>summary .recap-chevron {transform:rotate(180deg);}
#sidebar details.sidebar-has-active>summary {color:#fff;background:rgba(255,255,255,.08);}
#sidebar .sidebar-group-items {margin:4px 0 8px 20px;padding:0 0 0 8px;border-left:1px solid rgba(255,255,255,.18);}
#sidebar .sidebar-group-items .nav-link {font-size:.82rem;min-height:40px;padding:8px 10px;}
#sidebar .sidebar-group-items .nav-icon {font-size:.85rem;}
#sidebar-mobile-close {display:none;position:absolute;right:10px;top:10px;border:0;border-radius:8px;padding:8px;color:#fff;background:rgba(255,255,255,.1);}
#sidebar-backdrop[hidden] {display:none!important;}
#sidebar-backdrop {position:fixed;inset:0;background:rgba(15,10,26,.5);z-index:9998;}
@media(min-width:992px) {
 #sidebar~.wrapper {margin-left:272px;}
 #sidebar.sidebar-narrow {width:76px;--cui-sidebar-width:76px;}
 #sidebar.sidebar-narrow~.wrapper {margin-left:76px;}
 #sidebar.sidebar-narrow .sidebar-nav {padding:12px 8px;gap:6px;align-items:stretch;}
 #sidebar.sidebar-narrow .sidebar-nav>.nav-item {width:60px;max-width:60px;margin:0!important;padding:0;flex-shrink:0;}
 #sidebar.sidebar-narrow .sidebar-nav>.nav-item>details {width:100%;margin:0;}
 #sidebar.sidebar-narrow .sidebar-header {padding:18px 0!important;justify-content:center!important;}
 #sidebar.sidebar-narrow .nav-title,#sidebar.sidebar-narrow .sidebar-group-items,#sidebar.sidebar-narrow .sidebar-chevron,#sidebar.sidebar-narrow .recap-chevron {display:none!important;}
 #sidebar.sidebar-narrow .nav-link {box-sizing:border-box;width:60px;min-height:48px;height:48px;margin:0!important;font-size:0!important;display:flex;align-items:center;justify-content:center;padding:0!important;gap:0;border-radius:12px;}
 #sidebar.sidebar-narrow .nav-link>*:not(.nav-icon) {display:none!important;}
 #sidebar.sidebar-narrow .nav-link .nav-icon {display:inline-flex!important;align-items:center;justify-content:center;flex:0 0 24px!important;width:24px!important;height:24px;margin:0!important;padding:0!important;line-height:1;font-size:1.2rem!important;}
 #sidebar.sidebar-narrow .nav-link .nav-icon::before {line-height:1;}
 #sidebar.sidebar-narrow .nav-link.active {box-shadow:inset 3px 0 #d8c5ff;}
}
@media(max-width:991.98px) {
 #sidebar {width:min(292px,86vw)!important;max-width:86vw!important;z-index:9999!important;height:100dvh!important;}
 #sidebar-mobile-close {display:block;}
 body.sidebar-open {overflow:hidden!important;}
 #sidebar~.wrapper {margin-left:0!important;}
}
@media(prefers-reduced-motion:reduce) {#sidebar,#sidebar .sidebar-chevron,#sidebar .recap-chevron {transition:none!important;}}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('custom-sidebar-toggler');
    const close = document.getElementById('sidebar-mobile-close');
    const backdrop = document.getElementById('sidebar-backdrop');
    const wrapper = document.querySelector('.wrapper');
    if (!sidebar || !toggle || !close || !backdrop) return;
    const mobile = window.matchMedia('(max-width: 991.98px)');
    const storage = {
        get(key) { try { return localStorage.getItem(key); } catch { return null; } },
        set(key, value) { try { localStorage.setItem(key, value); } catch {} }
    };
    const role = sidebar.dataset.sidebarRole || 'guest';
    const key = `simtaqu.sidebar.v1.${role}`;
    let narrow = storage.get(`${key}.narrow`) === 'true';
    let returnFocus = null;
    const sync = () => {
        const shown = document.body.classList.contains('sidebar-open');
        sidebar.classList.toggle('sidebar-narrow', !mobile.matches && narrow);
        sidebar.inert = mobile.matches && !shown;
        if (wrapper) wrapper.inert = mobile.matches && shown;
        backdrop.hidden = !mobile.matches || !shown;
        toggle.setAttribute('aria-expanded', String(mobile.matches ? shown : !narrow));
        const label = mobile.matches ? (shown ? 'Tutup navigasi' : 'Buka navigasi') : (narrow ? 'Perluas sidebar' : 'Perkecil sidebar');
        toggle.setAttribute('aria-label', label); toggle.title = label;
    };
    const setMobile = (open) => {
        if (open) returnFocus = document.activeElement;
        document.body.classList.toggle('sidebar-open', open);
        sync();
        if (open) close.focus();
        else if (returnFocus instanceof HTMLElement) returnFocus.focus();
    };
    toggle.addEventListener('click', () => {
        if (mobile.matches) setMobile(!document.body.classList.contains('sidebar-open'));
        else { narrow = !narrow; storage.set(`${key}.narrow`, String(narrow)); sync(); }
    });
    close.addEventListener('click', () => setMobile(false));
    backdrop.addEventListener('click', () => setMobile(false));
    sidebar.querySelectorAll('a.nav-link, details>summary').forEach(link => {
        const label = link.textContent.trim().replace(/\s+/g, ' ');
        if (label) link.title = label;
        if (link.classList.contains('active')) link.setAttribute('aria-current', 'page');
    });
    sidebar.querySelectorAll('details[data-sidebar-group]').forEach(group => {
        const active = !!group.querySelector('a.active');
        const saved = storage.get(`${key}.group.${group.dataset.sidebarGroup}`);
        group.open = active || (saved === null ? group.open : saved === 'true');
        group.classList.toggle('sidebar-has-active', active);
        group.addEventListener('toggle', () => storage.set(`${key}.group.${group.dataset.sidebarGroup}`, String(group.open)));
        group.querySelector('summary').addEventListener('click', event => {
            if (!mobile.matches && narrow) {
                event.preventDefault(); narrow = false; group.open = true;
                storage.set(`${key}.narrow`, 'false'); sync();
            }
        });
    });
    sidebar.addEventListener('click', event => {
        if (mobile.matches && event.target.closest('a[href]:not([href="#"])')) setMobile(false);
    });
    document.addEventListener('keydown', event => {
        if (!mobile.matches || !document.body.classList.contains('sidebar-open')) return;
        if (event.key === 'Escape') { event.preventDefault(); setMobile(false); }
        if (event.key === 'Tab') {
            const links = [...sidebar.querySelectorAll('button, a[href], summary')].filter(el => el.getClientRects().length && !el.disabled);
            const first = links[0], last = links[links.length - 1];
            if (event.shiftKey && document.activeElement === first) {event.preventDefault(); last?.focus();}
            else if (!event.shiftKey && document.activeElement === last) {event.preventDefault(); first?.focus();}
        }
    });
    mobile.addEventListener('change', () => {document.body.classList.remove('sidebar-open'); sync();});
    sync();
    const current = sidebar.querySelector('a.active');
    if (current) current.scrollIntoView({block:'nearest'});
});
</script>
