/* Models GeneratePress's pre-bound navigation ancestor contract, without network access. */
document.querySelectorAll('nav .main-nav ul a').forEach(function (link) {
    function requireNavigation() {
        if (!this.closest('nav')) throw new Error('Switcher lost its navigation ancestor');
        var node = this;
        while (node && !node.classList.contains('main-nav')) node = node.parentElement;
        if (!node) throw new Error('Switcher lost the theme focus boundary');
    }
    ['focus', 'blur', 'click'].forEach(function (event) {
        link.addEventListener(event, requireNavigation);
    });
});
