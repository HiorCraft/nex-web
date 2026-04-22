fetch("/Admin/api.php?entity=mods")
    .then(res => res.json())
    .then(data => {
        loadMods(data.allowed || [], "allowed-mods", "whitelist");
        loadMods(data.banned || [], "banned-mods", "blacklist");
    });

function loadMods(list, elementId, type) {
    const ul = document.getElementById(elementId);
    if (!ul || !Array.isArray(list)) return;

    list.forEach(mod => {
        fetch(`https://api.modrinth.com/v2/project/${mod.slug}`)
            .then(res => res.json())
            .then(api => {
                const li = document.createElement("li");
                li.classList.add("mod-item", type);

                li.innerHTML = `
                    <img src="${api.icon_url}" class="mod-icon" alt="${api.title} Icon">
                    <a href="https://modrinth.com/mod/${api.slug}" target="_blank">${api.title}</a>
                `;

                ul.appendChild(li);
            });
    });
}
