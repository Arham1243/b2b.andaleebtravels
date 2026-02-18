@extends('frontend.layouts.main')
@section('content')
    <div class="banner-slider">
        <div class="banner">
            <div class="banner__img">
                <img src="https://andaleebtours.com/public/uploads/banners/1bf05961-69c6-4cbf-bbd1-9ba5cdc92a9d.webp"
                    alt="Banner" class="imgFluid">
            </div>
        </div>
        <div class="banner">
            <div class="banner__img">
                <img src="https://andaleebtours.com/public/uploads/banners/47dd568f-2115-4cc9-8419-f3cd2b443d7c.webp"
                    alt="Banner" class="imgFluid">
            </div>
        </div>
        <div class="banner">
            <div class="banner__img">
                <img src="https://andaleebtours.com/public/uploads/banners/b773c4c8-7130-4083-980b-24523966b263.webp"
                    alt="Banner" class="imgFluid">
            </div>
        </div>
    </div>

    <div class="about-wrapper">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="about-images">
                        <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&q=80&w=800"
                            alt="image" loading="lazy" class="first-image imgFluid">
                        <img src="https://images.unsplash.com/photo-1541410965313-d53b3c16ef17?auto=format&fit=crop&q=80&w=600"
                            alt="image" loading="lazy" class="second-image imgFluid">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="about-content section-content">
                        <div class="sub-heading justify-content-start">
                            <img src="{{ asset('frontend/assets/images/arrow-1.png') }}" alt="image"
                                class="imgFluid arrow-image">
                            About us
                        </div>
                        <h2 class="heading">Welcome to Andaleeb Travel&nbsp;Agency</h2>

                        <p>Established in 2021 and proudly based in Dubai, Andaleeb Travel Agency has spent the past four
                            years turning travel dreams into reality. With a strong reputation in the industry, we offer a
                            comprehensive range of services including UAE tourist visas, flight bookings, hotel stays,
                            holiday packages, guided tours, and travel insurance. Whether you're planning a relaxing getaway
                            or a business trip, our expert team ensures a smooth, stress-free experience. With our
                            commitment to quality service and competitive pricing, exploring the UAE and international
                            destinations has never been easier or more affordable..</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="perks-wrapper mar-y">
        <div class="container">
            <div class="row justify-content-center mb-4">
                <div class="col-md-9">
                    <div class="section-content text-center">
                        <div class="sub-heading color-primary">
                            <img src="{{ asset('frontend/assets/images/arrow-1.png') }}" alt="image"
                                class="imgFluid arrow-image">
                            Launch Your Business
                            <img src="{{ asset('frontend/assets/images/arrow-2.png') }}" alt="image"
                                class="imgFluid arrow-image">
                        </div>
                        <div class="heading">With UAE’s Top Travel Agencyl</div>
                    </div>
                </div>
            </div>
            <div class="row perks-slider">
                <div class="col-md-3">
                    <div class="perks-card">
                        <div class="perks-card__icon">
                            <i class='bx bxs-quote-left'></i>
                        </div>
                        <div class="perks-card__content">
                            <div class="title">Instant B2B Access</div>
                            <p>Get connected to thousands of global travel partners and resellers in one smart dashboard.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="perks-card">
                        <div class="perks-card__icon">
                            <i class='bx bxs-quote-left'></i>
                        </div>
                        <div class="perks-card__content">
                            <div class="title">Real-Time Booking</div>
                            <p>Books Tickets, Hotels ,Tours , Visas , and Transfer instantly with live Inventory and Zero
                                Delay.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="perks-card">
                        <div class="perks-card__icon">
                            <i class='bx bxs-quote-left'></i>
                        </div>
                        <div class="perks-card__content">
                            <div class="title">White Label Solutions</div>
                            <p>Launch your own branded portal powered by our tech and ready to scale fast.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="perks-card">
                        <div class="perks-card__icon">
                            <i class='bx bxs-quote-left'></i>
                        </div>
                        <div class="perks-card__content">
                            <div class="title">Dedicated Support</div>
                            <p>Our Dubai-based team is here 24/7 to back your business with expert assistance.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="perks-card">
                        <div class="perks-card__icon">
                            <i class='bx bxs-quote-left'></i>
                        </div>
                        <div class="perks-card__content">
                            <div class="title">Fast Payouts</div>
                            <p>Enjoy transparent billing, competitive commissions, and quick withdrawal cycles.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="contact-wrapper">
        <div class="container-fluid p-0">
            <div class="row">
                <div class="col-md-6">
                    <div class="contact-img">
                        <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&q=80&w=1000"
                            alt="Video Bg" class="imgFluid">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="new-contact-content">
                        <div class="section-content mb-4">
                            <div class="sub-heading justify-content-start color-primary">
                                <img src="{{ asset('frontend/assets/images/arrow-1.png') }}" alt="image"
                                    class="imgFluid arrow-image">
                                Join UAE Best Travel Portal
                            </div>
                            <div class="heading">Register your details with us and our team will get back to you shortly
                            </div>
                        </div>
                        <form class="new-contact-form" method="post" action="{{ route('frontend.contact.submit') }}"
                            id="inquiry-form">
                            @csrf
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="input-fields">
                                        <input type="text" placeholder="Name" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-fields">
                                        <input type="text" placeholder="Email" name="email" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-fields">
                                        <input type="text" placeholder="Phone" name="phone" required>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="input-fields">
                                        <textarea type="text" placeholder="Message" name="message" required></textarea>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-12">
                                    <div class="input-fields">
                                        <div class="g-recaptcha" data-sitekey="{{ env('RE_CAPTCHA_SITE_KEY') }}"> </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-fields">
                                        <button type="submit"
                                            class="themeBtn themeBtn--primary themeBtn--full">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('#inquiry-form');
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
