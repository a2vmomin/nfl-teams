(function() {
    // Get all the tab links.
    const tabLinks = [...document.querySelectorAll('.nfl-tabs a')];
    // Get all the tab containers.
    const containers = [...document.querySelectorAll('.team-container')];

    // Add click event listener to the tab links.
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove all the active classes from the tab links.
            removeActiveClasses();

            // Add active class to the clicked tab link.
            link.classList.add('active');

            // We have mapped the container id in the data attribute target for the tab links. Get the target data attribute of currently clicked link.
            const containerId = link.dataset.target;

            // Loop through all the containers and set the display attribute to block if the target data attribute matches the container id. Else hide the container.
            containers.forEach(container => {
                if ('#' + container.id === containerId) {
                    container.style.display = "block";
                } else {
                    container.style.display = "none";
                }
            });
        })
    });

    /**
     * Remove active classes for all tab links.
     */
    function removeActiveClasses() {
        tabLinks.forEach(link => {
            link.classList.remove('active');
        });
    }

    /**
     * We need Resize Oberver API to check the width of the plugin wrapper div.
     * Layout is not properly if the wrapper width is small.
     */
    const resizeObserver = new ResizeObserver(entries => {
        for (let entry of entries) {
            if (entry.contentBoxSize) {
                // Check if the wrapper width is less than 768 and widow width is greater than or equal to 640px.
                if (entry.target.clientWidth < 768 && window.innerWidth >= 640) {
                    // Add no-flex class.
                    addNoFlexClass();
                } else {
                    // Remove no-flex class.
                    removeNoFlexClass();
                }
            }
        }
    });
    resizeObserver.observe(document.querySelector('.nfl-teams'));

    /**
     * Add no-flex class.
     */
    const addNoFlexClass = () => {
        const teamInfoElements = [...document.querySelectorAll('.team-info')];
        teamInfoElements.map(teamInfoElement => (
            teamInfoElement.classList.add("no-flex")
        ));
    }

    /**
     * Remove no-flex class.
     */
    const removeNoFlexClass = () => {
        const teamInfoElements = [...document.querySelectorAll('.team-info')];
        teamInfoElements.map(teamInfoElement => (
            teamInfoElement.classList.remove("no-flex")
        ));
    }
})();