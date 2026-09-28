document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const slides = [...carousel.querySelectorAll('.slide')];
    const dots = carousel.querySelector('.dots');

    let index = 0;

    slides.forEach((slide, slideIndex) => {
        const button = document.createElement('button');

        button.type = 'button';
        button.textContent = '•';
        button.setAttribute('aria-label', `Открыть слайд ${slideIndex + 1}`);
        button.addEventListener('click', () => show(slideIndex));

        dots.appendChild(button);
    });

    function show(nextIndex) {
        index = (nextIndex + slides.length) % slides.length;

        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle('active', slideIndex === index);
        });

        [...dots.children].forEach((button, buttonIndex) => {
            button.style.opacity = buttonIndex === index ? '1' : '.35';
        });
    }

    carousel.querySelector('.prev').addEventListener('click', () => {
        show(index - 1);
    });

    carousel.querySelector('.next').addEventListener('click', () => {
        show(index + 1);
    });

    show(0);
});
