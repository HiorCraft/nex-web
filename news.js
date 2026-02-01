let allNews = [];

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

                <span class="news-toggle">Mehr anzeigen</span>

                <div class="news-extra">
                    ${entry.longtext}
                </div>
            </div>
        `;

        card.querySelector(".news-toggle").addEventListener("click", () => {

            document.querySelectorAll(".news-card.open").forEach(openCard => {
                if (openCard !== card) {
                    openCard.classList.remove("open");
                    openCard.querySelector(".news-toggle").textContent = "Mehr anzeigen";
                }
            });

            card.classList.toggle("open");

            const toggle = card.querySelector(".news-toggle");
            toggle.textContent = card.classList.contains("open")
                ? "Weniger anzeigen"
                : "Mehr anzeigen";
        });

        container.appendChild(card);
    });
}

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
