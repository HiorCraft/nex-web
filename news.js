fetch("news.json")
    .then(res => res.json())
    .then(news => {
        allNews = news;
        renderNews(news);
    });

function renderNews(list) {
    const container = document.getElementById("news-list");
    container.innerHTML = "";

    list.forEach(entry => {
        const card = document.createElement("article");
        card.classList.add("news-card");

        card.innerHTML = `
            <div class="news-image" style="background-image:url('${entry.image}')"></div>
            <div class="news-content">
                <div class="news-meta">
                    <span class="news-badge">${entry.category}</span>
                    <span class="news-date">${entry.date}</span>
                </div>
                <h2 class="news-title">${entry.title}</h2>
                <p class="small muted">${entry.description}</p>
            </div>
        `;
        card.addEventListener("click", () => {
            openNewsPopup(entry);
        });

        container.appendChild(card);
    });
}

function openNewsPopup(entry) {
    const popup = document.getElementById("news-popup");
    const inner = document.getElementById("popup-inner");

    inner.innerHTML = `
        <h1>${entry.title}</h1>
        <p class="small muted">${entry.date} • ${entry.category}</p>
        <img src="${entry.image}" alt="${entry.title}">
        ${entry.longtext}
    `;

    popup.style.display = "flex";
}

// Popup schließen
document.querySelector(".popup-close").addEventListener("click", () => {
    document.getElementById("news-popup").style.display = "none";
});

// Klick auf Hintergrund schließt Popup
document.getElementById("news-popup").addEventListener("click", (e) => {
    if (e.target.id === "news-popup") {
        e.target.style.display = "none";
    }
});
