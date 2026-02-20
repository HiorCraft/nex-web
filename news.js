let allNews = [];

fetch("news.json")
    .then(res => res.json())
    .then(news => {

        news.sort((a, b) => new Date(b.date) - new Date(a.date));

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

                <span class="news-toggle">Mehr anzeigen</span>
            </div>
        `;

        card.querySelector(".news-toggle").addEventListener("click", () => {
            openPopup(entry);
        });

        container.appendChild(card);
    });
}

function openPopup(entry) {

    document.getElementById("popup-title").textContent = entry.title;

    document.getElementById("popup-date").textContent = entry.date;

    document.getElementById("popup-category").textContent = entry.category;

    document.getElementById("popup-body").innerHTML = entry.longtext;

    document.getElementById("popup-image").src = entry.image;

    document.getElementById("news-popup").classList.add("active");
}

document.getElementById("popup-close").addEventListener("click", () => {
    document.getElementById("news-popup").classList.remove("active");
});

document.getElementById("news-popup").addEventListener("click", (e) => {
    if (e.target === e.currentTarget) {
        e.currentTarget.classList.remove("active");
    }
});

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
