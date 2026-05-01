document.getElementById("header").innerHTML = `
<header class="site-header">
    <div class="container nav">

        <div class="brand">
            <a href="/Home" class="logo" aria-label="Hexoria Netzwerk">
                <img src="/images/icon/logo.png" alt="Hexoria Logo">
            </a>
        </div>

        <nav>
            <a href="/Home" class="nav-link">Home</a>
            <a href="/News" class="nav-link">News</a>
            <a href="/Roadmap" class="nav-link">Roadmap</a>
            <a href="/Ranks" class="nav-link">Ränge</a>
        </nav>

    </div>
</header>
`;

(function () {
    const parts = window.location.pathname.split("/").filter(Boolean);
    const current = "/" + (parts[0] || "Home");
    document.querySelectorAll(".nav-link").forEach(function (link) {
        if (link.getAttribute("href") === current) {
            link.classList.add("active");
        }
    });
})();
