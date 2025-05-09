<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="about-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 animate-fade-up">
                <h1 class="display-4 fw-bold mb-4">About Our Training & Placement Office</h1>
                <p class="lead mb-4">Bridging the gap between academia and industry, we help students transform their potential into success stories.</p>
            </div>
            <div class="col-lg-6 animate-fade-left">
                <div class="about-hero-image">
                    <img src="assets/images/hero-illustration.svg" alt="About Us Illustration" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision Section -->
<section class="mission-vision">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="mission-card animate-fade-up">
                    <div class="card-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h2>Our Mission</h2>
                    <p>To empower students with the skills, opportunities, and connections they need to launch successful careers in their chosen fields.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="vision-card animate-fade-up delay-1">
                    <div class="card-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h2>Our Vision</h2>
                    <p>To be the leading facilitator of industry-academia partnerships and create a robust ecosystem for career development.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <h2 class="section-title text-center mb-5 animate-fade-up">What We Offer</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card animate-fade-up">
                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Training Programs</h3>
                    <p>Comprehensive training modules designed to enhance technical and soft skills essential for professional success.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card animate-fade-up delay-1">
                    <div class="feature-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Industry Partnerships</h3>
                    <p>Strong connections with leading companies across various sectors for placement opportunities.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card animate-fade-up delay-2">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Career Guidance</h3>
                    <p>Personalized mentoring and guidance to help students make informed career decisions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section">
    <div class="container">
        <h2 class="section-title text-center mb-5 animate-fade-up">Meet Our Team</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="team-card animate-fade-up">
                    <div class="team-image">
                        <img src="assets/images/placeholder.jpg" alt="Team Member" class="img-fluid">
                    </div>
                    <div class="team-info">
                        <h3>John Doe</h3>
                        <p class="designation">Training & Placement Officer</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Add more team members as needed -->
        </div>
    </div>
</section>

<!-- Achievement Section -->
<section class="achievement-section">
    <div class="container">
        <div class="achievement-grid">
            <div class="achievement-card animate-fade-up">
                <div class="achievement-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="achievement-number">95%</div>
                <div class="achievement-label">Placement Rate</div>
            </div>
            <div class="achievement-card animate-fade-up delay-1">
                <div class="achievement-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="achievement-number">100+</div>
                <div class="achievement-label">Partner Companies</div>
            </div>
            <div class="achievement-card animate-fade-up delay-2">
                <div class="achievement-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="achievement-number">1000+</div>
                <div class="achievement-label">Students Placed</div>
            </div>
            <div class="achievement-card animate-fade-up delay-3">
                <div class="achievement-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="achievement-number">50+</div>
                <div class="achievement-label">Training Programs</div>
            </div>
        </div>
    </div>
</section>

<style>
/* About Hero Section */
.about-hero {
    background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
    color: white;
    padding: 6rem 0;
    position: relative;
    overflow: hidden;
}

.about-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('assets/images/pattern.svg');
    opacity: 0.1;
    animation: slide 20s linear infinite;
}

.about-hero-image {
    position: relative;
    z-index: 1;
}

/* Mission & Vision Section */
.mission-vision {
    padding: 6rem 0;
    background: #f8f9fa;
}

.mission-card, .vision-card {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    height: 100%;
    transition: transform 0.3s ease;
}

.mission-card:hover, .vision-card:hover {
    transform: translateY(-5px);
}

.card-icon {
    font-size: 2.5rem;
    color: #1a73e8;
    margin-bottom: 1.5rem;
}

/* Features Section */
.features-section {
    padding: 6rem 0;
    background: white;
}

.feature-card {
    text-align: center;
    padding: 2rem;
    border-radius: 1rem;
    background: white;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    height: 100%;
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    font-size: 2.5rem;
    color: #1a73e8;
    margin-bottom: 1.5rem;
}

/* Team Section */
.team-section {
    padding: 6rem 0;
    background: #f8f9fa;
}

.team-card {
    background: white;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.team-card:hover {
    transform: translateY(-5px);
}

.team-image img {
    width: 100%;
    height: 300px;
    object-fit: cover;
}

.team-info {
    padding: 1.5rem;
    text-align: center;
}

.team-info h3 {
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.designation {
    color: #6c757d;
    margin-bottom: 1rem;
}

.social-links a {
    color: #1a73e8;
    margin: 0 0.5rem;
    font-size: 1.2rem;
    transition: color 0.3s ease;
}

.social-links a:hover {
    color: #0d47a1;
}

/* Achievement Section */
.achievement-section {
    padding: 6rem 0;
    background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
    color: white;
}

.achievement-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
}

.achievement-card {
    text-align: center;
    padding: 2rem;
}

.achievement-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    opacity: 0.9;
}

.achievement-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.achievement-label {
    font-size: 1.1rem;
    opacity: 0.9;
}

/* Animations */
.animate-fade-up {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeUp 0.6s ease forwards;
}

.animate-fade-left {
    opacity: 0;
    transform: translateX(20px);
    animation: fadeLeft 0.6s ease forwards;
}

.delay-1 {
    animation-delay: 0.2s;
}

.delay-2 {
    animation-delay: 0.4s;
}

.delay-3 {
    animation-delay: 0.6s;
}

@keyframes fadeUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeLeft {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slide {
    from {
        background-position: 0 0;
    }
    to {
        background-position: 100% 100%;
    }
}

/* Responsive Design */
@media (max-width: 991px) {
    .about-hero {
        padding: 4rem 0;
    }
    
    .about-hero-image {
        margin-top: 2rem;
    }
}

@media (max-width: 768px) {
    .achievement-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .achievement-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 