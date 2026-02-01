document.addEventListener('DOMContentLoaded', () => {
    // Mobile Menu
    const mobileMenuBtn = document.getElementById('mobile-menu');
    const navLinks = document.querySelector('.nav-links');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // Sticky Navbar
    window.addEventListener('scroll', () => {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(5, 5, 5, 0.95)';
            navbar.style.boxShadow = '0 5px 15px rgba(0,0,0,0.5)';
        } else {
            navbar.style.background = 'rgba(5, 5, 5, 0.8)';
            navbar.style.boxShadow = 'none';
        }
    });

    // Comparison Slider Logic
    const slider = document.querySelector('.comparison-slider');
    if (slider) {
        const overlay = slider.querySelector('.overlay-img');
        const handle = slider.querySelector('.slider-handle');
        let isDown = false;

        // Prevent native image dragging
        slider.querySelectorAll('img').forEach(img => {
            img.addEventListener('dragstart', (e) => e.preventDefault());
        });

        const startSliding = (e) => {
            isDown = true;
            moveSlider(e);
        };

        const stopSliding = () => {
            isDown = false;
        };

        const moveSlider = (e) => {
            if (!isDown) return;

            const rect = slider.getBoundingClientRect();
            let pageX = e.pageX;

            if (e.touches && e.touches[0]) {
                pageX = e.touches[0].pageX;
            } else if (pageX === undefined && e.clientX !== undefined) {
                pageX = e.clientX + window.scrollX;
            }

            let x = pageX - rect.left;

            if (x < 0) x = 0;
            if (x > rect.width) x = rect.width;

            const percentage = (x / rect.width) * 100;

            overlay.style.width = `${percentage}%`;
            handle.style.left = `${percentage}%`;

            overlay.style.backgroundSize = `${rect.width}px ${rect.height}px`;
        };

        const initSlider = () => {
            const rect = slider.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
                overlay.style.backgroundSize = `${rect.width}px ${rect.height}px`;
            }
        };

        slider.addEventListener('mousedown', startSliding);
        window.addEventListener('mouseup', stopSliding);
        window.addEventListener('mousemove', moveSlider);

        slider.addEventListener('touchstart', (e) => startSliding(e), { passive: false });
        window.addEventListener('touchend', stopSliding);
        window.addEventListener('touchmove', moveSlider, { passive: false });

        initSlider();
        window.addEventListener('resize', initSlider);
        window.addEventListener('load', initSlider);
    }

    // BMI Calculator
    const calculateBtn = document.getElementById('calculate-bmi');
    const resultDisplay = document.getElementById('bmi-result-display');
    const resultValue = document.getElementById('bmi-value');

    if (calculateBtn) {
        calculateBtn.addEventListener('click', () => {
            const weight = parseFloat(document.getElementById('bmi-weight').value);
            const height = parseFloat(document.getElementById('bmi-height').value);

            if (weight > 0 && height > 0) {
                const heightInMeters = height / 100;
                const bmi = (weight / (heightInMeters * heightInMeters)).toFixed(2);

                // Determine BMI category and color
                let category = '';
                let color = '';

                if (bmi < 18.5) {
                    category = 'Underweight';
                    color = '#7fa8d1'; // Muted blue
                } else if (bmi >= 18.5 && bmi < 25) {
                    category = 'Normal Weight';
                    color = '#8bc34a'; // Muted green
                } else if (bmi >= 25 && bmi < 30) {
                    category = 'Overweight';
                    color = '#ffb74d'; // Muted orange
                } else {
                    category = 'Obese';
                    color = '#e57373'; // Muted red
                }

                resultValue.innerHTML = `${bmi} <span style="font-size: 0.9rem; color: ${color}; display: block; margin-top: 8px; font-weight: 500;">(${category})</span>`;
                resultDisplay.style.display = 'inline-block';
            } else {
                alert('Please enter valid weight and height.');
            }
        });
    }

    // Pricing Toggle
    const toggleBtns = document.querySelectorAll('.toggle-btn');
    const priceTags = document.querySelectorAll('.price-tag');
    const pricingSection = document.getElementById('pricing');

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all
            toggleBtns.forEach(b => b.classList.remove('active'));
            // Add to clicked
            btn.classList.add('active');

            const period = btn.dataset.period;

            // Toggle section class
            if (pricingSection) {
                pricingSection.classList.remove('monthly', 'yearly');
                pricingSection.classList.add(period);
            }

            priceTags.forEach(tag => {
                const price = tag.getAttribute(`data-${period}`);
                // Update text, preserving the span
                tag.innerHTML = `${price} <span>/ ${period === 'monthly' ? 'Mo' : 'Yr'}</span>`;
            });
        });
    });

    // Client Reviews Slider (Simple transform)
    const reviewSlider = document.getElementById('review-slider');
    const prevBtn = document.getElementById('prev-review');
    const nextBtn = document.getElementById('next-review');

    // Duplicate reviews for infinite loop illusion if wanted, or just simple scroll
    // Here we will use simple scroll/transform
    let currentSlide = 0;

    if (reviewSlider && prevBtn && nextBtn) {
        const cards = reviewSlider.querySelectorAll('.review-card');
        const cardWidth = 330; // 300 width + 30 margin approx
        const visibleCards = Math.floor(reviewSlider.parentElement.offsetWidth / cardWidth);
        const maxSlide = cards.length - 1; // Simplify for now

        const updateSlider = () => {
            // Center active slide or just scroll
            // Let's just do translation ensuring center
            // offset = (containerWidth / 2) - (cardWidth / 2) - (index * cardWidth)
            const containerWidth = reviewSlider.parentElement.offsetWidth;
            const offset = (containerWidth / 2) - (cardWidth / 2) - (currentSlide * cardWidth);

            reviewSlider.style.transform = `translateX(${offset}px)`;

            // Highlight center card style optionally
            cards.forEach((card, index) => {
                if (index === currentSlide) {
                    card.style.transform = 'scale(1.1)';
                    card.style.opacity = '1';
                    card.style.zIndex = '2';
                } else {
                    card.style.transform = 'scale(0.9)';
                    card.style.opacity = '0.5';
                    card.style.zIndex = '1';
                }
            });
        };

        nextBtn.addEventListener('click', () => {
            if (currentSlide < maxSlide) {
                currentSlide++;
            } else {
                currentSlide = 0; // Loop back
            }
            updateSlider();
        });

        prevBtn.addEventListener('click', () => {
            if (currentSlide > 0) {
                currentSlide--;
            } else {
                currentSlide = maxSlide; // Loop to end
            }
            updateSlider();
        });

        // Initialize
        updateSlider();
        window.addEventListener('resize', updateSlider);
    }

    // Handle Join Now -> Scroll to Login Button visual cue
    document.querySelectorAll('a[href="#header-login"]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            // Scroll to top where navbar is
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

            // Highlight the login button
            const loginBtn = document.getElementById('header-login');
            if (loginBtn) {
                // Remove class if exists to restart animation
                loginBtn.classList.remove('highlight-btn');
                // Force reflow
                void loginBtn.offsetWidth;
                // Add class
                loginBtn.classList.add('highlight-btn');
            }
        });
    });


    // Load More / Show Less Equipment Logic
    const loadMoreBtn = document.getElementById('load-more-equipment');
    const showLessBtn = document.getElementById('show-less-equipment');
    const hiddenItems = document.querySelectorAll('.hidden-item');

    console.log('Load More Button:', loadMoreBtn);
    console.log('Show Less Button:', showLessBtn);
    console.log('Hidden Items Count:', hiddenItems.length);

    if (loadMoreBtn && showLessBtn) {
        loadMoreBtn.addEventListener('click', () => {
            console.log('See More clicked');
            hiddenItems.forEach(item => {
                item.style.display = 'block';
                item.style.animation = 'fadeIn 0.5s ease-out';
            });
            loadMoreBtn.style.display = 'none';
            showLessBtn.style.display = 'inline-block';
            console.log('Show Less button display:', showLessBtn.style.display);
        });

        showLessBtn.addEventListener('click', () => {
            console.log('Show Less clicked');
            hiddenItems.forEach(item => {
                item.style.display = 'none';
            });
            loadMoreBtn.style.display = 'inline-block';
            showLessBtn.style.display = 'none';

            // Optional: Scroll back to Equipment section top to prevent disorientation
            const equipSection = document.getElementById('equipment-showcase');
            if (equipSection) {
                equipSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    } else {
        console.error('Buttons not found!');
    }

    // Gender Select Color Handling
    const genderSelect = document.querySelector('.custom-select');
    if (genderSelect) {
        genderSelect.addEventListener('change', function () {
            if (this.value !== "") {
                this.style.color = "#fff";
            } else {
                this.style.color = "#aaa";
            }
        });
    }
});