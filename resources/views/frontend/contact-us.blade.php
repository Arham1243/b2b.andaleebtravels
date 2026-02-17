@extends('frontend.layouts.main')
@section('content')
    @if(isset($banner) && $banner)
    <section class="page-header py-5 d-flex align-items-center"
        style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('{{ asset($banner->image) }}'); background-size: cover; background-position: center; height: 350px;">
        <div class="container text-center text-white">
            <h1 class="fw-bold display-4">{{ $banner->heading ?? 'Get in Touch' }}</h1>
            @if($banner->paragraph)
                <p class="lead mb-0 opacity-75">{{ $banner->paragraph }}</p>
            @else
                <p class="lead mb-0 opacity-75">We are here to help plan your next journey</p>
            @endif
        </div>
    </section>
    @else
    <section class="page-header py-5 d-flex align-items-center"
        style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('{{ asset('frontend/assets/images/contact-banner.jpeg') }}'); background-size: cover; background-position: center; height: 350px;">
        <div class="container text-center text-white">
            <h1 class="fw-bold display-4">Get in Touch</h1>
            <p class="lead mb-0 opacity-75">We are here to help plan your next journey</p>
        </div>
    </section>
    @endif

    <!-- Contact Content -->
    <section class="section-contact py-5 bg-light">
        <div class="container py-4">
            <div class="row g-5">

                <!-- Left Column: Contact Info & Map -->
                <div class="col-lg-5">
                    <div class="contact-details-wrapper">
                        <h3 class="fw-bold">Contact Information</h3>
                        <p class="text-muted mb-4">Have questions about a tour or need a custom quote? Reach out to us
                            directly or visit our office.</p>

                        <!-- Info Items -->
                        <div class="d-flex align-items-start mb-4">
                            <div class="icon-box">
                                <i class='bx bx-map'></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="fw-bold mb-1">Our Location</h6>
                                <p class="text-muted mb-0">
                                    {!! nl2br(e($config['ADDRESS'] ?? 'Office# 18, Russia Cluster, Building V-05,<br>International City, Dubai, U.A.E')) !!}
                                </p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-4">
                            <div class="icon-box">
                                <i class='bx bx-phone'></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="fw-bold mb-1">Phone Number</h6>
                                <a href="tel:{{ $config['COMPANYPHONE'] ?? '+97145766068' }}"
                                    class="text-decoration-none text-muted d-block hover-primary">{{ $config['COMPANYPHONE'] ?? '+971 4 576 6068' }}</a>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-4">
                            <div class="icon-box">
                                <i class='bx bx-mobile-alt'></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="fw-bold mb-1">Mobile / WhatsApp</h6>
                                <a href="tel:{{ $config['WHATSAPP'] ?? '+971525748986' }}"
                                    class="text-decoration-none text-muted d-block hover-primary">{{ $config['WHATSAPP'] ?? '+971 52 574 8986' }}</a>
                            </div>
                        </div>

                        <!-- Embedded Map -->
                        <div class="map-container mt-4 rounded-4 overflow-hidden shadow-sm border">
                            <iframe
                                src="https://maps.google.com/maps?q={{ urlencode($config['ADDRESS'] ?? 'Office# 18, Russia Cluster, Building V-05, International City, Dubai, U.A.E') }}&amp;output=embed"
                                width="100%" height="420"style="border:0;" allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="contact-form-card bg-white p-4 p-md-5 rounded-4 shadow-sm h-100">
                        <h3 class="fw-bold mb-2">Send us a Message</h3>
                        <p class="text-muted mb-4 pb-1">Fill out the form below and our team will get back to you within 24
                            hours.</p>

                        <form action="{{ route('frontend.contact.submit') }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <!-- Name -->
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label fw-semibold small  text-muted">Full
                                            Name</label>
                                        <input type="text" name="name" class="custom-input" value="{{ old('name') }}" required>
                                    </div>
                                </div>

                                <!-- Phone -->
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label fw-semibold small  text-muted">Phone
                                            Number</label>
                                        <input type="tel" name="phone" class="custom-input" value="{{ old('phone') }}" required>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="col-12">
                                    <div class="form-group mb-3">
                                        <label class="form-label fw-semibold small  text-muted">Email
                                            Address</label>
                                        <input type="email" name="email" class="custom-input" value="{{ old('email') }}" required>
                                    </div>
                                </div>

                                <!-- Message -->
                                <div class="col-12">
                                    <div class="form-group mb-4">
                                        <label class="form-label fw-semibold small  text-muted">Your
                                            Message</label>
                                        <textarea name="message" class="custom-input" rows="5" placeholder="Tell us about your travel plans..." required>{{ old('message') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-12 mt-0">
                                    <div class="form-group">
                                        <div class="g-recaptcha" data-sitekey="{{ env('RE_CAPTCHA_SITE_KEY') }}"> </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="col-12">
                                    <button type="submit" class="btn-primary-custom">
                                        Send Message <i class='bx bx-paper-plane ms-2'></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('js')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const recaptcha = grecaptcha.getResponse();

                if (!recaptcha) {
                    e.preventDefault();
                    showMessage("Please complete the reCAPTCHA before submitting.", "error");
                    return false;
                }
            });

        });
    </script>
@endpush
