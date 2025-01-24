document.addEventListener("DOMContentLoaded", function () {
    const fadeElements = document.querySelectorAll('.fade-in');
    console.log("Found elements:", fadeElements);

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
            } else {
                entry.target.classList.remove('show');
            }
        });
    }, { threshold: 0.5 }); // Change to 0.5 or other values to adjust when the animation triggers
    

    fadeElements.forEach(element => observer.observe(element));
});

