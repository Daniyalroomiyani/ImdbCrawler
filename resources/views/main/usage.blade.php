<!-- ======= usage Section ======= -->
<section id="usage" class="usage">
    <div class="container">

        <div class="section-title">
            <h2>send a Crawler</h2>
            <p>
                Simply Fill IMDb ID and Type of date you want and then it Crawls
            </p>
        </div>

        <div class="row" data-aos="fade-in">

            <div class="col-lg-5 d-flex align-items-stretch">
                <form action="forms/contact.php" method="post" role="form" class="php-email-form">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="name">IMDb Id </label>
                            <input type="text" name="name" class="form-control" id="name" required>
                        </div>


                    </div>
                    <div class="form-group">
                        <label for="name">Subject</label>
                        <input type="text" class="form-control" name="subject" id="subject" required>
                    </div>

                    <div class="my-3">
                        <div class="loading">Loading</div>
                        <div class="error-message"></div>
                        <div class="sent-message">Your message has been sent. Thank you!</div>
                    </div>
                    <div class="text-center"><button type="submit">Send Message</button></div>
                </form>


            </div>

            <div class="col-lg-7 mt-5 mt-lg-0 d-flex align-items-stretch">
            </div>

        </div>

    </div>
</section><!-- End usage Section -->
