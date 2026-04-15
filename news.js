let allNews = [];

function parseDateStr(s){
    if(!s) return new Date(0);
    // unterstütze deutsches Format DD.MM.YYYY
    const m = s.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/);
    if(m){
        const [_, d, mo, y] = m;
        return new Date(`${y}-${mo.padStart(2,'0')}-${d.padStart(2,'0')}`);
    }
    return new Date(s);
}

// prepare 'Weiter lesen' button early to avoid race conditions
(function setupMoreButton(){
    const moreBtn = document.getElementById('news-more-btn');
    if(!moreBtn) return;

    let clicked = false;
    let pendingClick = false;

    moreBtn.addEventListener('click', (ev) => {
        // If news not loaded yet, prevent default and mark pending
        if(!allNews || allNews.length === 0) {
            ev.preventDefault();
            pendingClick = true;
            moreBtn.textContent = 'Laden...';
            return;
        }

        if(!clicked) {
            ev.preventDefault();
            renderNews(allNews);
            moreBtn.textContent = 'Zur News‑Seite';
            clicked = true;
            return;
        }
        // second click: allow navigation to /News (default behavior)
    });

    // If news load completes and a pending click exists, perform the action
    window.addEventListener('news:loaded', () => {
        if(pendingClick) {
            // show all news inline
            if(typeof renderNews === 'function') renderNews(allNews);
            moreBtn.textContent = 'Zur News‑Seite';
            clicked = true;
            pendingClick = false;
        }
    });
})();

fetch("/news.json")
    .then(res => res.json())
    .then(news => {

        news.sort((a, b) => parseDateStr(b.date) - parseDateStr(a.date));

        allNews = news;
        renderNews(news);

        // Event, damit andere Seiten nur Teile rendern können (z.B. Index zeigt 3)
        try { window.dispatchEvent(new CustomEvent('news:loaded', { detail: { count: news.length } })); } catch(e){}

        // Falls auf der Startseite ein 'Weiter lesen' Button existiert, (ursprüngliche Bindung, rückwärts-kompatibel)
        try {
            const moreBtn = document.getElementById('news-more-btn');
            if (moreBtn) {
                // falls jemand erwartete ältere Logik, belasse; neuer Setup oben kümmert sich bereits um Klicks
            }
        } catch (e) { console.warn('Kein news-more-btn vorhanden', e); }

    });

function renderNews(list) {
    const container = document.getElementById("news-list");
    if(!container) return; // robust gegen Seiten ohne news-list
    container.innerHTML = "";

    list.forEach(entry => {
        const card = document.createElement("article");
        card.classList.add("news-card");

        card.innerHTML = `
            <div class="news-image" style="background-image:url('${entry.image}')"></div>

            <div class="news-content">
                <div class="news-meta">
                    <span class="news-badge">${escapeHtml(entry.category)}</span>
                    <span class="news-date">${escapeHtml(entry.date)}</span>
                </div>

                <h2 class="news-title">${escapeHtml(entry.title)}</h2>

                <p class="small muted">${escapeHtml(entry.description)}</p>

                <span class="news-toggle">Mehr anzeigen</span>
            </div>
        `;

        // Karte anklickbar (öffnet Popup)
        card.addEventListener('click', () => {
            openPopup(entry);
        });

        const toggle = card.querySelector(".news-toggle");
        if (toggle) {
            // Verhindere, dass das Klick auf den Toggle zusätzlich die Karten-Click auslöst
            toggle.addEventListener("click", (e) => {
                e.stopPropagation();
                openPopup(entry);
            });
        }

        container.appendChild(card);
    });
}

function openPopup(entry) {

    const titleEl = document.getElementById("popup-title");
    const dateEl = document.getElementById("popup-date");
    const catEl = document.getElementById("popup-category");
    const bodyEl = document.getElementById("popup-body");
    const imgEl = document.getElementById("popup-image");
    const popup = document.getElementById("news-popup");

    if(titleEl) titleEl.textContent = entry.title || '';
    if(dateEl) dateEl.textContent = entry.date || '';
    if(catEl) catEl.textContent = entry.category || '';
    if(bodyEl) bodyEl.innerHTML = entry.longtext || entry.description || '';
    if(imgEl) imgEl.src = entry.image || ''; imgEl.alt = entry.title || 'News Bild';

    if(popup) {
        popup.classList.add("active");
        try { document.body.classList.add('popup-open'); } catch(e){}
    }
}

// close handlers (robust, prüfen ob vorhanden)
const popupClose = document.getElementById("popup-close");
if(popupClose) popupClose.addEventListener("click", () => {
    const p = document.getElementById("news-popup"); if(p) p.classList.remove("active");
    try { document.body.classList.remove('popup-open'); } catch(e){}
});

const popupWrap = document.getElementById("news-popup");
if(popupWrap) popupWrap.addEventListener("click", (e) => {
    if (e.target === e.currentTarget) {
        e.currentTarget.classList.remove("active");
        try { document.body.classList.remove('popup-open'); } catch(e){}
    }
});

// filter buttons (falls vorhanden)
document.querySelectorAll(".filter-btn").forEach(btn => {
    btn.addEventListener("click", () => {

        document.querySelectorAll(".filter-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        const filter = btn.dataset.filter;

        if (filter === "all") {
            renderNews(allNews);
        } else {
            const filtered = allNews.filter(n => n.category === filter);
            renderNews(filtered);
        }
    });
});

// kleine Hilfsfunktion zum Escapen
function escapeHtml(s){
    if(!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
