(function() {
    const tabLinks = [...document.querySelectorAll('.nfl-tabs a')];
    const containers = [...document.querySelectorAll('.team-container')];
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            removeActiveClasses();

            link.classList.add('active');
            const containerId = link.dataset.target;
            containers.forEach(container => {
                // console.log(containerId);
                console.log(container.id + ' - ' + containerId)
                if ('#' + container.id === containerId) {
                    container.style.display = "block";
                } else {
                    container.style.display = "none";
                }
            });
        })
    });

    function removeActiveClasses() {
        tabLinks.forEach(link => {
            link.classList.remove('active');
        });
    }
})();