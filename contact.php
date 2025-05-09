<?php 
require_once 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Add your email sending logic here
    // For now, we'll just show a success message
    $success = true;
}
?>

<!-- Contact Hero Section -->
<section class="contact-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 animate-fade-up">
                <h1 class="display-4 fw-bold mb-4">Get in Touch</h1>
                <p class="lead mb-4">Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h3>Address</h3>
                            <p>123 Education Street, Academic City, 12345</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h3>Phone</h3>
                            <p>+1 234 567 8900</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h3>Email</h3>
                            <p>placement@university.edu</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 animate-fade-left">
                <div class="contact-image">
                    <img src="assets/images/hero-illustration.svg" alt="Contact Illustration" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Form Section -->
<section class="contact-form-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success animate-fade-up" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        Thank you for your message! We'll get back to you soon.
                    </div>
                <?php endif; ?>
                
                <div class="contact-form-card animate-fade-up">
                    <form method="POST" action="" id="contactForm">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" required>
                                    <label for="name">Your Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" required>
                                    <label for="email">Your Email</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" required>
                                    <label for="subject">Subject</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="message" name="message" placeholder="Your Message" style="height: 150px" required></textarea>
                                    <label for="message">Your Message</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section">
    <div class="container">
        <div class="map-container animate-fade-up">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.2219901290355!2d-74.00369368400567!3d40.71312937933185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a23e28c1191%3A0x49f75d3281df052a!2s150%20Park%20Row%2C%20New%20York%2C%20NY%2010007!5e0!3m2!1sen!2sus!4v1644262070010!5m2!1sen!2sus"
                width="100%" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
    </div>
</section>

<style>
/* Contact Hero Section */
.contact-hero {
    background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
    color: white;
    padding: 6rem 0;
    position: relative;
    overflow: hidden;
}

.contact-hero::before {
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

.contact-info {
    margin-top: 3rem;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 2rem;
}

.contact-item i {
    font-size: 1.5rem;
    margin-right: 1rem;
    margin-top: 0.25rem;
}

.contact-item h3 {
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
}

.contact-item p {
    margin: 0;
    opacity: 0.9;
}

/* Contact Form Section */
.contact-form-section {
    padding: 6rem 0;
    background: #f8f9fa;
    margin-top: -4rem;
    border-radius: 2rem 2rem 0 0;
    position: relative;
    z-index: 1;
}

.contact-form-card {
    background: white;
    padding: 3rem;
    border-radius: 1rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.form-floating {
    margin-bottom: 1rem;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #1a73e8;
    box-shadow: 0 0 0 0.25rem rgba(26, 115, 232, 0.1);
}

.btn-primary {
    background: #1a73e8;
    border: none;
    padding: 1rem 2rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #0d47a1;
    transform: translateY(-2px);
}

/* Map Section */
.map-section {
    padding: 6rem 0;
    background: white;
}

.map-container {
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
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
    .contact-hero {
        padding: 4rem 0;
    }
    
    .contact-image {
        margin-top: 2rem;
        text-align: center;
    }
    
    .contact-form-card {
        padding: 2rem;
    }
}

@media (max-width: 576px) {
    .contact-form-card {
        padding: 1.5rem;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 