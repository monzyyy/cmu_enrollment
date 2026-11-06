document.addEventListener("DOMContentLoaded", function () {
    const menuButton = document.getElementById("menuButton");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    if (!menuButton || !sidebar || !overlay) return;
    menuButton.setAttribute("aria-controls", "sidebar");
    menuButton.setAttribute("aria-expanded", "false");
    function setOpen(open) {
        sidebar.classList.toggle("open", open);
        overlay.classList.toggle("show", open);
        document.body.classList.toggle("sidebar-open", open);
        menuButton.setAttribute("aria-expanded", String(open));
        menuButton.setAttribute("aria-label", open ? "Close menu" : "Open menu");
    }
    menuButton.addEventListener("click", function () { setOpen(!sidebar.classList.contains("open")); });
    overlay.addEventListener("click", function () { setOpen(false); });
    sidebar.querySelectorAll(".menu-link").forEach(function (link) {
        link.addEventListener("click", function () { setOpen(false); });
    });
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") setOpen(false);
    });
    window.addEventListener("resize", function () {
        if (window.innerWidth > 900 && sidebar.classList.contains("open")) setOpen(false);
    });
});
