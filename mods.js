fetch("mods.json")
    .then(res => res.json())
    .then(mods => {
        const container = document.getElementById("mod-list");

        mods.forEach(mod => {
            const card = document.createElement("article");
            card.classList.add("card", "mod-card");

            card.innerHTML = `
                <img src="${mod.icon}" alt="${mod.name} Icon" class="mod-icon">

                <h3>${mod.name}</h3>
                <p class="small muted">${mod.description}</p>

                <a class="btn small" href="${mod.modrinth}" target="_blank" rel="noopener">
                    Auf Modrinth ansehen
                </a>
            `;

            container.appendChild(card);
        });
    })
    .catch(err => console.error("Fehler beim Laden der Modliste:", err));
