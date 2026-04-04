(function () {
    if (document.getElementById('ev-lightbox')) return;

    document.head.insertAdjacentHTML(
        'beforeend',
        '<style id="ev-lightbox-styles">' +
            'img.ev-photo-lightbox{-webkit-touch-callout:none;-webkit-user-select:none;user-select:none;}' +
            '#ev-lightbox{' +
            'display:none;position:fixed;inset:0;z-index:9999;' +
            'width:100%;height:100%;max-height:100%;' +
            'min-height:100vh;min-height:100dvh;min-height:-webkit-fill-available;' +
            'box-sizing:border-box;' +
            'padding:max(10px,env(safe-area-inset-top)) max(10px,env(safe-area-inset-right)) max(14px,env(safe-area-inset-bottom)) max(10px,env(safe-area-inset-left));' +
            'align-items:center;justify-content:center;' +
            'flex-direction:row;background:rgba(0,0,0,.92);' +
            'overscroll-behavior:contain;touch-action:manipulation;-webkit-tap-highlight-color:transparent;' +
            '}' +
            '#ev-lightbox.ev-lightbox-open{display:flex!important;}' +
            '#ev-lightbox-img{' +
            'max-width:100%;max-height:100%;width:auto;height:auto;object-fit:contain;' +
            'pointer-events:none;user-select:none;-webkit-user-select:none;' +
            '}' +
            '#ev-lightbox-close{' +
            'position:absolute;top:max(6px,env(safe-area-inset-top));right:max(6px,env(safe-area-inset-right));' +
            'z-index:2;min-width:44px;min-height:44px;margin:0;padding:0 12px;border:0;border-radius:10px;' +
            'background:rgba(255,255,255,.14);color:#fff;font-size:26px;line-height:1;font-weight:300;' +
            'cursor:pointer;touch-action:manipulation;font-family:system-ui,sans-serif;' +
            '}' +
            '#ev-lightbox-close:active{background:rgba(255,255,255,.22);}' +
            'html.ev-lightbox-lock,body.ev-lightbox-lock{overflow:hidden;}' +
            'html.ev-lightbox-lock{height:100%;}' +
            'body.ev-lightbox-lock{position:fixed;width:100%;left:0;right:0;}' +
            '</style>'
    );

    document.body.insertAdjacentHTML(
        'beforeend',
        '<div id="ev-lightbox" role="dialog" aria-modal="true" aria-label="Full size photo">' +
            '<button type="button" id="ev-lightbox-close" aria-label="Close photo">&times;</button>' +
            '<img id="ev-lightbox-img" alt="">' +
        '</div>'
    );

    var lb = document.getElementById('ev-lightbox');
    var lbi = document.getElementById('ev-lightbox-img');
    var root = document.documentElement;
    var scrollY = 0;

    var LP_MS = 480;
    var LP_MOVE = 20;
    var lpTimer = null;
    var lpStart = null;
    var lpImg = null;
    var lpOpenedThisGesture = false;

    function useLongPressOpen() {
        return window.matchMedia('(hover: none) and (pointer: coarse)').matches;
    }

    function isThumbVisible(img) {
        if (!img.getAttribute('src')) return false;
        if (getComputedStyle(img).display === 'none' || getComputedStyle(img).visibility === 'hidden')
            return false;
        var r = img.getBoundingClientRect();
        return r.width > 2 && r.height > 2;
    }

    function close() {
        if (!lb.classList.contains('ev-lightbox-open')) return;
        lb.classList.remove('ev-lightbox-open');
        root.classList.remove('ev-lightbox-lock');
        document.body.classList.remove('ev-lightbox-lock');
        document.body.style.removeProperty('top');
        window.scrollTo(0, scrollY);
        lbi.removeAttribute('src');
        lbi.alt = '';
    }

    function open(src, alt) {
        scrollY = window.scrollY || root.scrollTop || 0;
        lbi.src = src;
        lbi.alt = alt || '';
        root.classList.add('ev-lightbox-lock');
        document.body.classList.add('ev-lightbox-lock');
        document.body.style.top = '-' + scrollY + 'px';
        lb.classList.add('ev-lightbox-open');
    }

    function clearLongPress() {
        if (lpTimer) {
            clearTimeout(lpTimer);
            lpTimer = null;
        }
        lpStart = null;
        lpImg = null;
    }

    lb.addEventListener('click', function (e) {
        if (e.target === lb || e.target === lbi) close();
    });

    document.getElementById('ev-lightbox-close').addEventListener('click', function (e) {
        e.stopPropagation();
        close();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && lb.classList.contains('ev-lightbox-open')) close();
    });

    document.addEventListener(
        'touchstart',
        function (e) {
            if (!useLongPressOpen() || lb.classList.contains('ev-lightbox-open')) return;
            var img = e.target.closest && e.target.closest('img.ev-photo-lightbox');
            if (!img || !isThumbVisible(img)) return;
            clearLongPress();
            lpOpenedThisGesture = false;
            lpImg = img;
            lpStart = { x: e.touches[0].clientX, y: e.touches[0].clientY };
            lpTimer = setTimeout(function () {
                lpTimer = null;
                if (!lpImg || !isThumbVisible(lpImg)) return;
                lpOpenedThisGesture = true;
                open(lpImg.currentSrc || lpImg.src, lpImg.alt);
                if (navigator.vibrate) navigator.vibrate(10);
            }, LP_MS);
        },
        { passive: true, capture: true }
    );

    document.addEventListener(
        'touchmove',
        function (e) {
            if (!lpTimer || !lpStart || !useLongPressOpen()) return;
            var t = e.touches[0];
            if (
                Math.abs(t.clientX - lpStart.x) > LP_MOVE ||
                Math.abs(t.clientY - lpStart.y) > LP_MOVE
            ) {
                clearLongPress();
            }
        },
        { passive: true, capture: true }
    );

    document.addEventListener(
        'touchend',
        function (e) {
            if (lpOpenedThisGesture) {
                e.preventDefault();
                lpOpenedThisGesture = false;
            }
            clearLongPress();
        },
        { passive: false, capture: true }
    );

    document.addEventListener('touchcancel', clearLongPress, { passive: true, capture: true });

    document.addEventListener('click', function (e) {
        if (lb.classList.contains('ev-lightbox-open')) return;
        if (useLongPressOpen()) return;
        var img = e.target.closest && e.target.closest('img.ev-photo-lightbox');
        if (!img || !isThumbVisible(img)) return;
        e.preventDefault();
        open(img.currentSrc || img.src, img.alt);
    });
})();
