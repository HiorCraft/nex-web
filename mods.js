fetch("mods.json")
    .then(res => res.json())
    .then(data => {
        loadMods(data.allowed, "allowed-mods");
        loadMods(data.banned, "banned-mods");
    });

function loadMods(list, elementId) {
    const ul = document.getElementById(elementId);

    list.forEach(mod => {
        fetch(`https://api.modrinth.com/v2/project/${mod.slug}`)
            .then(res => res.json())
            .then(api => {
                const li = document.createElement("li");
                li.classList.add("mod-item");

                li.innerHTML = `
                    <img src="${api.icon_url}" class="mod-icon" alt="${api.title} Icon">
                    <a href="https://modrinth.com/mod/${api.slug}" target="_blank">${api.title}</a>
                `;

                ul.appendChild(li);
            })
            .catch(err => console.error("Fehler bei Modrinth API:", err));
    });
}
